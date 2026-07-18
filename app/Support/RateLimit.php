<?php

namespace App\Support;

use App\Models\TestAttempt;
use App\Models\Treasure;

/**
 * Rate limiting for the distance-test endpoint, keyed per (treasure + hashed IP).
 * Enforces a minimum gap between tests plus per-minute and per-day ceilings, to
 * blunt scripted trilateration/enumeration (spec §11 CHEAT-2).
 */
class RateLimit
{
    public static function allowTest(Treasure $treasure): bool
    {
        $hash = self::ipHash();
        $cfg = config('geocache.test_rate');

        $recent = TestAttempt::where('treasure_id', $treasure->id)
            ->where('ip_hash', $hash);

        // Minimum seconds between tests.
        $last = (clone $recent)->max('attempted_at');
        if ($last && now()->diffInSeconds($last, absolute: true) < $cfg['min_seconds_between']) {
            return false;
        }

        // Per-minute ceiling.
        $perMinute = (clone $recent)->where('attempted_at', '>=', now()->subMinute())->count();
        if ($perMinute >= $cfg['per_minute']) {
            return false;
        }

        // Per-day ceiling.
        $perDay = (clone $recent)->where('attempted_at', '>=', now()->subDay())->count();
        if ($perDay >= $cfg['per_day']) {
            return false;
        }

        return true;
    }

    public static function recordAttempt(Treasure $treasure): void
    {
        TestAttempt::create([
            'treasure_id' => $treasure->id,
            'ip_hash' => self::ipHash(),
            'attempted_at' => now(),
        ]);
    }

    /**
     * SHA-256 of (ip + app key) so we never persist a raw IP (PRIV-4).
     */
    private static function ipHash(): string
    {
        return hash('sha256', request()->ip().'|'.config('app.key'));
    }
}
