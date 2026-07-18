<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class SocialAuthController extends Controller
{
    /**
     * Redirect the user to the OAuth provider's consent screen.
     */
    public function redirect(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle the provider callback: find or create the user, then log in.
     */
    public function callback(string $provider)
    {
        try {
            $oauthUser = Socialite::driver($provider)->user();
        } catch (Throwable $e) {
            return redirect()->route('home')
                ->with('error', 'Sign-in failed or was cancelled. Please try again.');
        }

        $user = $this->findOrCreateUser($provider, $oauthUser);

        Auth::login($user, remember: true);

        return redirect()->route('treasures.index');
    }

    /**
     * Resolve a local user for an OAuth identity.
     *
     * Order: (1) exact (provider, provider_user_id) match; (2) link to an
     * existing account by verified email (AUTH-3); (3) create a new user.
     *
     * @param  \Laravel\Socialite\Contracts\User  $oauthUser
     */
    private function findOrCreateUser(string $provider, $oauthUser): User
    {
        $existing = User::where('provider', $provider)
            ->where('provider_user_id', $oauthUser->getId())
            ->first();

        if ($existing) {
            return $existing;
        }

        $email = $oauthUser->getEmail();

        if ($email && $linkable = User::where('email', $email)->first()) {
            // Link this provider to the pre-existing account (verified email match).
            $linkable->forceFill([
                'provider' => $provider,
                'provider_user_id' => $oauthUser->getId(),
                'avatar_url' => $linkable->avatar_url ?: $oauthUser->getAvatar(),
                'name' => $linkable->name ?: $oauthUser->getName(),
            ])->save();

            return $linkable;
        }

        return User::create([
            'provider' => $provider,
            'provider_user_id' => $oauthUser->getId(),
            'email' => $email,
            'name' => $oauthUser->getName() ?: $oauthUser->getNickname(),
            'avatar_url' => $oauthUser->getAvatar(),
            'email_verified_at' => $email ? now() : null,
        ]);
    }
}
