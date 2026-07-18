<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A hidden treasure at a secret coordinate. Players close in on it by testing distance.
 *
 * Note: latitude/longitude are the secret and must never be serialized to a client
 * response. They are marked Hidden as a defense-in-depth measure; controllers should
 * also avoid selecting them into any client-facing payload.
 */
#[Fillable(['user_id', 'code', 'message', 'latitude', 'longitude', 'created_accuracy_m', 'status'])]
#[Hidden(['latitude', 'longitude'])]
class Treasure extends Model
{
    /** @use HasFactory<\Database\Factories\TreasureFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'created_accuracy_m' => 'float',
            'unlock_count' => 'integer',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** @return HasOne<TreasureImage, $this> */
    public function image(): HasOne
    {
        return $this->hasOne(TreasureImage::class);
    }

    public function hasImage(): bool
    {
        return $this->image()->exists();
    }

    /** @return HasMany<Unlock, $this> */
    public function unlocks(): HasMany
    {
        return $this->hasMany(Unlock::class);
    }
}
