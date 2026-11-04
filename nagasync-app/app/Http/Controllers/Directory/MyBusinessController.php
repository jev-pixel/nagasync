<?php

namespace App\Http\Controllers\Directory;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessRequest;
use App\Models\Barangay;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MyBusinessController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Businesses/Index', [
            'limit' => (int) config('nagasync.directory.max_listings_per_user'),
            'businesses' => $request->user()->businesses()->with('barangay:id,name')->latest()->get()->map(fn ($b) => [
                'id' => $b->id, 'slug' => $b->slug, 'name' => $b->name, 'category' => $b->category,
                'status' => $b->status, 'review_note' => $b->review_note, 'barangay' => $b->barangay?->name,
                'cover_url' => $b->coverUrl(),
            ]),
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('Businesses/Form', $this->formProps($request, null));
    }

    public function store(BusinessRequest $request)
    {
        $user = $request->user();
        $max = (int) config('nagasync.directory.max_listings_per_user');

        if ($user->businesses()->count() >= $max) {
            return back()->withErrors(['name' => "You can list up to {$max} businesses."]);
        }

        $data = $request->safe()->except('cover');
        $business = new Business($data);
        $business->forceFill([
            'owner_id'   => $user->id,
            'slug'       => Business::uniqueSlug($data['name']),
            'cover_path' => $request->hasFile('cover') ? $request->file('cover')->store('businesses', 'public') : null,
        ])->save();

        return redirect()->route('businesses.index');
    }

    public function edit(Request $request, Business $business)
    {
        abort_unless($business->owner_id === $request->user()->id, 403);

        return Inertia::render('Businesses/Form', $this->formProps($request, $business));
    }

    public function update(BusinessRequest $request, Business $business)
    {
        abort_unless($business->owner_id === $request->user()->id, 403);

        $data = $request->safe()->except('cover');

        // Loose comparison on purpose: "3" from the form equals the integer 3 in the database
        $material = collect(Business::MATERIAL)->contains(fn ($field) => ($data[$field] ?? null) != $business->{$field});

        $cover = $business->cover_path;
        if ($request->hasFile('cover')) {
            $new = $request->file('cover')->store('businesses', 'public');
            if ($cover) {
                Storage::disk('public')->delete($cover);
            }
            $cover = $new;
            $material = true;
        }

        $business->fill($data);
        $business->cover_path = $cover;

        // Substantive edits go back to review. A suspended listing is only ever released by staff.
        if ($material && in_array($business->status, ['approved', 'rejected'], true)) {
            $business->status = 'pending';
            $business->review_note = null;
        }
        $business->save();

        return redirect()->route('businesses.index');
    }

    public function destroy(Request $request, Business $business)
    {
        abort_unless($business->owner_id === $request->user()->id, 403);

        if ($business->cover_path) {
            Storage::disk('public')->delete($business->cover_path);
        }
        $business->delete();

        return redirect()->route('businesses.index');
    }

    private function formProps(Request $request, ?Business $business): array
    {
        return [
            'business' => $business ? [
                'id' => $business->id, 'name' => $business->name, 'category' => $business->category,
                'tagline' => $business->tagline, 'description' => $business->description,
                'barangay_id' => $business->barangay_id, 'address' => $business->address,
                'latitude' => $business->latitude, 'longitude' => $business->longitude,
                'phone' => $business->phone, 'email' => $business->email, 'website' => $business->website,
                'facebook_url' => $business->facebook_url, 'hours' => $business->hours,
                'price_range' => $business->price_range, 'tags' => $business->tags ?? [],
                'permit_no' => $business->permit_no, 'cover_url' => $business->coverUrl(),
                'status' => $business->status,
            ] : null,
            'categories'      => Business::CATEGORIES,
            'tags'            => Business::TAGS,
            'barangays'       => Barangay::orderBy('name')->get(['id', 'name']),
            'defaultBarangay' => $request->user()->barangay_id,
        ];
    }
}