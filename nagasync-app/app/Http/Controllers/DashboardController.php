<?php

namespace App\Http\Controllers;

use App\Models\Poll;
use App\Models\PollParticipation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // Open polls this person can still vote in (not yet voted, in scope)
        $openPolls = Poll::where('is_published', true)
            ->where('opens_at', '<=', now())
            ->where('closes_at', '>=', now())
            ->where(fn ($q) => $q->whereNull('barangay_id')->orWhere('barangay_id', $user->barangay_id))
            ->whereNotIn('id', PollParticipation::where('user_id', $user->id)->select('poll_id'))
            ->orderBy('closes_at')
            ->get(['id', 'title', 'closes_at'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'closes_at' => $p->closes_at->toDayDateTimeString(),
            ]);

        return Inertia::render('Dashboard', [
            'openPolls' => $openPolls,
            'canVote' => $user->resident_verified_at !== null && $user->barangay_id !== null,
        ]);
    }
}