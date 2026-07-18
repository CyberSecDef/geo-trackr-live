#!/usr/bin/env python3
"""
TLS-terminating HTTP reverse proxy for local phone testing.

Listens on https://0.0.0.0:9443 and forwards to the Laravel dev server on
http://127.0.0.1:9000. It injects X-Forwarded-Proto/Host/For so Laravel (which
trusts 127.0.0.1 as a proxy — see bootstrap/app.php) generates https:// URLs.
That gives the phone a secure context, which mobile browsers require before
they'll hand out geolocation.

Usage:  python3 dev/https-proxy.py
Stop:   Ctrl+C   (or: pkill -f dev/https-proxy.py)
"""

import http.client
import os
import ssl
import sys
from http.server import BaseHTTPRequestHandler, ThreadingHTTPServer

HERE = os.path.dirname(os.path.abspath(__file__))
LISTEN_HOST = "0.0.0.0"
LISTEN_PORT = 9443
BACKEND_HOST = "127.0.0.1"
BACKEND_PORT = 9000
CERT = os.path.join(HERE, "tls", "cert.pem")
KEY = os.path.join(HERE, "tls", "key.pem")

# Hop-by-hop headers must not be forwarded (RFC 7230 §6.1).
HOP_BY_HOP = {
    "connection", "keep-alive", "proxy-authenticate", "proxy-authorization",
    "te", "trailers", "transfer-encoding", "upgrade",
}


class ProxyHandler(BaseHTTPRequestHandler):
    protocol_version = "HTTP/1.1"
    server_version = "geo-trackr-dev-proxy"

    def log_message(self, fmt, *args):  # quieter, one clean line per request
        sys.stderr.write("%s -> %s\n" % (self.address_string(), (fmt % args)))

    def _proxy(self):
        length = int(self.headers.get("Content-Length", 0) or 0)
        body = self.rfile.read(length) if length else None
        host = self.headers.get("Host", "%s:%d" % (BACKEND_HOST, LISTEN_PORT))

        conn = http.client.HTTPConnection(BACKEND_HOST, BACKEND_PORT, timeout=30)
        try:
            conn.putrequest(
                self.command, self.path,
                skip_host=True, skip_accept_encoding=True,
            )
            for key, value in self.headers.items():
                if key.lower() in HOP_BY_HOP or key.lower() == "host":
                    continue
                conn.putheader(key, value)
            # Preserve the original host and advertise the TLS front end.
            conn.putheader("Host", host)
            conn.putheader("X-Forwarded-Proto", "https")
            conn.putheader("X-Forwarded-Host", host)
            conn.putheader("X-Forwarded-For", self.client_address[0])
            conn.putheader("X-Forwarded-Port", str(LISTEN_PORT))
            conn.endheaders()
            if body:
                conn.send(body)

            resp = conn.getresponse()
            payload = resp.read()
        except Exception as exc:  # backend down / timeout
            self.send_error(502, "Bad Gateway: %s" % exc)
            conn.close()
            return

        self.send_response(resp.status, resp.reason)
        for key, value in resp.getheaders():
            if key.lower() in HOP_BY_HOP or key.lower() == "content-length":
                continue
            self.send_header(key, value)
        self.send_header("Content-Length", str(len(payload)))
        self.end_headers()
        if self.command != "HEAD":
            self.wfile.write(payload)
        conn.close()

    # Map every HTTP verb the app uses onto the proxy.
    do_GET = do_POST = do_PUT = do_PATCH = do_DELETE = do_HEAD = do_OPTIONS = _proxy


def main():
    if not (os.path.exists(CERT) and os.path.exists(KEY)):
        sys.exit("Missing cert/key in dev/tls/ — generate them first (see README).")

    ctx = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
    ctx.load_cert_chain(certfile=CERT, keyfile=KEY)

    httpd = ThreadingHTTPServer((LISTEN_HOST, LISTEN_PORT), ProxyHandler)
    httpd.socket = ctx.wrap_socket(httpd.socket, server_side=True)
    print("HTTPS proxy: https://%s:%d  ->  http://%s:%d"
          % (LISTEN_HOST, LISTEN_PORT, BACKEND_HOST, BACKEND_PORT))
    print("Reach it from a phone at:  https://192.168.0.10:%d/" % LISTEN_PORT)
    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        print("\nStopping proxy.")
        httpd.shutdown()


if __name__ == "__main__":
    main()
