<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    private function fakeSocialiteUser(string $provider, string $id, ?string $email, string $name): void
    {
        $oauthUser = new SocialiteUser();
        $oauthUser->map([
            'id' => $id,
            'email' => $email,
            'name' => $name,
            'avatar' => 'https://example.com/a.png',
        ]);

        $driver = Mockery::mock('Laravel\Socialite\Contracts\Provider');
        $driver->shouldReceive('user')->andReturn($oauthUser);
        Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
    }

    public function test_callback_creates_user_and_logs_in(): void
    {
        $this->fakeSocialiteUser('google', 'g-123', 'alice@example.com', 'Alice');

        $this->get(route('auth.callback', 'google'))
            ->assertRedirect(route('treasures.index'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'provider' => 'google',
            'provider_user_id' => 'g-123',
            'email' => 'alice@example.com',
        ]);
    }

    public function test_second_provider_links_to_existing_email(): void
    {
        // Existing account created earlier via Google.
        $existing = User::factory()->create([
            'email' => 'bob@example.com',
            'provider' => 'google',
            'provider_user_id' => 'g-999',
        ]);

        // Same person signs in with Microsoft (same verified email).
        $this->fakeSocialiteUser('microsoft', 'ms-777', 'bob@example.com', 'Bob');

        $this->get(route('auth.callback', 'microsoft'))
            ->assertRedirect(route('treasures.index'));

        // No duplicate account: the existing row is re-pointed at Microsoft.
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'id' => $existing->id,
            'provider' => 'microsoft',
            'provider_user_id' => 'ms-777',
        ]);
    }

    public function test_returning_user_is_matched_by_provider_id(): void
    {
        $user = User::factory()->create([
            'provider' => 'google',
            'provider_user_id' => 'g-abc',
            'email' => 'carol@example.com',
        ]);

        $this->fakeSocialiteUser('google', 'g-abc', 'carol@example.com', 'Carol');

        $this->get(route('auth.callback', 'google'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
