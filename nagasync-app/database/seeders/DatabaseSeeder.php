<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
{
    $this->call(BarangaySeeder::class);

    if (app()->environment('production')) {
        if (! config('nagasync.seed_demo_users')) {
            return; // demo accounts only when explicitly enabled
        }

        if (config('nagasync.demo_password') === 'password') {
            throw new \RuntimeException(
                'Set DEMO_PASSWORD to something other than "password" before seeding demo users in production.'
            );
        }
    }

    $barangay = Barangay::firstOrFail();

    foreach (User::ROLES as $role) {
        $user = User::firstOrNew(['email' => "{$role}@nagasync.test"]);

        $user->forceFill([
            'name'                   => Str::headline($role) . ' Demo',
            'password'               => config('nagasync.demo_password'),
            'role'                   => $role,
            'phone'                  => '+639170000000',
            'barangay_id'            => $barangay->id,
            'email_verified_at'      => now(),
            'privacy_consent_at'     => now(),
            'privacy_notice_version' => config('nagasync.privacy_notice_version'),
        ])->save();
    }
}
}