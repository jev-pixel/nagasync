<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ReviewAccess;
use App\Models\AuditLog;
use App\Models\DocumentRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * PDF certificate for a RELEASED barangay document. City Hall documents stay in-person pickup.
 * Owner, or the staff who may review that request, can open it.
 */
class CertificateController extends Controller
{
    private const IMAGES = ['image/jpeg', 'image/png'];

    public function show(Request $request, DocumentRequest $documentRequest)
    {
        $user = $request->user();

        abort_unless($documentRequest->user_id === $user->id || ReviewAccess::canReview($user, $documentRequest), 403);
        abort_if($documentRequest->isCityLevel(), 404);
        abort_unless($documentRequest->status === 'released', 404); // only valid documents get a certificate

        // Staff printing a copy of someone else's document is recorded (the owner downloading their own is not)
        if ($documentRequest->user_id !== $user->id) {
            AuditLog::create([
                'user_id'        => $user->id,
                'action'         => 'certificate_downloaded',
                'auditable_type' => DocumentRequest::class,
                'auditable_id'   => $documentRequest->id,
                'ip_address'     => $request->ip(),
                'user_agent'     => $request->userAgent(),
            ]);
        }

        $doc = $documentRequest->load(['user:id,name', 'barangay', 'attachments.requirement']);

        $fields = collect(config('nagasync_documents.personal_info')[$doc->document_type] ?? [])->keyBy('key');
        $info = collect($doc->personal_info ?? [])->mapWithKeys(function ($v, $k) use ($fields) {
            $f = $fields[$k] ?? null;
            if (($f['type'] ?? null) === 'date') {
                try { $v = Carbon::parse($v)->format('F j, Y'); } catch (\Throwable) { /* print as entered */ }
            }

            return [($f['label'] ?? ucfirst(str_replace('_', ' ', $k))) => $v];
        });

        // The signature on file when the document was approved. Documents approved before the snapshot
        // existed use the barangay's current signature, as long as the captain's name is unchanged.
        $sigPath = $doc->signatory_signature_path
            ?: (($doc->signatory_name === null || $doc->signatory_name === $doc->barangay?->captain_name)
                ? $doc->barangay?->captain_signature_path : null);

        // The 1x1 photo is an ordinary uploaded requirement (private disk)
        $photoAtt = $doc->attachments->first(fn ($a) => $a->requirement?->name === 'Recent 1x1 photo');

        $pdf = Pdf::loadView('certificates.barangay', [
            'doc'       => $doc,
            'name'      => mb_strtoupper($doc->user->name),
            'barangay'  => $doc->barangay?->name,
            'info'      => $info,
            'raw'       => $doc->personal_info ?? [],
            'captain'   => $doc->signatory_name ?: $doc->barangay?->captain_name,
            'signature' => $this->dataUri($sigPath),
            'photo'     => $photoAtt ? $this->dataUri($photoAtt->file_path) : null,
            'qr'        => 'data:image/svg+xml;base64,' . base64_encode(
                (string) QrCode::format('svg')->size(110)->margin(0)->generate($doc->verificationUrl())
            ),
            'issued'    => ($doc->released_at ?? $doc->issued_at)?->format('F j, Y'),
        ])->setPaper('a4');

        return $pdf->stream($doc->tracking_number . '.pdf');
    }

    /** Embed a private image (jpg/png) as a data URI so dompdf needs no public URL. */
    private function dataUri(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($path) || $disk->size($path) > 5 * 1024 * 1024) {
            return null;
        }

        $mime = $disk->mimeType($path);

        return in_array($mime, self::IMAGES, true) ? "data:{$mime};base64," . base64_encode($disk->get($path)) : null;
    }
}