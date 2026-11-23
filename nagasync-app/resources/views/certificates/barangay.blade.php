<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $doc->document_type }} {{ $doc->tracking_number }}</title>
<style>
    @page { margin: 36px 48px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #1b1340; line-height: 1.6; }
    .center { text-align: center; }
    .small { font-size: 10px; color: #555; }
    h1 { font-size: 20px; letter-spacing: 2px; margin: 22px 0 4px; text-transform: uppercase; }
    .rule { border-top: 3px double #3d2470; margin: 10px 0 0; }
    .photo { float: right; width: 96px; height: 96px; border: 1px solid #999; margin: 0 0 8px 16px; object-fit: cover; }
    p.body { text-align: justify; margin: 14px 0; }
    table.info { border-collapse: collapse; margin: 12px 0; width: 100%; }
    table.info td { padding: 3px 6px; border-bottom: 1px solid #e5e5ef; vertical-align: top; }
    table.info td.k { width: 36%; color: #555; }
    .sign { margin-top: 56px; text-align: right; }
    .sign img { height: 56px; margin-bottom: -16px; } /* the signature sits on the line */
    .sign .line { display: inline-block; min-width: 200px; border-top: 1px solid #000; padding-top: 2px; text-align: center; font-weight: bold; }
    .foot { margin-top: 36px; border-top: 1px solid #ccc; padding-top: 8px; }
    .foot img { float: left; width: 80px; height: 80px; margin-right: 12px; }
</style>
</head>
<body>
<div class="center">
    <div class="small">Republic of the Philippines</div>
    <div><strong>City of Naga</strong></div>
    <div><strong>Barangay {{ $barangay }}</strong></div>
    <div class="rule"></div>
    <h1>{{ $doc->document_type }}</h1>
</div>

@php
    // Built here rather than inline: Blade skips a directive written right after a letter
    $address   = $raw['address'] ?? '—';
    $civil     = !empty($raw['civil_status']) ? ', ' . strtolower($raw['civil_status']) : '';
    $years     = !empty($raw['years_of_residency']) ? ', and has resided in this barangay for ' . $raw['years_of_residency'] . ' year(s)' : '';
    $household = !empty($raw['household_members']) ? ', with ' . $raw['household_members'] . ' household member(s)' : '';
    $income    = !empty($raw['monthly_income']) ? ' and a monthly household income of PHP ' . number_format((float) $raw['monthly_income'], 2) : '';
@endphp

@if ($photo)<img class="photo" src="{{ $photo }}" alt="">@endif

<p class="body"><strong>TO WHOM IT MAY CONCERN:</strong></p>

@switch($doc->document_type)
    @case('Barangay Clearance')
        <p class="body">This is to certify that <strong>{{ $name }}</strong>{{ $civil }}, a resident of {{ $address }}, Barangay {{ $barangay }}, City of Naga, has no derogatory record on file in this barangay as of the date below.</p>
        <p class="body">This clearance is issued upon the request of the above-named person for <strong>{{ $doc->purpose }}</strong> purposes.</p>
        @break
    @case('Certificate of Residency')
        <p class="body">This is to certify that <strong>{{ $name }}</strong> is a bona fide resident of {{ $address }}, Barangay {{ $barangay }}, City of Naga{{ $years }}.</p>
        <p class="body">This certification is issued upon request for <strong>{{ $doc->purpose }}</strong>.</p>
        @break
    @case('Certificate of Indigency')
        <p class="body">This is to certify that <strong>{{ $name }}</strong>, a resident of {{ $address }}, Barangay {{ $barangay }}, City of Naga, belongs to an indigent family in this barangay{{ $household }}{{ $income }}.</p>
        <p class="body">This certification is issued free of charge for <strong>{{ $doc->purpose }}</strong>.</p>
        @break
    @case('First-Time Jobseeker Certification')
        <p class="body">This is to certify that <strong>{{ $name }}</strong>, a resident of {{ $address }}, Barangay {{ $barangay }}, City of Naga, is a first-time jobseeker and qualifies for the benefits of Republic Act No. 11261 (First Time Jobseekers Assistance Act). This certification is valid only once and is issued free of charge.</p>
        @break
    @case('Business Clearance')
        <p class="body">This is to certify that the business <strong>{{ $raw['business_name'] ?? '—' }}</strong> ({{ $raw['business_nature'] ?? 'nature of business not stated' }}), located at {{ $raw['business_address'] ?? '—' }}, Barangay {{ $barangay }}, City of Naga, and owned by <strong>{{ $name }}</strong>, has no objection from this barangay to operate within its jurisdiction.</p>
        <p class="body">The barangay clearance fee is collected by the City Treasurer together with the business permit.</p>
        @break
    @default
        <p class="body">This is to certify that <strong>{{ $name }}</strong> is a resident of Barangay {{ $barangay }}, City of Naga.</p>
@endswitch

@if ($info->isNotEmpty())
<table class="info">
    @foreach ($info as $label => $value)
        <tr><td class="k">{{ $label }}</td><td>{{ $value }}</td></tr>
    @endforeach
</table>
@endif

<p class="body">Issued on <strong>{{ $issued }}</strong> at Barangay {{ $barangay }}, City of Naga.
@if ($doc->official_receipt_no) &nbsp;OR no. {{ $doc->official_receipt_no }}, PHP {{ number_format((float) $doc->amount_paid, 2) }}.@endif</p>

<div class="sign">
    @if ($signature)<img src="{{ $signature }}" alt=""><br>@endif
    <span class="line">{{ $captain ?: '________________' }}</span><br>
    <span class="small">Punong Barangay</span>
</div>

<div class="foot">
    <img src="{{ $qr }}" alt="">
    <div class="small">
        Tracking no. <strong>{{ $doc->tracking_number }}</strong><br>
        Scan the QR code to confirm this document is genuine and still valid.<br>
        Computer-generated by NagaSync. Not valid if the verification page does not show it as issued.
    </div>
</div>
</body>
</html>
