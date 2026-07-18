# Reverse Geocache — Requirements Specification

**Project:** GpsPuzzle
**Type:** Single-page-feeling web application (Laravel + Livewire)
**Status:** Draft v1.0
**Last updated:** 2026-07-17

---

## 1. Overview

GpsPuzzle is a **reverse geocache** web app. A reverse geocache flips the
traditional geocache: instead of being given coordinates and hunting for a
container, the player is given a *code only* and must physically move around the
world, repeatedly checking their distance to a hidden point, until they close in
on it. When they get within **10 feet** of the target, the treasure "unlocks"
and reveals a hidden message (and optional image).

### Core loop

1. An **authenticated creator** physically travels to a location.
2. At that spot they create a **treasure**: a secret message + optional image.
   The app captures their current GPS coordinates as the treasure location.
3. The treasure is assigned a **random 8-character code**.
4. The creator shares that code (or a link containing it) with anyone.
5. A **player** (no login required) enters the code and "tests" it. The app
   returns only the **distance** from the player's current location to the
   treasure — never a direction or bearing.
6. The player moves, tests again, and repeats — triangulating by trial and error.
7. When the player is **< 10 feet** from the treasure, it unlocks and the
   message + image are revealed.

---

## 2. Goals & Non-Goals

### Goals
- Frictionless: creating takes a couple taps at the physical location; testing
  needs no account.
- Fair & fun: distance-only feedback preserves the "walk around to find it" game.
- Resistant to trivial cheating (scripted trilateration, GPS spoofing is out of
  scope to fully prevent, but we make casual cheating harder).
- Deployable on **DreamHost shared hosting** with **MySQL**.

### Non-Goals (v1)
- Native mobile apps (web only; mobile browser is the primary target).
- Real-time multiplayer / live leaderboards.
- Full defense against determined GPS spoofing (client GPS is inherently
  trust-on-faith).
- Social features (comments, following, sharing feeds).

---

## 3. Roles

| Role | Auth required | Capabilities |
|------|---------------|--------------|
| **Guest / Player** | No | Enter a code, test distance, unlock & view revealed treasure. |
| **Creator** | Yes (social login) | Everything a Player can do, plus create/manage their own treasures. |
| **Admin** (optional, v1.1) | Yes | Moderate/remove treasures, view abuse reports. |

Anyone with a code can test it, logged in or not. Only creating a treasure
requires authentication.

---

## 4. Authentication

Social login only — no local password accounts. Implemented with **Laravel
Socialite** (+ community providers where needed).

**v1 ships with Google, Microsoft, and Facebook.** Apple Sign In is **deferred**
(requires a paid Apple Developer account) and will be added later; the provider
abstraction below is built so Apple can be dropped in without rework.

| Provider | Status | Socialite package | Notes |
|----------|--------|-------------------|-------|
| Google | **v1** | built-in | Standard OAuth client. |
| Facebook | **v1** | built-in | Requires app review for `email` scope in production. |
| Microsoft | **v1** | `socialiteproviders/microsoft` | Azure AD / MSA. |
| Apple | deferred | `socialiteproviders/apple` | Requires a paid Apple Developer account. Client secret is a signed JWT (from a `.p8` private key) regenerated periodically (max 6-month expiry). Add post-v1. |

### Requirements
- **AUTH-1** Users can sign in with Google, Apple, Microsoft, or Facebook.
- **AUTH-2** On first login, create a `users` record keyed by provider + provider
  user id; store email, display name, avatar URL if available.
- **AUTH-3** If the same email arrives from a different provider, link to the
  existing user (email-verified providers only) OR create a distinct account —
  **decision: link on verified email match**, to avoid duplicate accounts.
- **AUTH-4** Sessions via Laravel's standard session guard (cookie-based).
- **AUTH-5** Logout clears the session.
- **AUTH-6** No email/password registration form is exposed.

---

## 5. Treasure Creation

### Flow
1. Creator opens "Create Treasure" (must be authenticated).
2. Browser requests geolocation permission; app reads current position via the
   **HTML5 Geolocation API** (`navigator.geolocation.getCurrentPosition`) with
   `enableHighAccuracy: true`.
