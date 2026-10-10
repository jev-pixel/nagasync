<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\Poll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PollManagementController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Polls/Index', [
            'barangays' => Barangay::orderBy('name')->get(['id', 'name']),
            'polls' => Poll::with('barangay:id,name')->withCount('participations')->latest()->get()->map(fn ($p) => [
                'id' => $p->id, 'title' => $p->title, 'scope' => $p->barangay?->name ?? 'City-wide',
                'opens_at' => $p->opens_at->toDayDateTimeString(),
                'closes_at' => $p->closes_at->toDayDateTimeString(),
                'is_published' => $p->is_published, 'closed' => $p->closes_at->isPast(),
                'participants' => $p->participations_count, // turnout only; results stay hidden until close
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'barangay_id' => ['nullable', 'exists:barangays,id'],
            'opens_at'    => ['required', 'date'],
            'closes_at'   => ['required', 'date', 'after:opens_at'],
            'options'     => ['required', 'array', 'min:2', 'max:8'],
            'options.*'   => ['required', 'string', 'max:150', 'distinct'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $poll = Poll::create([
                'title' => $data['title'], 'description' => $data['description'] ?? null,
                'barangay_id' => $data['barangay_id'] ?? null,
                'opens_at' => $data['opens_at'], 'closes_at' => $data['closes_at'],
                'created_by' => $request->user()->id,
            ]);

            foreach (array_values($data['options']) as $i => $label) {
                $poll->options()->create(['label' => $label, 'position' => $i]);
            }
        });

        return back();
    }

    public function publish(Poll $poll)
    {
        abort_if($poll->closes_at->isPast(), 422, 'This poll has already closed.');
        $poll->update(['is_published' => true]);

        return back();
    }
}