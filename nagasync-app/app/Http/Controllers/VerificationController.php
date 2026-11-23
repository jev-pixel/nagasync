<?php
namespace App\Http\Controllers;

use App\Models\DocumentRequest;
use Inertia\Inertia;

class VerificationController extends Controller
{
    public function show(DocumentRequest $documentRequest)
    {
        $documentRequest->load(['user:id,name', 'barangay:id,name']);

        // A City Hall document is only issued once the fee is paid and it is handed over at the
        // counter (status "released"). Until then it is approved but not yet valid for anyone
        // relying on it. Barangay documents are valid once approved.
        $isCity = $documentRequest->isCityLevel();
        $valid  = $documentRequest->status === 'released';

        return Inertia::render('Verify', [
            'valid'          => $valid,
            'awaitingClaim'  => $documentRequest->status === 'approved',
            'status'         => $documentRequest->status,
            'trackingNumber' => $documentRequest->tracking_number,
            'documentType'   => $documentRequest->document_type,
            'barangay'       => $documentRequest->barangay?->name,
            'holder'         => $this->maskName($documentRequest->user->name),
            'issuedAt'       => $documentRequest->issued_at?->toFormattedDateString(),
            'releasedAt'     => $documentRequest->released_at?->toFormattedDateString(),
        ]);
    }

    // Data minimization: public page shows first name + initials only.
    private function maskName(string $name): string
    {
        return collect(explode(' ', trim($name)))
            ->map(fn ($p, $i) => $i === 0 ? $p : mb_substr($p, 0, 1) . '.')
            ->implode(' ');
    }
}