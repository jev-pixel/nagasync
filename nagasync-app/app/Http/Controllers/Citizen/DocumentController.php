<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use App\Models\DocumentRequirement;
use App\Models\RequestAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DocumentController extends Controller
{
    private const FILE_RULES = ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120']; // 5 MB
    private const EDITABLE   = ['pending', 'processing', 'needs_resubmission'];

    public function index(Request $request)
    {
        $user = $request->user();

        $requirements = DocumentRequirement::active()->orderBy('sort_order')->get();

        // For every "issued document" requirement: which of the resident's approved documents
        // could satisfy it, and whether one is already on its way.
        $linkable   = [];
        $inProgress = [];
        foreach ($requirements->where('kind', DocumentRequirement::KIND_ISSUED) as $r) {
            $linkable[$r->id] = DocumentRequest::usableFor($user->id, $r->satisfied_by_type, $r->max_age_days)
                ->limit(5)->get()
                ->map(fn ($d) => [
                    'id'              => $d->id,
                    'tracking_number' => $d->tracking_number,
                    'document_type'   => $d->document_type,
                    'issued_at'       => ($d->issued_at ?? $d->updated_at)?->toDateString(),
                ])->values();

            $inProgress[$r->id] = $user->documentRequests()
                ->where('document_type', $r->satisfied_by_type)
                ->whereIn('status', self::EDITABLE)
                ->latest('id')->first(['id', 'tracking_number', 'status']);
        }

        $docs = $user->documentRequests()
            ->with(['attachments.linkedDocument', 'attachments.requirement'])
            ->latest()
            ->get()
            ->map(fn ($d) => $d->only([
                    'id', 'document_type', 'level', 'purpose', 'tracking_number', 'status',
                    'remarks', 'issued_at', 'created_at',
                    // City Hall fee assessment and counter release
                    'fee_total', 'fee_breakdown', 'released_at', 'official_receipt_no',
                ]) + [
                    'verify_url'  => $d->status === 'approved' ? $d->verificationUrl() : null,
                    'attachments' => $d->attachments->map(fn ($a) => [
                        'id'                      => $a->id,
                        'document_requirement_id' => $a->document_requirement_id,
                        'original_name'           => $a->original_name,
                        'review_status'           => $a->review_status,
                        'reviewer_remarks'        => $a->reviewer_remarks,
                        'linked'                  => $a->isLinked() ? [
                            'tracking_number' => $a->linkedDocument?->tracking_number,
                            'valid'           => $a->isStillValid($d->user_id),
                        ] : null,
                        'needs_action'            => $a->isReplaceable($d->user_id),
                    ])->values(),
                ]);

        return Inertia::render('Citizen/Documents/Index', [
            'groups'       => [
                'barangay' => DocumentRequest::BARANGAY_TYPES,
                'city'     => DocumentRequest::CITY_TYPES,
            ],
            // Types where the business itself is the purpose: the page skips "What is it for?"
            'purposeless'  => DocumentRequest::PURPOSELESS_TYPES,
            'requirements' => $requirements
                ->map(fn ($r) => $r->only([
                    'id', 'document_type', 'name', 'description', 'is_required',
                    'kind', 'satisfied_by_type', 'allow_upload_fallback', 'max_age_days',
                ]))
                ->groupBy('document_type'),
            'linkable'     => (object) $linkable,
            'inProgress'   => (object) $inProgress,
            'documents'    => $docs,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'document_type' => ['required', Rule::in(DocumentRequest::TYPES)],
            // Not asked for documents where the business itself is the purpose (e.g. Business Permit)
            'purpose'       => [
                Rule::requiredIf(! in_array($request->input('document_type'), DocumentRequest::PURPOSELESS_TYPES, true)),
                'nullable', 'string', 'max:255',
            ],
            'files'         => ['nullable', 'array'],
            'files.*'       => self::FILE_RULES,
            'links'         => ['nullable', 'array'],
            'links.*'       => ['nullable', 'integer'],
        ]);

        $data['purpose'] = filled($data['purpose'] ?? null) ? $data['purpose'] : 'Business operation';

        if (! $user->barangay_id) {
            return back()->withErrors(['document_type' => 'Set your barangay before requesting documents.']);
        }

        // One open City Hall request per document type, so the counter never sees two live QR codes
        // for the same permit. (Barangay documents can be requested again for a different purpose.)
        if (in_array($data['document_type'], DocumentRequest::CITY_TYPES, true)
            && $user->documentRequests()
                ->where('document_type', $data['document_type'])
                ->whereIn('status', [...self::EDITABLE, 'approved'])
                ->exists()
        ) {
            return back()->withErrors([
                'document_type' => 'You already have an open request for this document. Check its status under My requests.',
            ]);
        }

        // Only requirements configured for this type are honoured; unknown ids are ignored.
        $requirements = DocumentRequirement::active()
            ->where('document_type', $data['document_type'])
            ->orderBy('sort_order')
            ->get();

        $errors  = [];
        $linked  = []; // requirement id => approved DocumentRequest to link
        $hasFile = []; // requirement id => bool (file usable for this requirement)

        foreach ($requirements as $req) {
            $linkId = $request->input("links.{$req->id}");

            if ($req->isIssuedKind() && $linkId) {
                $doc = DocumentRequest::usableFor($user->id, $req->satisfied_by_type, $req->max_age_days)
                    ->whereKey($linkId)->first();

                if ($doc) {
                    $linked[$req->id] = $doc;
                } else {
                    $errors["files.{$req->id}"] = "That {$req->satisfied_by_type} can't be used (not yours, not approved, or too old).";
                }
            }

            $hasFile[$req->id] = $request->hasFile("files.{$req->id}")
                && (! $req->isIssuedKind() || $req->allow_upload_fallback);

            if ($req->is_required && ! isset($linked[$req->id]) && ! $hasFile[$req->id] && ! isset($errors["files.{$req->id}"])) {
                $errors["files.{$req->id}"] = $req->isIssuedKind()
                    ? "Link your approved {$req->satisfied_by_type}" . ($req->allow_upload_fallback ? ' or upload a copy.' : '.')
                    : "{$req->name} is required.";
            }
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $storedPaths = [];

        try {
            DB::transaction(function () use ($user, $data, $requirements, $linked, $hasFile, $request, &$storedPaths) {
                $doc = $user->documentRequests()->create(
                    Arr::only($data, ['document_type', 'purpose']) + [
                        'barangay_id'     => $user->barangay_id,
                        'tracking_number' => DocumentRequest::generateTrackingNumber(),
                    ]
                );

                foreach ($requirements as $req) {
                    if (isset($linked[$req->id])) {
                        // The system already knows this document is genuine: no staff check needed.
                        $doc->attachments()->create([
                            'document_requirement_id'    => $req->id,
                            'linked_document_request_id' => $linked[$req->id]->id,
                            'review_status'              => 'verified',
                            'reviewed_at'                => now(),
                        ]);
                    } elseif ($hasFile[$req->id]) {
                        $file = $request->file("files.{$req->id}");
                        $path = $file->store("requirements/{$doc->id}", 'local');
                        $storedPaths[] = $path;

                        $doc->attachments()->create([
                            'document_requirement_id' => $req->id,
                            'file_path'               => $path,
                            'original_name'           => $file->getClientOriginalName(),
                            'mime_type'               => $file->getMimeType(),
                            'size'                    => $file->getSize(),
                        ]);
                    }
                }
            });
        } catch (\Throwable $e) {
            // Don't leave orphaned private files if the transaction rolled back.
            Storage::disk('local')->delete($storedPaths);
            throw $e;
        }

        return back()->with('success', 'Request submitted. Staff will review your documents.');
    }

    /**
     * Citizen uploads (or replaces) one requirement on their own request.
     * Allowed when the item was rejected, its linked document is no longer valid,
     * or nothing has been provided for it yet (e.g. an optional requirement).
     */
    public function resubmit(Request $request, DocumentRequest $documentRequest, DocumentRequirement $requirement)
    {
        $this->authorizeEdit($request, $documentRequest, $requirement);

        // An issued-document requirement may forbid paper copies
        abort_if($requirement->isIssuedKind() && ! $requirement->allow_upload_fallback, 422);

        $request->validate(['file' => array_merge(['required'], self::FILE_RULES)]);

        $existing = $documentRequest->attachments()
            ->where('document_requirement_id', $requirement->id)
            ->first();

        // A verified or still-pending item must not be silently replaced.
        abort_if($existing && ! $existing->isReplaceable($documentRequest->user_id), 422);

        $file    = $request->file('file');
        $newPath = $file->store("requirements/{$documentRequest->id}", 'local');

        $fields = [
            'linked_document_request_id' => null,
            'file_path'        => $newPath,
            'original_name'    => $file->getClientOriginalName(),
            'mime_type'        => $file->getMimeType(),
            'size'             => $file->getSize(),
            'review_status'    => 'pending',
            'reviewer_remarks' => null,
            'reviewed_by'      => null,
            'reviewed_at'      => null,
        ];

        $this->saveAttachment($documentRequest, $requirement, $existing, $fields);
        $this->reopenIfClear($documentRequest);

        return back()->with('success', 'Document submitted for review.');
    }

    /** Citizen links one of their approved documents to a requirement on their own request. */
    public function link(Request $request, DocumentRequest $documentRequest, DocumentRequirement $requirement)
    {
        $this->authorizeEdit($request, $documentRequest, $requirement);
        abort_unless($requirement->isIssuedKind(), 404);

        $data = $request->validate(['document_id' => ['required', 'integer']]);

        $linked = DocumentRequest::usableFor($request->user()->id, $requirement->satisfied_by_type, $requirement->max_age_days)
            ->whereKey($data['document_id'])->first();

        if (! $linked) {
            throw ValidationException::withMessages([
                'document_id' => "That {$requirement->satisfied_by_type} can't be used (not yours, not approved, or too old).",
            ]);
        }

        $existing = $documentRequest->attachments()
            ->where('document_requirement_id', $requirement->id)
            ->first();

        abort_if($existing && ! $existing->isReplaceable($documentRequest->user_id), 422);

        $this->saveAttachment($documentRequest, $requirement, $existing, [
            'linked_document_request_id' => $linked->id,
            'file_path'        => null,
            'original_name'    => null,
            'mime_type'        => null,
            'size'             => null,
            'review_status'    => 'verified',
            'reviewer_remarks' => null,
            'reviewed_by'      => null,
            'reviewed_at'      => now(),
        ]);
        $this->reopenIfClear($documentRequest);

        return back()->with('success', "{$requirement->satisfied_by_type} linked.");
    }

    public function qr(Request $request, DocumentRequest $documentRequest)
    {
        abort_unless($documentRequest->user_id === $request->user()->id, 403);
        abort_unless($documentRequest->status === 'approved', 404);

        $url = $documentRequest->verificationUrl();
        $svg = Cache::remember('qr:' . md5($url), now()->addDay(), fn () =>
            (string) QrCode::format('svg')->size(220)->margin(1)->generate($url));

        return response($svg, 200, [
            'Content-Type'  => 'image/svg+xml',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    // ---- helpers ---------------------------------------------------------

    private function authorizeEdit(Request $request, DocumentRequest $doc, DocumentRequirement $requirement): void
    {
        abort_unless($doc->user_id === $request->user()->id, 403);
        abort_unless(in_array($doc->status, self::EDITABLE, true), 422);
        abort_unless(
            $requirement->is_active && $requirement->document_type === $doc->document_type,
            404
        );
    }

    /** Create or replace the attachment row, removing a replaced private file. */
    private function saveAttachment(DocumentRequest $doc, DocumentRequirement $requirement, ?RequestAttachment $existing, array $fields): void
    {
        if ($existing) {
            $oldPath = $existing->file_path;
            $existing->update($fields);

            if ($oldPath && $oldPath !== ($fields['file_path'] ?? null)) {
                Storage::disk('local')->delete($oldPath);
            }
        } else {
            $doc->attachments()->create($fields + ['document_requirement_id' => $requirement->id]);
        }
    }

    /** Back to the staff queue once nothing is left rejected or invalid. */
    private function reopenIfClear(DocumentRequest $doc): void
    {
        if ($doc->status !== 'needs_resubmission') {
            return;
        }

        $stillBroken = $doc->attachments()->with(['linkedDocument', 'requirement'])->get()
            ->contains(fn ($a) => $a->isReplaceable($doc->user_id));

        if (! $stillBroken) {
            $doc->update(['status' => 'pending']);
        }
    }
}