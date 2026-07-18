<?php

use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\TreasureImageController;
use App\Livewire\CreateTreasure;
use App\Livewire\MyTreasures;
use App\Livewire\TestTreasure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// ---- Public: landing + testing ----------------------------------------------
Route::get('/', TestTreasure::class)->name('home');

// Shareable deep link: /t/ABCD2345 pre-fills the code on the test screen.
Route::get('/t/{code}', TestTreasure::class)->name('treasure.test');

// ---- Social authentication (Google, Microsoft, Facebook in v1) ---------------
// Named 'login' so the `auth` middleware redirects guests here.
Route::get('/login', function () {
    return Auth::check() ? redirect()->route('treasures.index') : view('auth.login');
})->name('login');

Route::controller(SocialAuthController::class)->group(function () {
    Route::get('/auth/{provider}/redirect', 'redirect')
        ->whereIn('provider', ['google', 'microsoft', 'facebook'])
        ->name('auth.redirect');
    Route::get('/auth/{provider}/callback', 'callback')
        ->whereIn('provider', ['google', 'microsoft', 'facebook'])
        ->name('auth.callback');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('home');
})->name('logout');

// ---- Authenticated: create & manage treasures --------------------------------
Route::middleware('auth')->group(function () {
    Route::get('/treasures', MyTreasures::class)->name('treasures.index');
    Route::get('/treasures/create', CreateTreasure::class)->name('treasures.create');
});

// ---- Gated image streaming ---------------------------------------------------
// Serves a treasure's BLOB image only to the owner or a session that has
// unlocked it, so images can't be fetched without solving the puzzle.
Route::get('/image/{treasure}', TreasureImageController::class)->name('treasure.image');