3. App displays the captured accuracy (± meters) and current coords (coords are
   captured but **never shown again** to players).
4. Creator enters a **message** (required) and optionally attaches **one image**.
5. Creator submits → treasure saved, 8-char code generated and shown with a
   shareable link.

### Requirements
- **CREATE-1** Only authenticated users can create treasures.
- **CREATE-2** The treasure location = the creator's current GPS coordinates at
  submit time (`latitude`, `longitude` stored as `DECIMAL(10,7)` / `DECIMAL(11,7)`).
- **CREATE-3** Capture and store the reported GPS **accuracy** (meters) at
  creation time for later quality/anti-cheat use.
- **CREATE-4** **Accuracy gate:** if reported accuracy is worse than **20 meters**,
  warn the creator and require explicit confirmation before saving (a treasure
  placed with poor accuracy is unfair to solve).
- **CREATE-5** Message: required, plain text, max **1,000 characters**. Rendered
  as escaped text (no HTML injection).
- **CREATE-6** Image: optional, single file. Allowed types: JPEG, PNG, WebP, GIF.
  Max upload **4 MB** (see §9 storage). Server re-encodes/strips EXIF (including
  any embedded GPS EXIF, which would leak the location) before storing.
- **CREATE-7** Each treasure receives a unique 8-character code (see §6).
- **CREATE-8** A creator may create multiple treasures.
- **CREATE-9** Creators can view a list of their own treasures with code, created
  date, and number of unlocks, and can **delete** their own treasures. Deleting a
  treasure removes it, its stored image BLOB, and invalidates its code.
- **CREATE-9a** Treasures **never expire**. They persist indefinitely until the
  creator deletes them (or pauses them via CREATE-10).
- **CREATE-10** Optional per-treasure toggle: **active/paused** (paused codes
  return "this treasure is not currently available" on test).

---

## 6. Treasure Codes

- **CODE-1** Format: **8 characters**, uppercase, drawn from an
  **unambiguous alphabet** excluding easily confused characters
  (`0/O`, `1/I/L`). Proposed alphabet: `ABCDEFGHJKMNPQRSTUVWXYZ23456789`
  (30 symbols → 30^8 ≈ 6.5×10^11 combinations).
- **CODE-2** Codes are generated with a cryptographically secure RNG and checked
  for uniqueness against existing codes (retry on collision).
- **CODE-3** Codes are case-insensitive on input (normalized to uppercase).
- **CODE-4** A code maps to exactly one treasure and never changes.
- **CODE-5** Shareable link format: `https://<host>/t/{CODE}` — opening it
  pre-fills the code on the test screen.

---

## 7. Testing (Distance Check) — the Player Experience

### Flow
1. Player opens the app and enters a code (or arrives via `/t/{CODE}`).
2. App requests geolocation permission and reads current position.
3. App computes great-circle distance between player and treasure.
4. App returns a **rounded/banded distance only** — no bearing, no direction,
   no coordinates.
5. Player physically moves and taps "Test again" to repeat.
6. When within the unlock threshold, the treasure unlocks (see §8).

### Distance calculation
- **TEST-1** Distance computed server-side using the **Haversine formula**
  (Earth radius 6,371,000 m). Client never receives the treasure coordinates.
- **TEST-2** Player coordinates are sent to the server per test; they are used
  for the calculation and **not persisted** beyond aggregate/abuse logging
  (see §10 privacy).

### Distance display (banding) — anti-trilateration + GPS-noise friendly
Distance is bucketed so exact-value trilateration is impractical while the game
stays winnable. Proposed bands (feet):

| True distance | Shown as |
|---------------|----------|
| < 10 ft | **UNLOCKED** (reveal) |
| 10 – 50 ft | "Under 50 ft — very close!" |
| 50 – 150 ft | rounded to nearest 25 ft |
| 150 – 1,000 ft | rounded to nearest 100 ft |
| 1,000 ft – 1 mi | rounded to nearest 0.1 mi |
| > 1 mi | rounded to nearest mile |

