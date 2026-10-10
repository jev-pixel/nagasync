<?php
namespace App\Http\Controllers;

use App\Models\DocumentRequest;
use Inertia\Inertia;

class VerificationController extends Controller
{
    public function show(DocumentRequest $documentRequest)
    {
        $documentRequest->load(['user:id,name', 'barangay:id,name']);

        return Inertia::render('Verify', [
            'valid'          => $documentRequest->status === 'approved',
            'status'         => $documentRequest->status,
            'trackingNumber' => $documentRequest->tracking_number,
            'documentType'   => $documentRequest->document_type,
            'barangay'       => $documentRequest->barangay?->name,
            'holder'         => $this->maskName($documentRequest->user->name),
            'issuedAt'       => $documentRequest->issued_at?->toFormattedDateString(),
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