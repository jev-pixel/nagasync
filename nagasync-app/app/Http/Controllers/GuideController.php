<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequirement;
use Inertia\Inertia;

/**
 * Guidelines and "how to request" pages. Open to every signed-in account, including
 * citizens who are not verified yet. The checklists are read from the same requirement
 * records staff review against, so the guide always matches the real rules.
 * News lives on its own page (NewsController).
 */
class GuideController extends Controller
{
    public function __invoke()
    {
        $requirements = DocumentRequirement::active()
            ->orderBy('document_type')
            ->orderBy('sort_order')
            ->get(['document_type', 'name', 'description', 'is_required', 'kind'])
            ->groupBy('document_type')
            ->map(fn ($rows) => $rows->map(fn ($r) => [
                'name'        => $r->name,
                'description' => $r->description,
                'is_required' => (bool) $r->is_required,
                'kind'        => $r->kind,
            ])->values());

        return Inertia::render('Guide/Index', [
            'requirements' => $requirements,
        ]);
    }
}