- **TEST-3** Show distance only, using the bands above (final band values may be
  tuned during playtesting; they are config-driven, not hard-coded).
- **TEST-4** Never expose bearing, heading, direction, min/max, or raw coords.
- **TEST-5** Units: **imperial (feet/miles)** as the default, with a **metric
  toggle (feet↔meters, miles↔km) included in v1**. The toggle affects display
  only; all internal math and the unlock threshold are computed in meters. The
  chosen unit is remembered per browser (localStorage) and applies across the
  test, reveal, and create screens.
- **TEST-6** Display the player's own GPS accuracy so they understand noise
  ("your location is accurate to ±X ft").

### GPS accuracy reality check
Consumer phone GPS is typically accurate to ~16–50 ft (5–15 m), sometimes worse
in cities. The 10 ft unlock threshold is therefore **tight**. Mitigations:
- **TEST-7** Unlock uses an **effective threshold** = `base_unlock_ft + a portion
  of the player's reported accuracy`, capped, so honest players aren't blocked by
  GPS noise. **Decision:** unlock if
  `distance - min(player_accuracy_ft, accuracy_cap_ft) < base_unlock_ft`.
  - `base_unlock_ft` defaults to **10 ft** but is **config-driven and may be
    raised** (e.g. to 20–30 ft) if playtesting shows real-world GPS makes 10 ft
    unreachable. The creator-facing copy says "about 10 feet," so bumping the
    constant needs no product change.
  - `accuracy_cap_ft` defaults to **25 ft**.
  All three values live in config, not hard-coded, so the range can be boosted
  without a code change.

---

## 8. Unlock & Reveal

- **UNLOCK-1** When a test satisfies the unlock condition (§7 TEST-7), the
  treasure is revealed: full message + image (if any).
- **UNLOCK-2** Record an **unlock event** (code, timestamp, and — if the tester
  is authenticated — the user id; otherwise anonymous) for the creator's unlock
  count. Store approximate location only if privacy policy permits (see §10);
  default is to store no player coordinates.
- **UNLOCK-3** Once unlocked in a session, the reveal remains viewable to that
  player (e.g., an unlock token/flag) so they don't have to re-satisfy the
  distance check to re-read it during the same session.
- **UNLOCK-4** Unlock does **not** consume or disable the treasure — others can
  still find it (unless the creator paused/deleted it).
- **UNLOCK-5** The reveal screen may show creator display name (optional) and
  "found on" timestamp.

---

## 9. Data Model

### `users`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| provider | varchar | google / apple / microsoft / facebook |
| provider_user_id | varchar | unique per provider |
| email | varchar nullable | |
| name | varchar nullable | |
| avatar_url | varchar nullable | |
| created_at / updated_at | timestamps | |

Unique index on (`provider`, `provider_user_id`); index on `email`.

### `treasures`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| user_id | bigint FK → users | creator |
| code | char(8) | unique, uppercase |
| message | varchar(1000) | secret text |
| latitude | decimal(10,7) | treasure location |
| longitude | decimal(11,7) | treasure location |
| created_accuracy_m | float nullable | GPS accuracy at creation |
| status | enum(active,paused) | default active |
| unlock_count | int | denormalized counter |
| created_at / updated_at | timestamps | |

Unique index on `code`. Coordinates are **never** sent to clients except
implicitly by unlocking.

### `treasure_images` (BLOB storage — chosen approach)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| treasure_id | bigint FK → treasures | one image per treasure (v1) |
| mime_type | varchar | image/jpeg etc. |
| byte_size | int | |
| data | MEDIUMBLOB | up to 16 MB; app caps uploads at 4 MB |

- **DATA-1** Images stored as **BLOB in MySQL** per the hosting decision.
  Use `MEDIUMBLOB`. Enforce a 4 MB post-processing cap so rows stay reasonable.
- **DATA-2** Because MySQL `max_allowed_packet` on shared hosting may be modest,
  verify DreamHost's value; keep the effective image cap comfortably under it.
