<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Notifications\BusinessReviewed;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BusinessReviewController extends Controller
{
    public function index(Request $request)
    {
        $actor = $request->user();
        abort_unless($actor->role === 'admin' || $actor->barangay_id, 403);

        $status = in_array($request->query('status'), array_keys(Business::TRANSITIONS), true)
            ? $request->query('status') : 'pending';

        $businesses = Business::with(['owner:id,name,phone', 'barangay:id,name'])
            ->when($actor->role !== 'admin', fn ($q) => $q->where('barangay_id', $actor->barangay_id))
            ->where('status', $status)->latest()->paginate(20)->withQueryString()
            ->through(fn ($b) => [
                'id' => $b->id, 'name' => $b->name, 'category' => $b->category, 'address' => $b->address,
                'description' => $b->description, 'permit_no' => $b->permit_no, 'status' => $b->status,
                'barangay' => $b->barangay?->name, 'owner' => $b->owner?->name, 'owner_phone' => $b->owner?->phone,
                'is_featured' => $b->is_featured, 'slug' => $b->slug, 'review_note' => $b->review_note,
                'submitted' => $b->created_at->toDayDateTimeString(),
            ]);

        return Inertia::render('Staff/Businesses/Index', [
            'businesses' => $businesses,
            'status'     => $status,
            'statuses'   => array_keys(Business::TRANSITIONS),
            'canFeature' => $actor->role === 'admin',
        ]);
    }

    public function update(Request $request, Business $business)
    {
        $actor = $request->user();

        abort_unless(
            $actor->role === 'admin'
                || ($actor->barangay_id && (int) $actor->barangay_id === (int) $business->barangay_id),
            403
        );

        // Featuring is a curation decision: administrators only
        if ($request->has('is_featured')) {
            abort_unless($actor->role === 'admin', 403);
            $business->forceFill(['is_featured' => $request->boolean('is_featured')])->save();

            return back();
        }

        $data = $request->validate([
            'status' => ['required', Rule::in(['approved', 'rejected', 'suspended'])],
            'note'   => ['nullable', 'string', 'max:300', 'required_if:status,rejected', 'required_if:status,suspended'],
        ]);

        abort_unless(in_array($data['status'], Business::TRANSITIONS[$business->status], true), 422, 'Invalid status change.');

        $business->forceFill([
            'status'      => $data['status'],
            'review_note' => $data['note'] ?? null,
            'reviewed_by' => $actor->id,
            'reviewed_at' => now(),
        ])->save();

        $business->owner?->notify(new BusinessReviewed($business));

        return back();
    }
}