<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Barangay;
use App\Models\DocumentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * Who signs each barangay's certificates: the Punong Barangay's name and signature image.
 * Administrators only. The image lives on the PRIVATE disk and is embedded in the PDF by the
 * server; it is never reachable by a public URL.
 */
class BarangaySignatoryController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Barangays/Index', [
            'barangays' => Barangay::orderBy('name')->get()->map(fn ($b) => [
                'id'            => $b->id,
                'name'          => $b->name,
                'captain_name'  => $b->captain_name,
                'has_signature' => (bool) $b->captain_signature_path,
                'version'       => $b->updated_at?->timestamp, // busts the browser cache after a new upload
            ]),
        ]);
    }

    public function update(Request $request, Barangay $barangay)
    {
        $data = $request->validate([
            'captain_name'     => ['required', 'string', 'max:120'],
            'signature'        => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:1024', // 1 MB
                                   'dimensions:min_width=200,min_height=60,max_width=2500,max_height=1500'],
            'remove_signature' => ['nullable', 'boolean'],
        ], [
            'signature.dimensions' => 'The signature image must be between 200×60 and 2500×1500 pixels.',
            'signature.max'        => 'The signature image must be 1 MB or smaller.',
        ]);

        $old  = $barangay->captain_signature_path;
        $path = $old;
        $signature = 'unchanged';

        if ($request->hasFile('signature')) {
            $path = $request->file('signature')->store('signatures', 'local');
            $signature = 'replaced';
        } elseif ($request->boolean('remove_signature')) {
            $path = null;
            $signature = 'removed';
        }

        $barangay->forceFill([
            'captain_name'           => trim($data['captain_name']),
            'captain_signature_path' => $path,
        ])->save();

        // Keep the old file while an issued document still points at it
        if ($old && $old !== $path && ! DocumentRequest::where('signatory_signature_path', $old)->exists()) {
            Storage::disk('local')->delete($old);
        }

        // A signature is sensitive: record who changed it (never the file itself)
        AuditLog::create([
            'user_id'        => $request->user()->id,
            'action'         => 'barangay_signatory_updated',
            'auditable_type' => Barangay::class,
            'auditable_id'   => $barangay->id,
            'new_values'     => ['captain_name' => $barangay->captain_name, 'signature' => $signature],
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        return back()->with('success', 'Signatory saved.');
    }

    /** Preview for the admin screen. */
    public function signature(Barangay $barangay)
    {
        $disk = Storage::disk('local');
        abort_unless($barangay->captain_signature_path && $disk->exists($barangay->captain_signature_path), 404);

        return $disk->response($barangay->captain_signature_path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'private, no-store',
        ]);
    }
}
