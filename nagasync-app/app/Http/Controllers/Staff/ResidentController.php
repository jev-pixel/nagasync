<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
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

        $residents = User::where('role', 'citizen')
            ->with(['barangay:id,name', 'verifier:id,name'])
            ->when($actor->role !== 'admin', fn ($q) => $q->where('barangay_id', $actor->barangay_id))
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%")))
            ->orderByRaw('resident_verified_at is not null') // unverified first
            ->latest()
            ->paginate(25)
            ->withQueryString()
            ->through(fn ($u) => [
                'id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'phone' => $u->phone,
                'barangay' => $u->barangay?->name,
                'registered' => $u->created_at->toFormattedDateString(),
                'verified' => $u->resident_verified_at !== null,
                'verified_at' => $u->resident_verified_at?->toFormattedDateString(),
                'verified_by' => $u->verifier?->name,
            ]);

        return Inertia::render('Staff/Residents/Index', [
            'residents' => $residents,
            'filters' => ['q' => $term],
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

        return back();
    }
}