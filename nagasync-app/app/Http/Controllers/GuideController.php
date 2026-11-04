<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequirement;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * News, guidelines and "how to request" pages. Open to every signed-in account, including
 * citizens who are not verified yet. The checklists are read from the same requirement
 * records staff review against, so the guide always matches the real rules.
 */
class GuideController extends Controller
{
    public function __invoke(Request $request)
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
            // Placeholder announcements: move these to a table when admins need to post news
            'news' => [
                ['title' => 'Welcome to NagaSync', 'date' => now()->toFormattedDateString(),
                 'body' => 'Request barangay and City Hall documents online, track them, and claim them once approved.'],
                ['title' => 'Verification opens document requests', 'date' => now()->toFormattedDateString(),
                 'body' => 'Barangay staff confirm that you live in the barangay you registered with. Once verified, you can request documents, pay fees and vote in polls.'],
            ],
            'requirements' => $requirements,
        ]);
    }
}