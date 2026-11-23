<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequirement;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * The Services page (the mobile bottom bar's "Services" tab). For citizens it also lists the
 * kinds of documents they can request, split into barangay and City Hall requests, read from
 * the same requirement records the Guide and the review queue use.
 */
class ServicesController extends Controller
{
    /**
     * Document types handled at City Hall. PLACEHOLDER: replace with your real list, or with
     * whatever ReviewAccess uses to decide which office handles a type.
     */
    private const CITY_HALL_TYPES = ['Business Permit'];

    public function __invoke(Request $request)
    {
        $types = ['barangay' => [], 'city_hall' => []];

        if ($request->user()->role === 'citizen') {
            DocumentRequirement::active()
                ->selectRaw('document_type, count(*) as requirements')
                ->groupBy('document_type')
                ->orderBy('document_type')
                ->get()
                ->each(function ($row) use (&$types) {
                    $level = in_array($row->document_type, self::CITY_HALL_TYPES, true) ? 'city_hall' : 'barangay';
                    $types[$level][] = ['type' => $row->document_type, 'requirements' => (int) $row->requirements];
                });
        }

        return Inertia::render('Services', ['requestTypes' => $types]);
    }
}