<?php

namespace Tests\Feature;

use App\Livewire\TestTreasure;
use App\Models\Treasure;
use App\Models\TreasureImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TreasureTestFlowTest extends TestCase
{
    use RefreshDatabase;

    private function treasureAt(float $lat, float $lng, array $attrs = []): Treasure
    {
        return Treasure::factory()->create(array_merge([
            'latitude' => $lat,
            'longitude' => $lng,
        ], $attrs));
    }

    public function test_player_within_range_unlocks_and_sees_message(): void
    {
        $t = $this->treasureAt(40.0, -75.0, ['message' => 'You found the treasure!']);

        Livewire::test(TestTreasure::class)
            ->set('code', $t->code)
            ->call('test', 40.0000135, -75.0, 3.0) // ~1.5 m north
            ->assertSet('unlocked', true)
            ->assertSet('revealMessage', 'You found the treasure!');

        $this->assertDatabaseHas('unlocks', ['treasure_id' => $t->id]);
        $this->assertSame(1, $t->fresh()->unlock_count);
    }

    public function test_player_far_away_gets_only_a_banded_distance(): void
    {
        $t = $this->treasureAt(40.0, -75.0);

        $component = Livewire::test(TestTreasure::class)
            ->set('code', $t->code)
            ->call('test', 40.01, -75.0, 8.0) // ~1.1 km away
            ->assertSet('unlocked', false);

        $label = $component->get('result');
        $this->assertNotNull($label);
        // Never reveals an exact distance — only a coarse band.
        $this->assertMatchesRegularExpression('/away$/', $label);
        $this->assertDatabaseCount('unlocks', 0);
    }

    public function test_unknown_code_reports_not_found(): void
    {
        Livewire::test(TestTreasure::class)
            ->set('code', 'ZZZZ2345')
            ->call('test', 40.0, -75.0, 5.0)
            ->assertSet('notFound', true)
            ->assertSet('unlocked', false);
    }

    public function test_paused_treasure_is_unavailable(): void
    {
        $t = $this->treasureAt(40.0, -75.0, ['status' => 'paused']);

        Livewire::test(TestTreasure::class)
            ->set('code', $t->code)
            ->call('test', 40.0000135, -75.0, 3.0) // physically on top of it
            ->assertSet('paused', true)
            ->assertSet('unlocked', false);
    }

    public function test_metric_toggle_changes_units_in_result(): void
    {
        $t = $this->treasureAt(40.0, -75.0);

        $label = Livewire::test(TestTreasure::class)
            ->set('code', $t->code)
            ->set('unit', 'metric')
            ->call('test', 40.01, -75.0, 8.0)
            ->get('result');

        $this->assertStringContainsString('m away', $label);
    }

    public function test_image_is_gated_behind_unlock(): void
    {
        $t = $this->treasureAt(40.0, -75.0);
        TreasureImage::create([
            'treasure_id' => $t->id,
            'mime_type' => 'image/jpeg',
            'byte_size' => 3,
            'data' => 'abc',
        ]);

        // Guest with no unlock in session is forbidden.
        $this->get(route('treasure.image', $t))->assertForbidden();

        // A session that has unlocked it may view it.
        $this->withSession(['unlocked_treasures' => [$t->id => true]])
            ->get(route('treasure.image', $t))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        // The owner may always view it.
        $this->actingAs($t->creator)
            ->get(route('treasure.image', $t))
            ->assertOk();
    }
}
