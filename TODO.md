# Geo.Trackr.Live — TODO & Status

_Last updated: 2026-07-18_

**What it is:** a GPS treasure-hunt game ("Find the Signal"). A creator hides a
treasure (message + optional photo) at a physical GPS location and gets an
8‑character code. Anyone with the code tests their **distance only** (banded,
never exact) and the treasure unlocks when they're within ~10 ft.

- **Repo:** https://github.com/CyberSecDef/geo-trackr-live (public, `origin/main`)
- **Stack:** Laravel 13 · Livewire 4 · Alpine · Tailwind v4 · MySQL
- **Internal name:** GpsPuzzle · **Brand/domain:** Geo.Trackr.Live / geo.trackr.live
- **Spec:** [`requirements_spec.md`](requirements_spec.md) · **OAuth guide:** [`docs/oauth-setup.md`](docs/oauth-setup.md)

---

## ✅ Done

- Full scaffold: models, migrations, services (distance/Haversine, code gen,
  image processing), Livewire screens (test/unlock, create, my-treasures).
- Game logic: accuracy-blended unlock + anti-trilateration distance bands, all
  tunable in [`config/geocache.php`](config/geocache.php).
- Social auth via Socialite (Google, Microsoft, Facebook) with email
  account-linking. Login page + `login` route.
- Image pipeline: GD re-encode strips EXIF/GPS, resizes, caps size; stored as
  MySQL `MEDIUMBLOB`; gated image route (owner or unlocked session only).
- Test-endpoint rate limiting via salted IP hash.
- **22 passing tests** against MySQL (`gpspuzzle_test`).
- Dark/light mode toggle persisted to a `theme` cookie (no-flash).
- "Find the Signal" game-hero check-code screen (space theme, rotating globe,
  starfield, shooting stars, glowing headline).
- Footer non-affiliation disclaimer (independent of Geocaching.com / Groundspeak).
- Local dev DB (`gpspuzzle`) + test DB (`gpspuzzle_test`) on MySQL 8.4.
- Pushed to GitHub (2 commits: scaffold + visual/branding).

---

## 🔜 To Do

### 1. Make auth actually work (highest priority)
- [ ] Register OAuth apps and fill creds in `.env` — follow [`docs/oauth-setup.md`](docs/oauth-setup.md).
      Redirect URIs: dev `http://localhost:9000/auth/{provider}/callback`,
      prod `https://geo.trackr.live/auth/{provider}/callback`.
  - [ ] Google
  - [ ] Microsoft
  - [ ] Facebook (needs app review for `email` in Live mode — plan lead time)
- [ ] End-to-end login test with each provider → confirms a `users` row is created.

### 2. Playtest & tune
- [ ] Field-test the **10 ft unlock threshold** on real phones; adjust
      `GEOCACHE_BASE_UNLOCK_FT` / accuracy blend if GPS makes it unreachable.
- [ ] Sanity-check the distance **bands** feel right while walking in.

### 3. Deployment (DreamHost)
- [ ] Provision MySQL on DreamHost; set `DB_*`; run `php artisan migrate`.
- [ ] Point web root at `public/`; enable HTTPS (Let's Encrypt) — required for geolocation.
- [ ] Set prod `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://geo.trackr.live`.
- [ ] `php artisan config:cache route:cache view:cache`.
- [ ] Confirm PHP version satisfies Laravel 13 and MySQL `max_allowed_packet` > 4 MB.

### 4. Cleanup / hardening
- [ ] Gate the loopback `trustProxies` in `bootstrap/app.php` to `local` env
      (added for HTTPS phone testing; harmless but not needed in prod).
- [ ] Optional: scrub "reverse geocache" from `README.md` / `requirements_spec.md`
      to match the "Find the Signal" branding (UI already updated).
- [ ] Add DB-backed feature tests for edge cases as features grow.
- [ ] Accessibility pass (labels, contrast, keyboard) and loading/empty-state polish.

### 5. Nice-to-haves / future (post-v1, see spec §17)
- [ ] Apple Sign In (needs paid Apple Developer account).
- [ ] Admin/moderation dashboard.
- [ ] Player accounts with found-history / stats; leaderboards.
- [ ] Marketing: a Facebook launch post was drafted this session — refine & post when live.

---

## ▶️ Resume locally

```bash
cd ~/Git/GpsPuzzle

# App (browse at http://localhost:9000)
php artisan serve --host=0.0.0.0 --port=9000

# HTTPS for phone geolocation testing (self-signed proxy → https://192.168.0.10:9443)
# Cert + proxy script live in the session scratchpad: scratchpad/tls/
node scratchpad/tls/proxy.mjs      # adjust path to wherever you keep it

# Front-end assets
npm run build          # or: npm run dev

# Tests
php artisan test
```

- MySQL runs as a system service. Dev DB creds are in `.env` (git-ignored);
  test DB creds in `.env.testing` (git-ignored).
- Geolocation only works over `https://` or `localhost` — use the `:9443` proxy
  URL when testing from a phone.

---

## ❓ Open decisions
- Keep internal name **GpsPuzzle** (folder/db/namespace) or rename to match brand? (currently: keep)
- Facebook launch post: general-audience vs developer-focused tone.
