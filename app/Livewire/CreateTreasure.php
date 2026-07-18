<?php

namespace App\Livewire;

use App\Models\Treasure;
use App\Models\TreasureImage;
use App\Services\CodeGenerator;
use App\Services\ImageProcessor;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('components.layouts.app')]
class CreateTreasure extends Component
{
    use WithFileUploads;

    #[Validate('required|string|max:1000')]
    public string $message = '';

    #[Validate('nullable|image|max:12288')] // 12 MB pre-processing; re-encoded/capped server-side
    public $photo = null;

    // Captured from the browser at "capture location" time.
    public ?float $latitude = null;
    public ?float $longitude = null;
    public ?float $accuracy = null;

    public bool $accuracyConfirmed = false;

    // Result after creation
    public ?string $createdCode = null;

    #[Computed]
    public function accuracyIsPoor(): bool
    {
        return $this->accuracy !== null
            && $this->accuracy > config('geocache.create_accuracy_warn_m');
    }

    /** Called from the browser once geolocation resolves. */
    public function setLocation(float $lat, float $lng, ?float $accuracy = null): void
    {
        $this->latitude = $lat;
        $this->longitude = $lng;
        $this->accuracy = $accuracy;
        $this->accuracyConfirmed = false;
    }

    public function save(CodeGenerator $codes, ImageProcessor $images): void
    {
        $this->validate();

        if ($this->latitude === null || $this->longitude === null) {
            $this->addError('latitude', 'Capture your location first.');

            return;
        }

        if ($this->accuracyIsPoor && ! $this->accuracyConfirmed) {
            $this->addError('accuracy', 'Your GPS accuracy is low. Confirm to place the treasure here anyway.');

            return;
        }

        $processed = null;
        if ($this->photo instanceof TemporaryUploadedFile) {
            try {
                $processed = $images->process($this->photo);
            } catch (Throwable $e) {
                $this->addError('photo', $e->getMessage());

                return;
            }
        }

        $treasure = DB::transaction(function () use ($codes, $processed) {
            $treasure = Treasure::create([
                'user_id' => auth()->id(),
                'code' => $codes->unique(),
                'message' => $this->message,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'created_accuracy_m' => $this->accuracy,
                'status' => 'active',
            ]);

            if ($processed) {
                TreasureImage::create([
                    'treasure_id' => $treasure->id,
                    'mime_type' => $processed['mime'],
                    'byte_size' => $processed['bytes'],
                    'data' => $processed['data'],
                ]);
            }

            return $treasure;
        });

        $this->createdCode = $treasure->code;
        $this->reset('message', 'photo', 'latitude', 'longitude', 'accuracy', 'accuracyConfirmed');
    }

    public function render()
    {
        return view('livewire.create-treasure');
    }
}