- **DATA-3** **Fallback (only if BLOBs prove problematic on DreamHost):** store
  files under `./var/treasures/` on the filesystem and keep only a path in the
  DB. BLOB-in-DB is the default.

### `unlocks`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| treasure_id | bigint FK | |
| user_id | bigint FK nullable | null = anonymous player |
| unlocked_at | timestamp | |

### `test_attempts` (rate-limiting / abuse — minimal, privacy-preserving)
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| treasure_id | bigint FK | |
| ip_hash | char(64) nullable | hashed IP for rate limiting, not raw |
| attempted_at | timestamp | |

No player coordinates stored by default (see §10).

---

## 10. Privacy

- **PRIV-1** Player coordinates submitted during a test are used only to compute
  distance and are **not persisted** by default.
- **PRIV-2** Treasure coordinates are secret; never sent to any client and never
  logged in application logs.
- **PRIV-3** Uploaded images are **stripped of EXIF metadata** (especially GPS
  EXIF) on upload — otherwise a photo could leak the treasure's real coordinates.
- **PRIV-4** IP addresses used for rate limiting are stored **hashed** (e.g.
  SHA-256 with an app secret salt), not raw.
- **PRIV-5** A short privacy notice explains geolocation usage before/at first
  location prompt.

---

## 11. Anti-Cheat & Abuse Controls

- **CHEAT-1** Distance banding (§7) makes precise multilateration hard.
- **CHEAT-2** **Rate limiting** on the test endpoint:
  - Per code + IP: e.g. **max ~1 test / 3 seconds**, burst-limited, and a
    daily ceiling (config-driven).
  - Global per-IP throttle to blunt scripted enumeration.
- **CHEAT-3** Code space is large and non-sequential (random), so codes can't be
  guessed/enumerated cheaply; enumeration attempts are throttled and can be
  flagged.
- **CHEAT-4** Server-side distance computation only (client can't derive coords
  from client code).
- **CHEAT-5** GPS spoofing (fake location) is acknowledged as **not fully
  preventable** in a browser; out of scope to defeat in v1. Optionally record the
  Geolocation API `accuracy` and flag implausibly perfect/teleporting sequences
  for later review (v1.1).
- **CHEAT-6** Upload validation: MIME sniffing, re-encode images server-side,
  reject non-images.

---

## 12. Technical Stack

| Layer | Choice |
|-------|--------|
| Framework | **Laravel** (latest LTS-compatible with DreamHost PHP) |
| UI | **Livewire + Alpine.js** (Blade components; no separate SPA build) |
| Auth | Laravel **Socialite** + `socialiteproviders/microsoft`, `socialiteproviders/apple` |
| DB | **MySQL** |
| Image handling | Intervention Image (or GD) for re-encode/EXIF strip; stored as MEDIUMBLOB |
| Geolocation | Browser HTML5 Geolocation API (client), Haversine (server) |
| Assets | Vite (Laravel default) for Alpine/CSS bundle |
| CSS | Tailwind (Laravel/Livewire default) |

### Key routes (indicative)
| Method | Path | Purpose | Auth |
|--------|------|---------|------|
| GET | `/` | Landing / enter code | public |
| GET | `/auth/{provider}/redirect` | Start social login | public |
| GET | `/auth/{provider}/callback` | OAuth callback | public |
| POST | `/logout` | Log out | auth |
| GET | `/treasures` | Creator's treasure list | auth |
| GET/POST | `/treasures/create` | Create treasure (Livewire) | auth |
| DELETE | `/treasures/{id}` | Delete own treasure | auth (owner) |
| GET | `/t/{code}` | Test screen (prefilled) | public |
| POST | `/test` | Submit code + coords → banded distance / unlock | public (rate-limited) |
| GET | `/image/{treasureId}` | Serve BLOB image (only if unlocked/owner) | conditional |

