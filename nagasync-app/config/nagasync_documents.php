<?php

/*
 * Personal details asked for on each document request (stored in document_requests.personal_info).
 * Field: key, label, type (text|date|number|select), required, options (select only).
 */
$f = fn (string $key, string $label, string $type = 'text', bool $required = true, array $options = [])
    => compact('key', 'label', 'type', 'required', 'options');

$birth   = $f('birth_date', 'Date of birth', 'date');
$civil   = $f('civil_status', 'Civil status', 'select', true, ['Single', 'Married', 'Widowed', 'Separated']);
$address = $f('address', 'Street / zone address');
$income  = [
    $f('household_members', 'Household members', 'number'),
    $f('monthly_income', 'Monthly household income (PHP)', 'number'),
];
$biz = [
    $f('business_name', 'Business name'),
    $f('business_address', 'Business address'),
    $f('business_nature', 'Nature of business'),
];

return [
    'personal_info' => [
        // Barangay level
        'Barangay Clearance'                 => [$birth, $civil, $address],
        'Certificate of Residency'           => [$address, $f('years_of_residency', 'Years of residency', 'number')],
        'Certificate of Indigency'           => [$address, ...$income],
        'First-Time Jobseeker Certification' => [$birth, $civil, $address],
        'Business Clearance'                 => $biz,

        // City Hall level
        'Business Permit (New)'              => $biz,
        'Business Permit (Renewal)'          => $biz,
        'Community Tax Certificate'          => [$birth, $civil, $f('occupation', 'Occupation')],
        'Certificate of Good Moral Character' => [$birth, $address],
        "Mayor's Clearance"                  => [$birth, $address, $f('destination', 'Destination country / agency', 'text', false)],
        'City Certificate of Indigency'      => [$address, ...$income],
        'Medical Assistance Referral'        => [$f('patient_name', 'Patient name'), $f('hospital', 'Hospital / clinic', 'text', false)],
    ],
];
