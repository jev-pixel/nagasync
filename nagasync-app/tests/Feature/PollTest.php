<?php

namespace Tests\Feature;

use App\Models\Poll;
use App\Models\PollBallot;
use App\Models\PollParticipation;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PollTest extends TestCase
{
    private function openPoll(?int $barangayId = null): Poll
    {
        $poll = Poll::create([
            'title' => 'Test poll', 'barangay_id' => $barangayId, 'is_published' => true,
            'opens_at' => now()->subHour(), 'closes_at' => now()->addHour(),
        ]);
        $poll->options()->create(['label' => 'A', 'position' => 0]);
        $poll->options()->create(['label' => 'B', 'position' => 1]);

        return $poll;
    }

    private function resident(?string $barangay = null): User
    {
        return $this->userWithRole('citizen', $barangay ? $this->barangay($barangay) : null, ['resident_verified_at' => now()]);
    }

    public function test_unverified_residents_cannot_vote(): void
    {
        $poll = $this->openPoll();

        $this->actingAs($this->userWithRole('citizen'))
            ->post("/polls/{$poll->id}/vote", ['option_id' => $poll->options->first()->id])->assertForbidden();

        $this->assertSame(0, PollBallot::count());
    }

    public function test_one_person_gets_one_vote(): void
    {
        $poll = $this->openPoll();
        $user = $this->resident();
        $optionId = $poll->options->first()->id;

        $this->actingAs($user)->post("/polls/{$poll->id}/vote", ['option_id' => $optionId])->assertSessionHasNoErrors();
        $this->actingAs($user)->post("/polls/{$poll->id}/vote", ['option_id' => $optionId])->assertSessionHasErrors('option_id');

        $this->assertSame(1, PollParticipation::count());
        $this->assertSame(1, PollBallot::count());
    }

    public function test_barangay_scoped_polls_exclude_other_barangays(): void
    {
        $poll = $this->openPoll($this->barangay('A')->id);

        $this->actingAs($this->resident('B'))
            ->post("/polls/{$poll->id}/vote", ['option_id' => $poll->options->first()->id])->assertForbidden();
    }

    public function test_closed_polls_refuse_votes(): void
    {
        $poll = $this->openPoll();
        $poll->update(['closes_at' => now()->subMinute()]);

        $this->actingAs($this->resident())
            ->post("/polls/{$poll->id}/vote", ['option_id' => $poll->options->first()->id])->assertStatus(422);
    }

    public function test_results_stay_hidden_until_the_poll_closes(): void
    {
        $poll = $this->openPoll();
        $user = $this->resident();
        $this->actingAs($user)->post("/polls/{$poll->id}/vote", ['option_id' => $poll->options->first()->id]);

        $this->actingAs($user)->get('/polls')->assertInertia(fn (Assert $p) => $p
            ->where('polls.0.state', 'open')->where('polls.0.options.0.votes', null));

        $poll->update(['closes_at' => now()->subMinute()]);

        $this->actingAs($user)->get('/polls')->assertInertia(fn (Assert $p) => $p
            ->where('polls.0.state', 'closed')->where('polls.0.options.0.votes', 1));
    }

    public function test_ballots_cannot_be_traced_to_a_person(): void
    {
        $this->assertFalse(Schema::hasColumn('poll_ballots', 'user_id'));
        $this->assertFalse(Schema::hasColumn('poll_ballots', 'created_at'));
    }
}