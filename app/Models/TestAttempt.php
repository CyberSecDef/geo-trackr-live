<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A privacy-preserving log of a distance test, used only for rate limiting / abuse
 * detection. Stores no player coordinates and only a salted hash of the IP.
 */
#[Fillable(['treasure_id', 'ip_hash', 'attempted_at'])]
class TestAttempt extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Treasure, $this> */
    public function treasure(): BelongsTo
    {
        return $this->belongsTo(Treasure::class);
    }
}
