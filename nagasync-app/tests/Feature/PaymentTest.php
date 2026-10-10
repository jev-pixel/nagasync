<?php

namespace Tests\Feature;

use App\Models\MunicipalPayment;
use App\Models\User;
use App\Notifications\PaymentReceived;
use App\Services\Payments\PaymentProcessor;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    private function makePayment(User $user): MunicipalPayment
    {
        return MunicipalPayment::create([
            'reference_no' => MunicipalPayment::generateReference(), 'user_id' => $user->id,
            'barangay_id' => $user->barangay_id, 'payment_type' => 'barangay_clearance_fee',
            'amount' => 5000, 'convenience_fee' => 1500, 'total' => 6500,
        ]);
    }

    public function test_amount_is_decided_by_the_server(): void
    {
        $this->actingAs($this->userWithRole('citizen'))
            ->post('/payments', ['payment_type' => 'barangay_clearance_fee', 'amount' => 1, 'total' => 1]);

        $p = MunicipalPayment::firstOrFail();
        $this->assertEquals(config('nagasync.payment_types.barangay_clearance_fee.amount'), $p->amount);
        $this->assertEquals($p->amount + config('nagasync.convenience_fee'), $p->total);
    }

    public function test_unknown_payment_type_is_rejected(): void
    {
        $this->actingAs($this->userWithRole('citizen'))
            ->post('/payments', ['payment_type' => 'free_money'])
            ->assertSessionHasErrors('payment_type');

        $this->assertSame(0, MunicipalPayment::count());
    }

    public function test_settlement_is_idempotent(): void
    {
        Notification::fake();
        $user = $this->userWithRole('citizen');
        $payment = $this->makePayment($user);
        $processor = app(PaymentProcessor::class);

        $processor->settle($payment->reference_no, 'paid');
        $paidAt = $payment->fresh()->paid_at;
        $processor->settle($payment->reference_no, 'failed'); // a late or duplicate webhook

        $fresh = $payment->fresh();
        $this->assertSame('paid', $fresh->status);
        $this->assertEquals($paidAt, $fresh->paid_at);
        Notification::assertSentToTimes($user, PaymentReceived::class, 1);
    }

    public function test_other_users_cannot_open_someone_elses_sandbox_checkout(): void
    {
        $payment = $this->makePayment($this->userWithRole('citizen'));

        $this->actingAs($this->userWithRole('citizen'))
            ->get("/payments/sandbox/{$payment->reference_no}")->assertForbidden();
    }
}