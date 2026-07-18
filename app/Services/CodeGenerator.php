<?php

namespace App\Services;

use App\Models\Treasure;
use RuntimeException;

/**
 * Generates unique, unambiguous, cryptographically-random treasure codes.
 */
class CodeGenerator
{
    /**
     * Return a code not currently used by any treasure.
     */
    public function unique(): string
    {
        // Practically never collides given 30^8 space, but loop to be safe.
        for ($i = 0; $i < 10; $i++) {
            $code = $this->random();
            if (! Treasure::where('code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Unable to generate a unique treasure code.');
    }

    public function random(): string
    {
        $alphabet = (string) config('geocache.code.alphabet');
        $length = (int) config('geocache.code.length');
        $max = strlen($alphabet) - 1;

        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, $max)];
        }

        return $code;
    }

    /**
     * Normalize user-entered codes for lookup (case-insensitive input).
     */
    public function normalize(string $input): string
    {
        return strtoupper(trim($input));
    }
}
