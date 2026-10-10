<?php

namespace App\Http\Controllers\Citizen;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollBallot;
use App\Models\PollParticipation;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PollController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $now = now();

        $polls = Poll::with('options')->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('barangay_id')->orWhere('barangay_id', $user->barangay_id))
            ->orderByDesc('opens_at')->get();

        $voted = PollParticipation::where('user_id', $user->id)->pluck('poll_id')->all();

        $counts = PollBallot::whereIn('poll_id', $polls->pluck('id'))
            ->select('poll_option_id', DB::raw('count(*) as total'))
            ->groupBy('poll_option_id')->pluck('total', 'poll_option_id');

        return Inertia::render('Polls/Index', [
            'eligible' => $user->resident_verified_at !== null && $user->barangay_id !== null,
            'polls' => $polls->map(function ($p) use ($voted, $counts, $now) {
                $closed = $now->gt($p->closes_at);

                return [
                    'id' => $p->id, 'title' => $p->title, 'description' => $p->description,
                    'scope' => $p->barangay_id ? 'Barangay only' : 'City-wide',
                    'opens_at' => $p->opens_at->toDayDateTimeString(),
                    'closes_at' => $p->closes_at->toDayDateTimeString(),
                    'state' => $closed ? 'closed' : ($now->gte($p->opens_at) ? 'open' : 'upcoming'),
                    'has_voted' => in_array($p->id, $voted, true),
                    'options' => $p->options->map(fn ($o) => [
                        'id' => $o->id, 'label' => $o->label,
                        'votes' => $closed ? (int) ($counts[$o->id] ?? 0) : null, // hidden until close
                    ]),
                ];
            }),
        ]);
    }

    public function vote(Request $request, Poll $poll)
    {
        $user = $request->user();
        $data = $request->validate(['option_id' => ['required', 'integer']]);

        abort_unless($poll->is_published, 404);
        abort_unless($user->resident_verified_at !== null && $user->barangay_id !== null, 403);
        abort_unless($poll->barangay_id === null || (int) $poll->barangay_id === (int) $user->barangay_id, 403);
        abort_unless(now()->between($poll->opens_at, $poll->closes_at), 422, 'This poll is not open.');

        $option = $poll->options()->whereKey($data['option_id'])->firstOrFail();

        try {
            DB::transaction(function () use ($poll, $user, $option) {
                PollParticipation::create(['poll_id' => $poll->id, 'user_id' => $user->id]);
                PollBallot::create(['poll_id' => $poll->id, 'poll_option_id' => $option->id]);
            });
        } catch (UniqueConstraintViolationException) {
            return back()->withErrors(['option_id' => 'You have already voted in this poll.']);
        }

        return back();
    }
}