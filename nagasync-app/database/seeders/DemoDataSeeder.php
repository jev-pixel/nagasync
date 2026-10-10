<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\DocumentRequest;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $citizen = User::where('email', 'citizen@nagasync.test')->firstOrFail();
        $staff = User::where('email', 'barangay_staff@nagasync.test')->firstOrFail();
        $barangayIds = Barangay::pluck('id');
        $statuses = ['pending', 'processing', 'approved', 'approved', 'approved', 'rejected', 'revoked'];
        $purposes = ['employment', 'school enrollment', 'travel', 'scholarship', 'loan application'];

        foreach (range(1, 60) as $i) {
            $created = now()->subDays(rand(0, 13))->subMinutes(rand(0, 1400));
            $status = $statuses[array_rand($statuses)];

            $issued = null;
            if (in_array($status, ['approved', 'revoked'], true)) {
                $issued = $created->copy()->addMinutes(rand(20, 2880));
                if ($issued->gt(now())) {
                    $issued = now();
                }
            }

            $doc = new DocumentRequest([
                'user_id'         => $citizen->id,
                'barangay_id'     => $barangayIds->random(),
                'document_type'   => DocumentRequest::TYPES[array_rand(DocumentRequest::TYPES)],
                'purpose'         => $purposes[array_rand($purposes)],
                'tracking_number' => DocumentRequest::generateTrackingNumber(),
                'status'          => $status,
                'processed_by'    => $status === 'pending' ? null : $staff->id,
                'issued_at'       => $issued,
            ]);
            $doc->forceFill(['created_at' => $created, 'updated_at' => $created])->save();
        }
    }
}