# OAuth Setup Guide

GpsPuzzle uses [Laravel Socialite](https://laravel.com/docs/socialite) for social
login. v1 ships **Google**, **Microsoft**, and **Facebook** (Apple is deferred).

This guide walks through registering each OAuth app and plugging the credentials
into `.env`. You register these yourself in each provider's developer console —
they can't be created from the CLI.

## Redirect URIs used by this app

Every provider must be given the **callback** URL exactly. GpsPuzzle's routes are:

| Environment | Redirect / callback URL |
|-------------|-------------------------|
| **Local dev** | `http://localhost:9000/auth/{provider}/callback` |
| **Production** | `https://geo.trackr.live/auth/{provider}/callback` |

…where `{provider}` is `google`, `microsoft`, or `facebook`. So each provider gets
**two** redirect URIs registered (dev + prod), e.g. for Google:

```
http://localhost:9000/auth/google/callback
https://geo.trackr.live/auth/google/callback
```

> Run the local server on port 9000 to match: `php artisan serve --port=9000`.
> `APP_URL` in `.env` is already set to `http://localhost:9000`, and the
> `*_REDIRECT_URI` values derive from it.

After you fill in credentials, run `php artisan config:clear`.

---

## 1. Google

**Console:** <https://console.cloud.google.com/>

1. **Create/select a project** (top bar → project picker → *New Project*, e.g.
   "GpsPuzzle").
2. **Configure the OAuth consent screen:** *APIs & Services → OAuth consent screen*.
   - User type: **External**.
   - App name, support email, developer contact.
   - Scopes: add `.../auth/userinfo.email`, `.../auth/userinfo.profile`, `openid`
     (these are Socialite's defaults; you don't need any sensitive scopes).
   - While in **Testing** mode, add your Google account under *Test users* so you
     can log in before the app is published/verified.
3. **Create credentials:** *APIs & Services → Credentials → Create Credentials →
   OAuth client ID*.
   - Application type: **Web application**.
   - Name: "GpsPuzzle Web".
   - **Authorized redirect URIs** — add both:
     ```
     http://localhost:9000/auth/google/callback
     https://geo.trackr.live/auth/google/callback
     ```
   - (Authorized JavaScript origins are not required — Socialite is a server-side
     redirect flow.)
4. Copy the **Client ID** and **Client secret** into `.env`:
   ```dotenv
   GOOGLE_CLIENT_ID=xxxx.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=xxxx
   GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
   ```

**Going live:** publish the consent screen (*OAuth consent screen → Publish app*).
Basic email/profile scopes don't require Google's app-verification review.

---

## 2. Microsoft

Uses the [`socialiteproviders/microsoft`](https://socialiteproviders.com/Microsoft/)
driver (already installed and registered in `AppServiceProvider`).

**Console:** <https://entra.microsoft.com/> → *Microsoft Entra ID → App registrations*
(also reachable via the Azure portal).

1. **New registration.**
   - Name: "GpsPuzzle".
   - **Supported account types:** *Accounts in any organizational directory and
     personal Microsoft accounts* — this maps to the `common` tenant so both work
     and personal (outlook.com) accounts can sign in.
   - **Redirect URI:** platform **Web**, value
     `http://localhost:9000/auth/microsoft/callback`.
2. After creation, note the **Application (client) ID** from the *Overview* page.
3. **Add the second redirect URI:** *Authentication → Add URI* →
   `https://geo.trackr.live/auth/microsoft/callback`. Save.
4. **Create a client secret:** *Certificates & secrets → New client secret*.
   - Copy the secret **Value** immediately (it's shown only once).
   - Note the expiry (max 24 months) — you'll need to rotate it before then.
5. **API permissions** (usually present by default): *Microsoft Graph → Delegated →*
   `User.Read`, `openid`, `email`, `profile`. Add them if missing.
6. Fill `.env`:
   ```dotenv
   MICROSOFT_CLIENT_ID=xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx
   MICROSOFT_CLIENT_SECRET=xxxx
   MICROSOFT_REDIRECT_URI="${APP_URL}/auth/microsoft/callback"
   ```

> If you later want to restrict to a single org, set a `MICROSOFT_TENANT` and
> uncomment the `tenant` line in `config/services.php`. For public sign-in leave
> it as `common`.

---

## 3. Facebook

**Console:** <https://developers.facebook.com/apps/>

1. **Create app.**
   - Use case: **Authenticate and request data from users with Facebook Login**
     (the older "Consumer" type also works).
   - App name: "GpsPuzzle"; add a contact email.
2. **Add Facebook Login:** in the app dashboard, add the **Facebook Login** product
   (Web). You can skip the quickstart wizard.
3. **Configure OAuth redirect URIs:** *Facebook Login → Settings → Valid OAuth
   Redirect URIs* — add both:
   ```
   http://localhost:9000/auth/facebook/callback
   https://geo.trackr.live/auth/facebook/callback
   ```
   Keep *Client OAuth Login* and *Web OAuth Login* enabled.
4. **Get credentials:** *App settings → Basic* → **App ID** and **App Secret**.
   Also set the **App Domain** (`geo.trackr.live`) and a Privacy Policy URL (Meta
   requires one to take the app live).
5. Fill `.env`:
   ```dotenv
   FACEBOOK_CLIENT_ID=xxxxxxxxxxxx
   FACEBOOK_CLIENT_SECRET=xxxx
   FACEBOOK_REDIRECT_URI="${APP_URL}/auth/facebook/callback"
   ```

**Caveats:**
- While the app is in **Development** mode, only app roles (admins/developers/
  testers you add under *App roles*) can log in. Add your test account there.
- To allow the public **and** receive the user's email, you must switch the app to
  **Live** and complete Meta's review for the `email` / `public_profile`
  permissions. Plan for that review lead time (see spec §16).
- Facebook requires **HTTPS** redirect URIs in Live mode; `localhost` is allowed
  for development.

---

## Verifying

1. Fill the credentials in `.env`, then:
   ```bash
   php artisan config:clear
   php artisan serve --port=9000
   ```
2. Visit `http://localhost:9000` and use the **Sign in** link, or hit a provider
   directly:
   - `http://localhost:9000/auth/google/redirect`
   - `http://localhost:9000/auth/microsoft/redirect`
   - `http://localhost:9000/auth/facebook/redirect`
3. A successful round-trip lands on **My Treasures** and creates a `users` row
   (`provider`, `provider_user_id`, `email`, `name`, `avatar_url`).

## Troubleshooting

| Symptom | Likely cause |
|---------|--------------|
| `redirect_uri_mismatch` (Google) / "URL blocked" (Facebook) | The callback URL in the console doesn't **exactly** match — check scheme, port `:9000`, trailing path. |
| Microsoft `AADSTS700016` / unauthorized client | Wrong client ID, or the secret used its *ID* instead of its *Value*. |
| `invalid_client` on callback | `*_CLIENT_SECRET` wrong or expired; regenerate and `config:clear`. |
| Works locally, fails in prod | Register the `https://geo.trackr.live/...` URI too, and set `APP_URL=https://geo.trackr.live` in the production `.env`. |
| Facebook returns no email | App still in Development mode or `email` permission not granted/reviewed. |

## Production `.env` reminders

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://geo.trackr.live
```

The `*_REDIRECT_URI` values derive from `APP_URL`, so they become the
`https://geo.trackr.live/...` callbacks automatically once `APP_URL` is set.
