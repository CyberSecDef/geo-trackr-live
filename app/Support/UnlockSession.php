<?php

namespace App\Support;

use App\Models\Treasure;

/**
 * Tracks treasures the current (possibly anonymous) session has unlocked, so the
 * reveal persists for the session (UNLOCK-3) and the image route stays gated.
 */
class UnlockSession
{
    private const KEY = 'unlocked_treasures';

    public static function mark(Treasure $treasure): void
    {
        $ids = session()->get(self::KEY, []);
        $ids[$treasure->id] = true;
        session()->put(self::KEY, $ids);
    }

    public static function has(Treasure $treasure): bool
    {
        return (bool) (session()->get(self::KEY, [])[$treasure->id] ?? false);
    }
}
