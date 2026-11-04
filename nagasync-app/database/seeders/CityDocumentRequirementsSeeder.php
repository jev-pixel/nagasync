<?php

namespace Database\Seeders;

use App\Models\DocumentRequirement;
use Illuminate\Database\Seeder;

class CityDocumentRequirementsSeeder extends Seeder
{
    public function run(): void
    {
        $upload = fn (string $type, string $name, string $desc, int $order, bool $required = true) => [
            'document_type' => $type, 'name' => $name, 'description' => $desc,
            'is_required' => $required, 'kind' => 'upload', 'satisfied_by_type' => null,
            'allow_upload_fallback' => false, 'max_age_days' => null,
            'sort_order' => $order, 'is_active' => true,
        ];

        // A document already issued through NagaSync can be linked instead of uploaded.
        $issued = fn (string $type, string $name, string $desc, string $satisfiedBy, int $days, int $order, bool $fallback) => [
            'document_type' => $type, 'name' => $name, 'description' => $desc,
            'is_required' => true, 'kind' => DocumentRequirement::KIND_ISSUED,
            'satisfied_by_type' => $satisfiedBy, 'allow_upload_fallback' => $fallback,
            'max_age_days' => $days, 'sort_order' => $order, 'is_active' => true,
        ];

        $new = 'Business Permit (New)';
        $renew = 'Business Permit (Renewal)';
        $ctc = 'Community Tax Certificate';

        $rows = [
            $upload($new, 'Valid ID of the owner', 'Government-issued ID, clear photo or scan.', 1),
            $upload($new, 'DTI or SEC registration', 'DTI for sole proprietors, SEC for corporations and partnerships.', 2),
            $issued($new, 'Barangay business clearance', 'Link your approved barangay clearance (max 6 months old).', 'Business Clearance', 180, 3, false),
            $upload($new, 'Proof of business location', 'Lease contract, or land title / tax declaration if you own the site.', 4),
            $issued($new, 'Community tax certificate (cedula)', 'Link your approved cedula, or upload a copy.', $ctc, 365, 5, true),
            $upload($new, 'Sketch or photo of the establishment', 'Helps inspectors locate the business.', 6, false),

            $upload($renew, 'Valid ID of the owner', 'Government-issued ID, clear photo or scan.', 1),
            $upload($renew, "Last year's business permit", 'Copy of the permit you are renewing.', 2),
            $issued($renew, 'Barangay business clearance', 'Link your approved barangay clearance (max 6 months old).', 'Business Clearance', 180, 3, false),
            $upload($renew, 'Sworn statement of gross sales', 'Gross sales for the previous year, or audited financial statements.', 4),
            $issued($renew, 'Community tax certificate (cedula)', 'Link your approved cedula, or upload a copy.', $ctc, 365, 5, true),

            $upload($ctc, 'Valid ID', 'Government-issued ID, clear photo or scan.', 1),
            $upload($ctc, 'Proof of income', 'Optional. Only needed if the additional community tax applies.', 2, false),
        ];

        foreach ($rows as $row) {
            DocumentRequirement::updateOrCreate(
                ['document_type' => $row['document_type'], 'name' => $row['name']],
                $row
            );
        }
    }
}