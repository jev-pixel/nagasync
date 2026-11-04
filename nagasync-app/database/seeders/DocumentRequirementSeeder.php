<?php

namespace Database\Seeders;

use App\Models\DocumentRequirement;
use Illuminate\Database\Seeder;

/**
 * Starter requirements. Confirm against the actual Naga City / barangay rules
 * before launch; admins can edit these later without a code change.
 *
 * Two kinds of requirement:
 *   upload(): the citizen attaches a file that staff verify
 *   issued(): satisfied by an approved NagaSync document of another type
 *             (auto-checked), with an optional upload fallback for paper copies
 */
class DocumentRequirementSeeder extends Seeder
{
    private const ISSUED_MAX_AGE_DAYS = 180; // how old a linked barangay/city document may be

    public function run(): void
    {
        $catalog = [
            // ---------------- Barangay level ----------------
            'Barangay Clearance' => [
                $this->upload('Valid government ID', 'Front and back, clear and readable.'),
                $this->upload('Proof of residency', 'Recent utility bill, cedula, or similar showing your barangay address.'),
                $this->upload('Recent 1x1 photo', 'Plain background, face clearly visible.'),
            ],
            'Certificate of Residency' => [
                $this->upload('Valid government ID', 'Front and back, clear and readable.'),
                $this->upload('Proof of residency', 'Recent utility bill, cedula, or similar showing your barangay address.'),
            ],
            'Certificate of Indigency' => [
                $this->upload('Valid government ID', 'Front and back, clear and readable.'),
                $this->upload('Proof of residency', 'Recent utility bill, cedula, or similar showing your barangay address.'),
                $this->upload('Letter stating purpose', 'Short signed letter explaining why you need the certificate.'),
            ],
            'Business Clearance' => [
                $this->upload('Valid government ID', 'Front and back, clear and readable.'),
                $this->upload('DTI / SEC registration', 'Business name registration certificate.'),
                $this->upload('Proof of business location', 'Lease contract or proof of ownership of the premises.'),
                $this->upload('Location sketch', 'Simple hand-drawn or printed map of the business location.', false),
            ],

            // ---------------- City Hall level ----------------
            'Business Permit (New)' => [
                $this->issued('Business Clearance', 'Business Clearance', 'Issued by your barangay through NagaSync.'),
                $this->issued('Barangay Clearance', 'Barangay Clearance', 'Owner\'s clearance, issued through NagaSync.'),
                $this->issued('Community Tax Certificate', 'Community Tax Certificate', 'Cedula for the current year.'),
                $this->upload('Valid government ID', 'Owner\'s ID, front and back.'),
                $this->upload('DTI / SEC registration', 'Business name registration certificate.'),
                $this->upload('Lease contract or proof of ownership', 'Of the business premises.'),
                $this->upload('Fire safety inspection certificate', 'From the fire station covering the premises.'),
                $this->upload('Sanitary permit', 'From the city health office, if applicable to your business.'),
                $this->upload('Photos of the establishment', 'Front of the business and signage.', false),
            ],
            'Business Permit (Renewal)' => [
                $this->upload('Previous business permit', 'Last year\'s permit, clear photo or scan.'),
                $this->issued('Business Clearance', 'Business Clearance', 'Issued by your barangay through NagaSync.'),
                $this->issued('Community Tax Certificate', 'Community Tax Certificate', 'Cedula for the current year.'),
                $this->upload('Valid government ID', 'Owner\'s ID, front and back.'),
            ],
            'Community Tax Certificate' => [
                $this->issued('Certificate of Residency', 'Certificate of Residency', 'Issued by your barangay through NagaSync.'),
                $this->upload('Valid government ID', 'Front and back, clear and readable.'),
            ],
        ];

        foreach ($catalog as $type => $items) {
            foreach ($items as $i => $item) {
                DocumentRequirement::updateOrCreate(
                    ['document_type' => $type, 'name' => $item['name']],
                    $item + ['is_active' => true, 'sort_order' => $i + 1]
                );
            }
        }
    }

    private function upload(string $name, string $description, bool $required = true): array
    {
        return [
            'name'                  => $name,
            'description'           => $description,
            'is_required'           => $required,
            'kind'                  => DocumentRequirement::KIND_UPLOAD,
            'satisfied_by_type'     => null,
            'allow_upload_fallback' => true,
            'max_age_days'          => null,
        ];
    }

    /** $name is what the citizen sees; $byType is the NagaSync document type that satisfies it. */
    private function issued(string $name, string $byType, string $description, bool $required = true): array
    {
        return [
            'name'                  => $name,
            'description'           => $description,
            'is_required'           => $required,
            'kind'                  => DocumentRequirement::KIND_ISSUED,
            'satisfied_by_type'     => $byType,
            'allow_upload_fallback' => true,
            'max_age_days'          => self::ISSUED_MAX_AGE_DAYS,
        ];
    }
}
