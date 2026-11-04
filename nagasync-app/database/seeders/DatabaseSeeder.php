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
            return; // demo accounts never exist in production
        }

        $barangay = Barangay::firstOrFail();

        foreach (User::ROLES as $role) {
            $user = User::firstOrNew(['email' => "{$role}@nagasync.test"]);

            // role and the flags below are not mass-assignable, so set them explicitly
            $user->forceFill([
                'name'                   => Str::headline($role) . ' Demo',
                'password'               => 'password', // hashed by the model cast
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