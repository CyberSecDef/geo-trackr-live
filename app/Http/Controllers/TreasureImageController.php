<?php

namespace App\Http\Controllers;

use App\Models\Treasure;
use App\Support\UnlockSession;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TreasureImageController extends Controller
{
    /**
     * Stream a treasure's image BLOB — but only to the owner or a session that
     * has already unlocked it. Otherwise the image would be fetchable without
     * solving the puzzle.
     */
    public function __invoke(Request $request, Treasure $treasure): Response
    {
        $isOwner = $request->user() && $request->user()->id === $treasure->user_id;

        abort_unless($isOwner || UnlockSession::has($treasure), 403);

        $image = $treasure->image;
        abort_if($image === null, 404);

        return response($image->data, 200, [
            'Content-Type' => $image->mime_type,
            'Content-Length' => (string) $image->byte_size,
            // Private: tied to this authenticated/unlocked session, not shareable/CDN-cacheable.
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
