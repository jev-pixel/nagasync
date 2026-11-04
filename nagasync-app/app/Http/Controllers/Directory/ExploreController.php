<?php

namespace App\Http\Controllers\Directory;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ExploreController extends Controller
{
    public function index(Request $request)
    {
        // Unknown or malformed filter values are ignored rather than erroring, so shared links never break
        $rawTerm = $request->query('q');
        $term = is_string($rawTerm) ? trim(Str::limit($rawTerm, 80, '')) : '';

        $category = in_array($request->query('category'), Business::CATEGORIES, true)
            ? $request->query('category') : null;
        $tag = in_array($request->query('tag'), Business::TAGS, true)
            ? $request->query('tag') : null;

        $rawBarangay = $request->query('barangay');
        $barangay = is_numeric($rawBarangay) && (int) $rawBarangay > 0 ? (int) $rawBarangay : null;

        $like = '%' . addcslashes($term, '%_\\') . '%';

        $businesses = Business::approved()->with('barangay:id,name')
            ->when($term !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', $like)
                ->orWhere('tagline', 'like', $like)
                ->orWhere('description', 'like', $like)))
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($tag, fn ($q) => $q->whereJsonContains('tags', $tag))
            ->when($barangay, fn ($q) => $q->where('barangay_id', $barangay))
            ->orderByDesc('is_featured')->orderBy('name')
            ->paginate(12)->withQueryString()
            ->through(fn ($b) => $this->card($b));

        $filtered = $term !== '' || $category || $tag || $barangay;

        return Inertia::render('Explore/Index', [
            'businesses' => $businesses,
            'featured'   => $filtered || $businesses->currentPage() > 1 ? [] :
                Business::approved()->where('is_featured', true)->with('barangay:id,name')
                    ->orderBy('name')->limit(8)->get()->map(fn ($b) => $this->card($b)),
            'filters'    => ['q' => $term, 'category' => $category, 'tag' => $tag, 'barangay' => $barangay],
            'categories' => Business::CATEGORIES,
            'tags'       => Business::TAGS,
            'barangays'  => Barangay::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Business $business)
    {
        abort_unless($business->status === 'approved', 404);
        $business->load('barangay:id,name');

        // Only public fields: never the owner, the permit number or the review notes
        return Inertia::render('Explore/Show', [
            'business' => $this->card($business) + [
                'description'  => $business->description,
                'address'      => $business->address,
                'latitude'     => $business->latitude,
                'longitude'    => $business->longitude,
                'phone'        => $business->phone,
                'email'        => $business->email,
                'website'      => $business->website,
                'facebook_url' => $business->facebook_url,
                'hours'        => $business->hours,
                'tags'         => $business->tags ?? [],
            ],
        ]);
    }

    private function card(Business $b): array
    {
        return [
            'slug'        => $b->slug,
            'name'        => $b->name,
            'category'    => $b->category,
            'tagline'     => $b->tagline,
            'barangay'    => $b->barangay?->name,
            'price_range' => $b->price_range,
            'cover_url'   => $b->coverUrl(),
            'featured'    => $b->is_featured,
        ];
    }
}