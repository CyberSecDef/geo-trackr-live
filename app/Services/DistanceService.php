<?php

namespace App\Services;

use App\Models\Treasure;

/**
 * Core reverse-geocache math. Lives server-side so treasure coordinates never
 * reach a client and so the unlock decision cannot be tampered with.
 */
class DistanceService
{
    private const EARTH_RADIUS_M = 6_371_000.0;
    private const M_TO_FT = 3.280839895;

    /**
     * Great-circle distance in meters between two lat/lng points (Haversine).
     */
    public function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return self::EARTH_RADIUS_M * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Whether the player is close enough to unlock, blending in a capped portion
     * of their reported GPS accuracy so noise doesn't block honest players.
     *
     * @param float      $distanceM    true distance to the treasure (m)
     * @param float|null $accuracyM    player's reported GPS accuracy (m), if any
     */
    public function isUnlocked(float $distanceM, ?float $accuracyM): bool
    {
        $forgiven = min($accuracyM ?? 0.0, (float) config('geocache.accuracy_cap_m'));

        return ($distanceM - $forgiven) < (float) config('geocache.base_unlock_m');
    }

    /**
     * Reduce a true distance to the coarse, display-only band from config.
     * Returns a human string only — never a raw number the client could
     * trilaterate against. The band boundaries are identical regardless of
     * unit (they carry the anti-trilateration property); only the rendered
     * number is converted, so the metric toggle is display-only.
     *
     * @param  'imperial'|'metric'  $unit
     * @return array{label:string, band_index:int}
     */
    public function band(float $distanceM, string $unit = 'imperial'): array
    {
        $ft = $distanceM * self::M_TO_FT;

        foreach (config('geocache.bands') as $i => $band) {
            if ($ft < $band['max_ft']) {
                return [
                    'label' => $this->formatBand($ft, $band, $unit),
                    'band_index' => $i,
                ];
            }
        }

        // Fallback (should be unreachable given the INF final band).
        return ['label' => 'Very far away', 'band_index' => -1];
    }

    /**
     * @param  array{max_ft:float, label?:string, round_ft?:float, round_mi?:float}  $band
     * @param  'imperial'|'metric'  $unit
     */
    private function formatBand(float $ft, array $band, string $unit): string
    {
        // A fixed "very close" band uses its own copy regardless of unit.
        if (isset($band['label'])) {
            return $band['label'];
        }

        $metric = $unit === 'metric';

        if (isset($band['round_mi'])) {
            if ($metric) {
                $km = ($ft / 5280) * 1.609344;
                $stepKm = $band['round_mi'] * 1.609344;
                $rounded = max(round($km / $stepKm) * $stepKm, $stepKm);

                return 'About '.number_format($rounded, $stepKm < 1 ? 1 : 0).' km away';
            }

            $mi = $ft / 5280;
            $rounded = max(round($mi / $band['round_mi']) * $band['round_mi'], $band['round_mi']);

            return 'About '.number_format($rounded, $band['round_mi'] < 1 ? 1 : 0).' mi away';
        }

        $stepFt = $band['round_ft'] ?? 10;

        if ($metric) {
            $m = $ft * 0.3048;
            $stepM = max(round($stepFt * 0.3048 / 5) * 5, 5); // round step to a tidy 5 m
            $rounded = max(round($m / $stepM) * $stepM, $stepM);

            return 'About '.number_format($rounded).' m away';
        }

        $rounded = max(round($ft / $stepFt) * $stepFt, $stepFt);

        return 'About '.number_format($rounded).' ft away';
    }

    /**
     * Full evaluation of a player's test against a treasure.
     *
     * @param  'imperial'|'metric'  $unit
     * @return array{unlocked:bool, label:string, distance_m:float, band_index:int}
     */
    public function evaluate(Treasure $treasure, float $playerLat, float $playerLng, ?float $accuracyM, string $unit = 'imperial'): array
    {
        $distanceM = $this->haversineMeters(
            $treasure->latitude,
            $treasure->longitude,
            $playerLat,
            $playerLng,
        );

        $unlocked = $this->isUnlocked($distanceM, $accuracyM);
        $band = $this->band($distanceM, $unit);

        return [
            'unlocked' => $unlocked,
            'label' => $unlocked ? 'Unlocked!' : $band['label'],
            'distance_m' => $distanceM,
            'band_index' => $band['band_index'],
        ];
    }
}
