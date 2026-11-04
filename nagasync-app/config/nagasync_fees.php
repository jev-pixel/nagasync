<?php

return [
    'types' => [
        'Business Permit (New)' => [
            'flat' => [
                ['label' => "Mayor's permit fee",       'amount' => 500.00],
                ['label' => 'Sanitary inspection fee',  'amount' => 100.00],
                ['label' => 'Garbage collection fee',   'amount' => 150.00],
            ],
            // Percentage of the declared capital (new) or gross sales (renewal).
            'rate_on_declared' => ['label' => 'Business tax on declared amount', 'rate' => 0.005],
        ],
        'Business Permit (Renewal)' => [
            'flat' => [
                ['label' => "Mayor's permit fee",       'amount' => 400.00],
                ['label' => 'Sanitary inspection fee',  'amount' => 100.00],
                ['label' => 'Garbage collection fee',   'amount' => 150.00],
            ],
            'rate_on_declared' => ['label' => 'Business tax on declared amount', 'rate' => 0.005],
        ],
        'Community Tax Certificate' => [
            'flat' => [
                ['label' => 'Basic community tax', 'amount' => 5.00],
            ],
            // Additional tax per 1,000 of income, with a cap.
            'per_thousand' => ['label' => 'Additional community tax', 'amount' => 1.00, 'cap' => 5000.00],
        ],
    ],
];