# GpsPuzzle — Reverse Geocache

A location-based reverse geocache. A creator hides a treasure (message + optional
image) at their physical GPS location and gets an 8-character code. Anyone with the
code can "test" it from anywhere — the app tells them **only the distance** to the
treasure. Players move, test again, and zero in until they're within ~10 feet, at
which point the treasure unlocks and reveals its message and image.

See [`requirements_spec.md`](requirements_spec.md) for the full specification.

## Stack

- **Laravel 13** + **Livewire 4** + **Alpine.js** (Blade, no separate SPA build)
- **Tailwind CSS v4** via Vite
- **MySQL** (images stored as `MEDIUMBLOB`)
- **Laravel Socialite** — Google, Microsoft, Facebook (Apple deferred)

## Architecture at a glance

| Concern | Where |
|---------|-------|
| Game math (Haversine, unlock decision, distance banding) | `app/Services/DistanceService.php` |
| Unique code generation | `app/Services/CodeGenerator.php` |
| Image re-encode / EXIF strip / resize / cap | `app/Services/ImageProcessor.php` |
| Test rate limiting (per code + hashed IP) | `app/Support/RateLimit.php` |
| Per-session unlock tracking | `app/Support/UnlockSession.php` |
| Test / unlock screen | `app/Livewire/TestTreasure.php` |
| Create screen | `app/Livewire/CreateTreasure.php` |
| Manage own treasures | `app/Livewire/MyTreasures.php` |
| Social login | `app/Http/Controllers/Auth/SocialAuthController.php` |
| Gated image streaming | `app/Http/Controllers/TreasureImageController.php` |
| **All tunables** (unlock range, bands, rate limits, image caps) | `config/geocache.php` |

Treasure coordinates are computed **server-side only** and are never sent to a
client. The distance shown is coarsened into bands to resist trilateration, and the
unlock threshold blends in a capped portion of the player's GPS accuracy so noise
doesn't block honest players. Both are configurable — you can raise the unlock range
in the field with `GEOCACHE_BASE_UNLOCK_FT` without a code change.

## Local setup

```bash
composer install
npm install
# .env is already present; edit DB + OAuth creds (or copy from .env.example)
php artisan key:generate

# Point DB_* at a MySQL database, then:
php artisan migrate

npm run build             # or: npm run dev
php artisan serve
```

Requires a MySQL database (`DB_DATABASE=gpspuzzle` by default). Sessions and cache
use the filesystem and the queue runs synchronously, so no Redis or queue worker is
needed. HTTPS is required in any non-localhost environment because the browser
Geolocation API only works on secure origins.

### OAuth credentials

Create OAuth apps and fill these in `.env` (redirect URIs must match exactly):

- **Google** — `GOOGLE_CLIENT_ID/SECRET`, redirect `/auth/google/callback`
- **Microsoft** — `MICROSOFT_CLIENT_ID/SECRET`, redirect `/auth/microsoft/callback`
- **Facebook** — `FACEBOOK_CLIENT_ID/SECRET`, redirect `/auth/facebook/callback`

## Tests

```bash
php artisan test
```

`DistanceServiceTest` validates the core game math (Haversine, unlock blending,
band coarsening, metric toggle) and needs no database.

## Deploying to DreamHost (shared hosting)

- Point the domain's web root at `public/`.
- Provision a MySQL database; set `DB_*` in `.env`; run `php artisan migrate`.
- Enable HTTPS (Let's Encrypt) — required for geolocation.
- No queue worker/daemon is needed (sync queue, file cache/sessions). Image
  processing runs synchronously on upload.
- Confirm the server PHP version satisfies Laravel 13, and that MySQL
  `max_allowed_packet` comfortably exceeds `GEOCACHE_IMAGE_MAX_BYTES` (4 MB) so
  BLOB writes succeed.
- Run `php artisan config:cache route:cache view:cache` for production.

## Not yet built (this is a scaffold)

The domain model, routes, game logic, screens, auth, and image pipeline are all in
place and boot cleanly. Still to do before it's production-ready:

- OAuth app credentials + end-to-end login testing with each provider.
- Field playtest of the unlock threshold and distance bands.
- Feature/integration tests that exercise the DB (need a MySQL test database).
- Polish: loading/empty states, error copy, accessibility pass.
