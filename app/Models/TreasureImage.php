<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The image BLOB for a treasure, stored directly in MySQL (MEDIUMBLOB).
 * The raw `data` column is Hidden so it is never accidentally serialized;
 * it is streamed to clients only via the gated image route.
 */
#[Fillable(['treasure_id', 'mime_type', 'byte_size', 'data'])]
#[Hidden(['data'])]
class TreasureImage extends Model
{
    protected function casts(): array
    {
        return [
            'byte_size' => 'integer',
        ];
    }

    /** @return BelongsTo<Treasure, $this> */
    public function treasure(): BelongsTo
    {
        return $this->belongsTo(Treasure::class);
    }
}
