<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ReviewAccess;
use App\Models\AuditLog;
use App\Models\DocumentRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResidentController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor->role === 'admin' || $actor->barangay_id, 403);

        $term = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', ''); // unverified | verified | '' (all)

        // Everything this staff member may see: admin all residents, barangay staff their own barangay
        $scoped = fn () => User::where('role', 'citizen')
            ->when($actor->role !== 'admin', fn ($q) => $q->where('barangay_id', $actor->barangay_id));

        $counts = [
            'all'        => $scoped()->count(),
            'unverified' => $scoped()->whereNull('resident_verified_at')->count(),
            'verified'   => $scoped()->whereNotNull('resident_verified_at')->count(),
        ];

        $like = '%' . addcslashes($term, '%_\\') . '%';

        $residents = $scoped()
            ->with(['barangay:id,name', 'verifier:id,name'])
            ->when($status === 'unverified', fn ($q) => $q->whereNull('resident_verified_at'))
            ->when($status === 'verified', fn ($q) => $q->whereNotNull('resident_verified_at'))
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone', 'like', $like)))
            ->orderByRaw('resident_verified_at is not null') // unverified first
            ->latest()
            ->paginate(25)
            ->withQueryString();

        // Documents already issued (released) to the residents on this page, so staff can print a
        // copy when a resident asks at the barangay hall. Limited by ReviewAccess to what this
        // staff member may handle, and capped per resident to the 10 most recent.
        $issued = ReviewAccess::scopeQueue($actor, DocumentRequest::query())
            ->whereIn('user_id', $residents->getCollection()->pluck('id'))
            ->where('status', 'released')
            ->latest('released_at')
            ->get()
            ->reject(fn ($d) => $d->isCityLevel()) // City Hall documents have no PDF certificate (in-person pickup)
            ->groupBy('user_id');

        $residents->through(fn ($u) => [
            'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'phone' => $u->phone,
            'barangay' => $u->barangay?->name,
            'registered' => $u->created_at->toFormattedDateString(),
            'verified' => $u->resident_verified_at !== null,
            'verified_at' => $u->resident_verified_at?->toFormattedDateString(),
            'verified_by' => $u->verifier?->name,
            'documents' => ($issued[$u->id] ?? collect())->take(10)->map(fn ($d) => [
                'id' => $d->id,
                'document_type' => $d->document_type,
                'tracking_number' => $d->tracking_number,
                'released' => $d->released_at?->toFormattedDateString(),
            ])->values(),
        ]);

        return Inertia::render('Staff/Residents/Index', [
            'residents' => $residents,
            'filters' => ['q' => $term, 'status' => $status],
            'counts'  => $counts,
        ]);
    }

    public function update(Request $request, User $resident)
    {
        $actor = $request->user();

        abort_unless($resident->role === 'citizen', 404); // staff can't verify other staff
        abort_unless(
            $actor->role === 'admin'
                || ($actor->barangay_id && (int) $actor->barangay_id === (int) $resident->barangay_id),
            403
        );

        $verify = (bool) $request->validate(['verified' => ['required', 'boolean']])['verified'];

        // Nothing to do (also keeps the original verifier on record)
        if ($verify === ($resident->resident_verified_at !== null)) {
            return back();
        }

        $resident->forceFill([
            'resident_verified_at' => $verify ? now() : null,
            'resident_verified_by' => $verify ? $actor->id : null,
        ])->save();

        AuditLog::create([
            'user_id'        => $actor->id,
            'action'         => $verify ? 'resident_verified' : 'resident_unverified',
            'auditable_type' => User::class,
            'auditable_id'   => $resident->id,
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return back()->with('success', $verify ? 'Resident verified.' : 'Verification removed.');
    }
}