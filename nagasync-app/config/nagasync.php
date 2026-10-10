<?php

return [
    // Convenience fee per online transaction, in centavos (your deck says PHP 10-20)
    'convenience_fee' => 1500,
    'privacy_notice_version' => '2026-10-draft',

    // DEMO amounts in centavos. Replace with the LGU's official fee schedule.
    'payment_types' => [
        'barangay_clearance_fee' => ['label' => 'Barangay Clearance Fee', 'amount' => 5000],
        'business_permit_fee'    => ['label' => 'Business Permit Fee',    'amount' => 150000],
        'market_stall_fee'       => ['label' => 'Market Stall Fee',       'amount' => 20000],
        'community_tax'          => ['label' => 'Community Tax',          'amount' => 5500],
    ],

    'payments' => [
        'gateway' => env('PAYMENT_GATEWAY', 'sandbox'),
    ],

    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
    ],

    'market_name' => env('MARKET_NAME', 'Public Market'),

    'analytics' => [
        'url' => env('ANALYTICS_URL', 'http://127.0.0.1:8001'),
        'key' => env('ANALYTICS_KEY'),
    ],

    'retention' => [
        'emergency_location_days' => 90,   // strip coordinates/note from closed alerts after this long
        'audit_log_days'          => 730,  // delete audit entries older than this
    ],

    // Demo accounts: always seeded locally; in production only when explicitly enabled.
    'seed_demo_users' => env('SEED_DEMO_USERS', false),
    'demo_password'   => env('DEMO_PASSWORD', 'password'),
];