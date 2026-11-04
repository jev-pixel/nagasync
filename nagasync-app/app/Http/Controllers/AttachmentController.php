<?php

namespace App\Http\Controllers;

use App\Models\RequestAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Streams a citizen's uploaded requirement from PRIVATE storage.
 * Files are never reachable by a public URL: only the owner, or staff/admin who are
 * allowed to review that request, can open them.
 */
class AttachmentController extends Controller
{
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'application/pdf'];

    public function show(Request $request, RequestAttachment $attachment)
    {
        $doc  = $attachment->documentRequest;
        $user = $request->user();

        abort_unless(
            $doc && ($doc->user_id === $user->id || ReviewAccess::canReview($user, $doc)),
            403
        );

        // A linked NagaSync document has no file of its own.
        abort_if(! $attachment->file_path, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($attachment->file_path), 404);

        // Only ever serve the types we accept at upload, and never let the browser guess.
        $type = in_array($attachment->mime_type, self::ALLOWED_TYPES, true)
            ? $attachment->mime_type
            : 'application/octet-stream';

        // Optional: record staff views here with your audit log helper, since these are personal documents.

        return $disk->response($attachment->file_path, $attachment->original_name, [
            'Content-Type'           => $type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'private, no-store',
        ]);
    }
}