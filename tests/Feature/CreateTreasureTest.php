<?php

namespace Tests\Feature;

use App\Livewire\CreateTreasure;
use App\Models\Treasure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class CreateTreasureTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_creates_a_treasure_with_a_code(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(CreateTreasure::class)
            ->set('message', 'Behind the third oak tree.')
            ->call('setLocation', 40.123456, -75.654321, 6.0)
            ->call('save')
            ->assertHasNoErrors();

        $code = $component->get('createdCode');
        $this->assertNotNull($code);
        $this->assertSame(8, strlen($code));

        $this->assertDatabaseHas('treasures', [
            'user_id' => $user->id,
            'code' => $code,
            'message' => 'Behind the third oak tree.',
            'status' => 'active',
        ]);
    }

    public function test_creation_requires_a_captured_location(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(CreateTreasure::class)
            ->set('message', 'No location captured yet.')
            ->call('save')
            ->assertHasErrors('latitude');

        $this->assertDatabaseCount('treasures', 0);
    }

    public function test_poor_accuracy_requires_confirmation(): void
    {
        $user = User::factory()->create();

        // Accuracy worse than the configured warn threshold (default 20 m).
        Livewire::actingAs($user)
            ->test(CreateTreasure::class)
            ->set('message', 'Placed with a weak GPS fix.')
            ->call('setLocation', 40.0, -75.0, 80.0)
            ->call('save')
            ->assertHasErrors('accuracy');

        $this->assertDatabaseCount('treasures', 0);
    }

    public function test_optional_image_is_stored_as_a_blob(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(CreateTreasure::class)
            ->set('message', 'With a photo.')
            ->set('photo', UploadedFile::fake()->image('clue.jpg', 400, 300))
            ->call('setLocation', 40.0, -75.0, 5.0)
            ->call('save')
            ->assertHasNoErrors();

        $treasure = Treasure::where('code', $component->get('createdCode'))->firstOrFail();

        $this->assertDatabaseHas('treasure_images', ['treasure_id' => $treasure->id]);
        $this->assertGreaterThan(0, $treasure->image->byte_size);
        $this->assertContains($treasure->image->mime_type, ['image/jpeg', 'image/png']);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('treasures.create'))->assertRedirect(route('login'));
    }
}
