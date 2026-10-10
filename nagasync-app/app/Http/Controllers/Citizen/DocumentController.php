<?php
namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\DocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Cache;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $docs = $request->user()->documentRequests()->latest()->get()->map(fn ($d) =>
            $d->only(['id','document_type','purpose','tracking_number','status','remarks','issued_at','created_at'])
            + ['verify_url' => $d->status === 'approved' ? $d->verificationUrl() : null]);

        return Inertia::render('Citizen/Documents/Index', [
            'types' => DocumentRequest::TYPES,
            'documents' => $docs,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'document_type' => ['required', Rule::in(DocumentRequest::TYPES)],
            'purpose'       => ['required', 'string', 'max:255'],
        ]);

        if (! $request->user()->barangay_id) {
            return back()->withErrors(['document_type' => 'Set your barangay before requesting documents.']);
        }

        $request->user()->documentRequests()->create($data + [
            'barangay_id'     => $request->user()->barangay_id,
            'tracking_number' => DocumentRequest::generateTrackingNumber(),
        ]);

        return back()->with('success', 'Request submitted.');
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
}