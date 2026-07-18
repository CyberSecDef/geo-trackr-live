<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records that a treasure was unlocked. user_id is null for anonymous players.
 */
#[Fillable(['treasure_id', 'user_id', 'unlocked_at'])]
class Unlock extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'unlocked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Treasure, $this> */
    public function treasure(): BelongsTo
    {
        return $this->belongsTo(Treasure::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
