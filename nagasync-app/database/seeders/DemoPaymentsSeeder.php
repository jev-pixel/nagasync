<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\MunicipalPayment;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoPaymentsSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $citizen = User::where('email', 'citizen@nagasync.test')->firstOrFail();
        $barangayIds = Barangay::pluck('id');
        $types = config('nagasync.payment_types');
        $fee = config('nagasync.convenience_fee');
        $statuses = ['paid', 'paid', 'paid', 'paid', 'failed', 'pending'];

        foreach (range(1, 40) as $i) {
            $key = array_rand($types);
            $status = $statuses[array_rand($statuses)];
            $created = now()->subDays(rand(0, 13))->subMinutes(rand(0, 1400));
            $amount = $types[$key]['amount'];

            $payment = new MunicipalPayment([
                'reference_no' => MunicipalPayment::generateReference(),
                'user_id' => $citizen->id, 'barangay_id' => $barangayIds->random(),
                'payment_type' => $key, 'amount' => $amount, 'convenience_fee' => $fee,
                'total' => $amount + $fee, 'status' => $status,
                'gateway' => 'sandbox', 'gateway_ref' => 'sbx_demo' . $i,
                'paid_at' => $status === 'paid' ? $created->copy()->addMinutes(rand(1, 30)) : null,
            ]);
            $payment->forceFill(['created_at' => $created, 'updated_at' => $created])->save();
        }
    }
}