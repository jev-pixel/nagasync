<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\DocumentRequest;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Real configuration, needed in every environment: barangays and the requirement checklists.
        // City Hall documents cannot be approved without their requirements (they fail closed).
        $this->call([
            BarangaySeeder::class,
            DocumentRequirementSeeder::class,
            CityDocumentRequirementsSeeder::class,
        ]);

        if (app()->environment('production')) {
            return; // demo accounts never exist in production
        }

        $barangay = Barangay::firstOrFail();

        // Demo only: a captain's name so the PDF certificates print a signatory
        Barangay::query()->update(['captain_name' => 'Hon. Demo Captain']);

        // One demo account per role. city_staff@ has no office, so it can work every City Hall office.
        foreach (User::ROLES as $role) {
            $this->account("{$role}@nagasync.test", Str::headline($role) . ' Demo', $role, null, $barangay->id);
        }

        // One City Hall clerk per office, to test the office routing
        foreach (DocumentRequest::OFFICES as $office) {
            $this->account("city_{$office}@nagasync.test", 'City ' . Str::upper($office) . ' Demo', 'city_staff', $office, $barangay->id);
        }

        // The demo citizen is already verified by the demo barangay clerk, so documents, polls and
        // payments work straight away. Register a new account to try the verification flow.
        $clerk = User::where('email', 'barangay_staff@nagasync.test')->first();
        User::where('email', 'citizen@nagasync.test')->update([
            'resident_verified_at' => now(),
            'resident_verified_by' => $clerk?->id,
        ]);

        $this->call([DemoMarketSeeder::class, DemoBusinessSeeder::class, DemoPaymentsSeeder::class]);
    }

    private function account(string $email, string $name, string $role, ?string $office, int $barangayId): void
    {
        $user = User::firstOrNew(['email' => $email]);

        // role, office and the flags below are not mass-assignable, so set them explicitly
        $user->forceFill([
            'name'                   => $name,
            'password'               => 'password', // hashed by the model cast
            'role'                   => $role,
            'office'                 => $office,
            'phone'                  => '+639170000000',
            'barangay_id'            => $barangayId,
            'email_verified_at'      => now(),
            'privacy_consent_at'     => now(),
            'privacy_notice_version' => config('nagasync.privacy_notice_version'),
        ])->save();
    }
}
