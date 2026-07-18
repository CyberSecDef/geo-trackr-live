<?php

namespace App\Livewire;

use App\Models\Treasure;
use App\Models\Unlock;
use App\Services\CodeGenerator;
use App\Services\DistanceService;
use App\Support\RateLimit;
use App\Support\UnlockSession;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.app')]
class TestTreasure extends Component
{
    /** Code being tested (from route or user input). */
    #[Validate('required|string|size:8')]
    public string $code = '';

    /** 'imperial' | 'metric' — display only. */
    public string $unit = 'imperial';

    // Result state
    public ?string $result = null;
    public ?string $accuracyNote = null;
    public bool $unlocked = false;
    public bool $notFound = false;
    public bool $paused = false;

    // Reveal payload (only populated once unlocked)
    public ?int $treasureId = null;
    public ?string $revealMessage = null;
    public bool $revealHasImage = false;

    public function mount(?string $code = null): void
    {
        if ($code !== null) {
            $this->code = app(CodeGenerator::class)->normalize($code);
            $this->restoreIfAlreadyUnlocked();
        }
    }

    /**
     * Test the current code against the player's live coordinates.
     * Called from the browser once geolocation resolves.
     */
    public function test(float $lat, float $lng, ?float $accuracy = null): void
    {
        $this->reset('result', 'accuracyNote', 'unlocked', 'notFound', 'paused',
            'revealMessage', 'revealHasImage', 'treasureId');

        $normalized = app(CodeGenerator::class)->normalize($this->code);
        $this->validateOnly('code', ['code' => 'required|string|size:8']);

        $treasure = Treasure::where('code', $normalized)->first();

        if (! $treasure) {
            $this->notFound = true;

            return;
        }

        // Rate limit per (code + hashed IP) before doing any work.
        if (! RateLimit::allowTest($treasure)) {
            $this->result = 'Slow down a moment, then test again.';

            return;
        }

        RateLimit::recordAttempt($treasure);

        if (! $treasure->isActive()) {
            $this->paused = true;

            return;
        }

        $eval = app(DistanceService::class)->evaluate($treasure, $lat, $lng, $accuracy, $this->unit);

        $this->accuracyNote = $accuracy
            ? 'Your location is accurate to about '.$this->formatAccuracy($accuracy).'.'
            : null;

        if ($eval['unlocked']) {
            $this->unlockTreasure($treasure);

            return;
        }

        $this->result = $eval['label'];
    }

    private function unlockTreasure(Treasure $treasure): void
    {
        // Persist the unlock for the session so the reveal survives refreshes.
        UnlockSession::mark($treasure);

        DB::transaction(function () use ($treasure) {
            Unlock::create([
                'treasure_id' => $treasure->id,
                'user_id' => auth()->id(),
                'unlocked_at' => now(),
            ]);
            $treasure->increment('unlock_count');
        });

        $this->loadReveal($treasure);
    }

    private function restoreIfAlreadyUnlocked(): void
    {
        $treasure = Treasure::where('code', $this->code)->first();
        if ($treasure && UnlockSession::has($treasure)) {
            $this->loadReveal($treasure);
        }
    }

    private function loadReveal(Treasure $treasure): void
    {
        $this->unlocked = true;
        $this->treasureId = $treasure->id;
        $this->revealMessage = $treasure->message;
        $this->revealHasImage = $treasure->hasImage();
    }

    private function formatAccuracy(float $accuracyM): string
    {
        return $this->unit === 'metric'
            ? number_format($accuracyM).' m'
            : number_format($accuracyM * 3.280839895).' ft';
    }

    public function render()
    {
        return view('livewire.test-treasure');
    }
}
