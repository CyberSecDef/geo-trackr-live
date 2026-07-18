<?php

namespace Tests\Unit;

use App\Models\Treasure;
use App\Services\DistanceService;
use Tests\TestCase;

class DistanceServiceTest extends TestCase
{
    private DistanceService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new DistanceService();
    }

    public function test_haversine_matches_known_distance(): void
    {
        // ~111.19 km per degree of latitude at the equator meridian.
        $meters = $this->svc->haversineMeters(0.0, 0.0, 1.0, 0.0);
        $this->assertEqualsWithDelta(111_195, $meters, 200);
    }

    public function test_zero_distance_is_zero(): void
    {
        $this->assertEqualsWithDelta(0, $this->svc->haversineMeters(40.1, -75.2, 40.1, -75.2), 0.001);
    }

    public function test_unlock_within_base_threshold(): void
    {
        // 2 m away, no accuracy help needed (base is 10 ft ≈ 3.05 m).
        $this->assertTrue($this->svc->isUnlocked(2.0, null));
    }

    public function test_no_unlock_when_far(): void
    {
        $this->assertFalse($this->svc->isUnlocked(50.0, 5.0));
    }

    public function test_accuracy_blend_helps_but_is_capped(): void
    {
        // 30 m away with a claimed 100 m accuracy: forgiveness is capped (25 ft ≈ 7.62 m),
        // so 30 - 7.62 = 22.38 m is still well beyond the ~3.05 m base → no unlock.
        $this->assertFalse($this->svc->isUnlocked(30.0, 100.0));

        // 9 m away with 25 ft (7.62 m) forgiveness → 1.38 m < 3.05 m → unlock.
        $this->assertTrue($this->svc->isUnlocked(9.0, 100.0));
    }

    public function test_bands_coarsen_and_never_leak_exact_distance(): void
    {
        // Close band uses a fixed label.
        $near = $this->svc->band(9.0); // ~30 ft
        $this->assertStringContainsString('very close', strtolower($near['label']));

        // Far band rounds to miles.
        $far = $this->svc->band(3218.0); // ~2 mi
        $this->assertStringContainsString('mi away', $far['label']);
    }

    public function test_metric_toggle_changes_units_only(): void
    {
        $imperial = $this->svc->band(300.0, 'imperial');
        $metric = $this->svc->band(300.0, 'metric');

        $this->assertStringContainsString('ft away', $imperial['label']);
        $this->assertStringContainsString('m away', $metric['label']);
        // Same underlying band index → same anti-trilateration coarseness.
        $this->assertSame($imperial['band_index'], $metric['band_index']);
    }

    public function test_evaluate_hides_distance_and_reports_unlock(): void
    {
        $treasure = new Treasure(['latitude' => 40.000000, 'longitude' => -75.000000]);

        // A point ~1.5 m north (well within the base unlock radius).
        $eval = $this->svc->evaluate($treasure, 40.0000135, -75.000000, 3.0);

        $this->assertTrue($eval['unlocked']);
        $this->assertSame('Unlocked!', $eval['label']);
    }
}
