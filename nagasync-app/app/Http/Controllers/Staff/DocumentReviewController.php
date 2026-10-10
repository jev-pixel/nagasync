<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use App\Notifications\DocumentStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DocumentReviewController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $q = DocumentRequest::with(['user:id,name', 'barangay:id,name'])->latest();

        if ($user->role !== 'admin') {
            // Staff must belong to a barangay; otherwise they would match unassigned rows
            abort_unless($user->barangay_id, 403);
            $q->where('barangay_id', $user->barangay_id);
        }

        return Inertia::render('Staff/Documents/Index', [
            'documents' => $q->paginate(20)->through(fn ($d) => [
                'id' => $d->id, 'tracking_number' => $d->tracking_number,
                'document_type' => $d->document_type, 'purpose' => $d->purpose,
                'status' => $d->status, 'remarks' => $d->remarks,
                'citizen' => $d->user?->name, 'barangay' => $d->barangay?->name,
                'created_at' => $d->created_at->toDateTimeString(),
            ]),
            'transitions' => DocumentRequest::TRANSITIONS,
        ]);
    }

    public function update(Request $request, DocumentRequest $documentRequest)
    {
        $user = $request->user();

        abort_unless(
            $user->role === 'admin'
                || ($user->barangay_id && $documentRequest->barangay_id === $user->barangay_id),
            403
        );

        $data = $request->validate([
            'status'  => ['required', Rule::in(['processing', 'approved', 'rejected', 'revoked'])],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        // Lock the row so two simultaneous clicks can't both pass the transition check
        $document = DB::transaction(function () use ($documentRequest, $data, $user) {
            $doc = DocumentRequest::whereKey($documentRequest->id)->lockForUpdate()->firstOrFail();

            abort_unless(
                in_array($data['status'], DocumentRequest::TRANSITIONS[$doc->status], true),
                422,
                'Invalid status change.'
            );

            $doc->update([
                'status'       => $data['status'],
                'remarks'      => $data['remarks'] ?? $doc->remarks,
                'processed_by' => $user->id,
                'issued_at'    => $data['status'] === 'approved' ? now() : $doc->issued_at,
            ]);

            return $doc;
        });

        if (in_array($data['status'], ['approved', 'rejected', 'revoked'], true)) {
            $document->user?->notify(new DocumentStatusChanged($document));
        }

        return back()->with('success', 'Status updated.');
    }
}