- **API-1** `/image/{id}` streams the BLOB with correct content-type and caching
  headers, and only serves the image to a session that has unlocked the treasure
  or to the owner (so the image isn't fetchable without solving).

---

## 13. UI / Screens

1. **Landing** — brief explainer + "Enter a code" field + "Sign in to create".
2. **Test screen** — code (prefilled if via link), big "Test" button, last
   result as a banded distance with an accuracy note, "Test again". Clear
   instruction: *only distance is shown; move and test again to zero in.*
3. **Unlock/Reveal** — the message and image, celebratory state.
4. **Create screen** (auth) — "Capture my location" (shows accuracy), message
   field, image picker, submit → shows code + copyable share link.
5. **My Treasures** (auth) — list with code, share link, unlock count, pause,
   delete.
6. **Auth screens** — provider buttons; error states.

- **UI-1** Mobile-first responsive design (primary device is a phone in the field).
- **UI-2** Graceful handling of denied/unavailable geolocation with clear guidance.
- **UI-3** Loading/spinner states while acquiring GPS (can take several seconds).

---

## 14. Deployment (DreamHost)

- **DEP-1** Target: **DreamHost shared hosting** with MySQL.
- **DEP-2** Constraints to design around:
  - No guaranteed long-running processes → **avoid queue workers**; do image
    processing **synchronously** on request (uploads are small and infrequent).
    If any async work is added later, use `queue:work` invoked via **cron**, not
    a daemon.
  - Confirm the available **PHP version** meets the chosen Laravel version's
    minimum before finalizing the Laravel version.
  - Check MySQL **`max_allowed_packet`** to confirm 4 MB BLOB writes succeed;
    adjust the image cap if needed.
  - HTTPS is required (Geolocation API only works on secure origins). Ensure a
    valid TLS cert for the domain (Let's Encrypt via DreamHost).
- **DEP-3** Document env config for each OAuth provider (client id/secret,
  redirect URIs) and Apple's `.p8` key + client-secret JWT generation.
- **DEP-4** `php artisan migrate` run during deploy; `storage`/cache dirs
  writable; `APP_KEY` set.

---

## 15. Non-Functional Requirements

- **NFR-1** Secure origin (HTTPS) mandatory.
- **NFR-2** All user input escaped/validated; CSRF protection on state-changing
  requests (Laravel default).
- **NFR-3** Rate limiting on test and auth endpoints.
- **NFR-4** Distance math accurate enough that unlock behaves consistently near
  the 10 ft boundary given accuracy blending.
- **NFR-5** Reasonable performance on shared hosting: test endpoint is a single
  indexed lookup + trig calc (cheap).
- **NFR-6** Accessibility: labeled controls, sufficient contrast, keyboard-usable
  forms.

---

## 16. Decisions Locked & Remaining Confirmations

### Locked
- **Providers:** v1 ships Google, Microsoft, Facebook. Apple deferred.
- **Unlock range:** base 10 ft, config-driven and boostable if GPS accuracy
  proves it necessary (TEST-7).
- **Metric toggle:** included in v1 (TEST-5).
- **Expiry:** none; creators delete their own treasures (CREATE-9 / CREATE-9a).
- **Admin/moderation:** deferred to post-v1.
- **Images:** single image per treasure in v1.

### Still to confirm before/during build
1. **Facebook app review** — production email scope needs Facebook app review;
   plan for the review lead time.
2. **DreamHost PHP version** — confirm exact version to pin the Laravel version.
3. **max_allowed_packet** on the DreamHost MySQL instance — confirm the 4 MB
   BLOB cap is safe.
4. **Unlock threshold playtest** — validate the base value / accuracy-blending in
   the field and adjust the config constant.

---

## 17. Future Enhancements (Post-v1)

- **Apple Sign In** (once a paid Apple Developer account is available).
- **Admin / moderation dashboard** and abuse reporting.
- Player accounts with a personal "found" history and stats.
- Leaderboards / fastest-find, fewest-tests.
- Hints purchasable at a "getting warmer/colder" trend level.
- Multiple images / richer media in reveal.
- Spoofing-detection heuristics (teleport/implausible-accuracy flags).
- Public directory of treasures (opt-in) by region.
