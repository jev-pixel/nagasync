<?php

/*
 * Fee rules per document type. DEMO amounts in pesos: confirm with the LGU before launch.
 *
 *   flat                   fixed lines
 *   by_purpose             fee depends on the purpose the citizen picks. Either an inline table
 *                          (key => label/fee) or the name of a config key holding one.
 *   rate_on_declared       percentage of a declared amount (staff enter it)
 *   per_thousand           additional amount per 1,000 declared, with a cap
 *   accepts_clearance_fee  staff may add the barangay business clearance fee as a line
 *                          (collected by the Treasurer together with the permit, Ord. 2021-071)
 *
 * A type with NO declared-amount rule has its fee frozen the moment the request is submitted.
 * An empty array means the document is free.
 */
return [
    'types' => [
        // ---------------- Barangay level ----------------
        'Barangay Clearance'                 => ['by_purpose' => 'nagasync.clearance_purposes'],
        'Certificate of Residency'           => ['flat' => [['label' => 'Certification fee', 'amount' => 50.00]]],
        'Certificate of Indigency'           => [],   // free
        'First-Time Jobseeker Certification' => [],   // free (RA 11261)
        'Business Clearance'                 => [],   // charged as a line in the permit fee

        // ---------------- City Hall level ----------------
        'Business Permit (New)' => [
            'flat' => [
                ['label' => "Mayor's permit fee",      'amount' => 500.00],
                ['label' => 'Sanitary inspection fee', 'amount' => 100.00],
                ['label' => 'Garbage collection fee',  'amount' => 150.00],
            ],
            'rate_on_declared'      => ['label' => 'Business tax on declared amount', 'rate' => 0.005],
            'accepts_clearance_fee' => true,
        ],
        'Business Permit (Renewal)' => [
            'flat' => [
                ['label' => "Mayor's permit fee",      'amount' => 400.00],
                ['label' => 'Sanitary inspection fee', 'amount' => 100.00],
                ['label' => 'Garbage collection fee',  'amount' => 150.00],
            ],
            'rate_on_declared'      => ['label' => 'Business tax on declared amount', 'rate' => 0.005],
            'accepts_clearance_fee' => true,
        ],
        'Community Tax Certificate' => [
            'flat'         => [['label' => 'Basic community tax', 'amount' => 5.00]],
            'per_thousand' => ['label' => 'Additional community tax', 'amount' => 1.00, 'cap' => 5000.00],
        ],
        'Certificate of Good Moral Character' => [
            'flat' => [['label' => 'Good moral certificate', 'amount' => 50.00]],
        ],
        "Mayor's Clearance" => [
            'by_purpose' => [
                'firearms'      => ['label' => 'Firearms license', 'fee' => 200],
                'passport_visa' => ['label' => 'Passport / visa',  'fee' => 200],
                'overseas_work' => ['label' => 'Overseas work',    'fee' => 100],
            ],
        ],
        'City Certificate of Indigency' => [],   // free (CSWDO)
        'Medical Assistance Referral'   => [],   // free (Lingkod Barangay)
    ],
];
