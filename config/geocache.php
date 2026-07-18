<?php

/**
 * Tunable constants for the reverse-geocache game logic.
 *
 * All distances internally are computed in METERS; imperial values here are
 * converted on load so the whole app has one source of truth. Values are
 * overridable via env so the unlock range can be boosted in the field without
 * a code change (spec §7 TEST-7).
 */

$ftToM = fn (float $ft): float => $ft * 0.3048;

return [

    // ---- Unlock threshold -------------------------------------------------
    // Base unlock radius. Defaults to 10 ft but can be raised (e.g. 20-30 ft)
    // if real-world GPS accuracy makes 10 ft unreachable.
    'base_unlock_ft' => (float) env('GEOCACHE_BASE_UNLOCK_FT', 10),

    // How much of the player's reported GPS accuracy we forgive, capped, so
    // honest players are not blocked by GPS noise. Effective unlock condition:
    //   distance_m - min(player_accuracy_m, accuracy_cap_m) < base_unlock_m
    'accuracy_cap_ft' => (float) env('GEOCACHE_ACCURACY_CAP_FT', 25),

    // ---- Creation accuracy gate ------------------------------------------
    // Warn + require confirmation if the creator's GPS accuracy is worse than
    // this (meters). A treasure placed with poor accuracy is unfair to solve.
    'create_accuracy_warn_m' => (float) env('GEOCACHE_CREATE_WARN_M', 20),

    // ---- Distance display bands ------------------------------------------
    // Ordered by ascending max_ft. The first band whose max_ft the true
    // distance falls under is used. `round_ft` (or `round_mi`) controls the
    // granularity shown; `label` overrides a numeric readout entirely.
    // Bands intentionally coarsen with distance to resist trilateration while
    // keeping the game winnable (spec §7).
    'bands' => [
        ['max_ft' => 50,      'label' => 'Under 50 ft — very close!'],
        ['max_ft' => 150,     'round_ft' => 25],
        ['max_ft' => 1000,    'round_ft' => 100],
        ['max_ft' => 5280,    'round_mi' => 0.1],
        ['max_ft' => INF,     'round_mi' => 1.0],
    ],

    // ---- Rate limiting ----------------------------------------------------
    // Applied to the distance-test endpoint. Keys are per (code + ip_hash).
    'test_rate' => [
        'min_seconds_between' => (int) env('GEOCACHE_TEST_MIN_SECONDS', 3),
        'per_minute'          => (int) env('GEOCACHE_TEST_PER_MINUTE', 15),
        'per_day'             => (int) env('GEOCACHE_TEST_PER_DAY', 500),
    ],

    // ---- Image upload -----------------------------------------------------
    'image' => [
        // Post-processing cap (bytes). Kept comfortably under MySQL
        // max_allowed_packet and the MEDIUMBLOB ceiling. 4 MB.
        'max_bytes'    => (int) env('GEOCACHE_IMAGE_MAX_BYTES', 4 * 1024 * 1024),
        'allowed_mime' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        // Longest edge to re-encode down to, stripping EXIF (incl. GPS) in the process.
        'max_dimension' => (int) env('GEOCACHE_IMAGE_MAX_DIM', 2048),
    ],

    // ---- Code generation --------------------------------------------------
    'code' => [
        'length'   => 8,
        // Unambiguous alphabet: no 0/O, 1/I/L. 30 symbols.
        'alphabet' => 'ABCDEFGHJKMNPQRSTUVWXYZ23456789',
    ],

    // ---- Derived (meters) — do not set directly ---------------------------
    'base_unlock_m'   => $ftToM((float) env('GEOCACHE_BASE_UNLOCK_FT', 10)),
    'accuracy_cap_m'  => $ftToM((float) env('GEOCACHE_ACCURACY_CAP_FT', 25)),
];
