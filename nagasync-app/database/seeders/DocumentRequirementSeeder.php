<?php

namespace Database\Seeders;

use App\Models\DocumentRequirement;
use Illuminate\Database\Seeder;

/**
 * Barangay-level requirements. Confirm against the actual Naga City / barangay rules before
 * launch. The City Hall types are in CityDocumentRequirementsSeeder.
 *
 *   upload(): the citizen attaches a file that staff verify
 *   issued(): satisfied by a released NagaSync document of another type (auto-checked), with an
 *             optional upload fallback for paper copies
 * purpose_keys limits a requirement to some purposes (null = every purpose).
 */
class DocumentRequirementSeeder extends Seeder
{
    private const ISSUED_MAX_AGE_DAYS = 180;

    public function run(): void
    {
        // Students (school, scholarship) are not asked for a cedula
        $nonStudent = collect(config('nagasync.clearance_purposes'))
            ->reject(fn ($p) => $p['student'])->keys()->all();

        $id        = $this->upload('Valid government ID', 'Front and back, clear and readable.');

        $catalog = [
            'Barangay Clearance' => [
                $id,
                $this->camera('Recent 1x1 photo', 'Take a live photo with your camera. Plain background, face clearly visible.'),
                $this->issued('Community tax certificate (cedula)', 'Community Tax Certificate',
                    'Link your released cedula, or upload a copy. Not needed for school or scholarship.', true, 365, $nonStudent),
            ],
            'Certificate of Residency' => [$id],
            'Certificate of Indigency' => [
                $id,
                $this->upload('Letter stating purpose', 'Short signed letter explaining why you need the certificate.'),
            ],
            'First-Time Jobseeker Certification' => [
                $id,
                $this->upload('Oath of undertaking', 'Signed oath that this is your first time seeking employment (RA 11261).'),
            ],
            'Business Clearance' => [
                $id,
                $this->upload('DTI / SEC registration', 'Business name registration certificate.'),
                $this->upload('Proof of business location', 'Lease contract or proof of ownership of the premises.'),
                $this->upload('Location sketch', 'Simple hand-drawn or printed map of the business location.', false),
            ],
        ];

        foreach ($catalog as $type => $items) {
            foreach ($items as $i => $item) {
                DocumentRequirement::updateOrCreate(
                    ['document_type' => $type, 'name' => $item['name']],
                    $item + ['is_active' => true, 'sort_order' => $i + 1]
                );
            }

            // This seeder owns these types: retire any rows it no longer defines
            DocumentRequirement::where('document_type', $type)
                ->whereNotIn('name', collect($items)->pluck('name'))
                ->update(['is_active' => false]);
        }
    }

    private function upload(string $name, string $description, bool $required = true, ?array $purposeKeys = null): array
    {
        return [
            'name' => $name, 'description' => $description, 'is_required' => $required,
            'kind' => DocumentRequirement::KIND_UPLOAD, 'satisfied_by_type' => null,
            'allow_upload_fallback' => true, 'max_age_days' => null, 'purpose_keys' => $purposeKeys,
        ];
    }

    /** A photo taken live in the browser. No upload fallback unless $fallback is true (camera blocked or missing). */
    private function camera(string $name, string $description, bool $fallback = false): array
    {
        return [
            'name' => $name, 'description' => $description, 'is_required' => true,
            'kind' => DocumentRequirement::KIND_CAMERA, 'satisfied_by_type' => null,
            'allow_upload_fallback' => $fallback, 'max_age_days' => null, 'purpose_keys' => null,
        ];
    }

    /** $byType is the NagaSync document type that satisfies it. */
    private function issued(string $name, string $byType, string $description, bool $fallback = true,
                            int $days = self::ISSUED_MAX_AGE_DAYS, ?array $purposeKeys = null): array
    {
        return [
            'name' => $name, 'description' => $description, 'is_required' => true,
            'kind' => DocumentRequirement::KIND_ISSUED, 'satisfied_by_type' => $byType,
            'allow_upload_fallback' => $fallback, 'max_age_days' => $days, 'purpose_keys' => $purposeKeys,
        ];
    }
}
