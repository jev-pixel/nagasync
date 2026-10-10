<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class DocumentRequest extends Model
{
    use Auditable;

    public const TYPES = [
        'Barangay Clearance',
        'Certificate of Residency',
        'Certificate of Indigency',
        'Business Clearance',
    ];

    // Allowed status changes: current status => statuses it may move to
    public const TRANSITIONS = [
        'pending'    => ['processing', 'approved', 'rejected'],
        'processing' => ['approved', 'rejected'],
        'approved'   => ['revoked'],
        'rejected'   => [],
        'revoked'    => [],
    ];

    protected $fillable = [
        'user_id', 'barangay_id', 'document_type', 'purpose', 'tracking_number',
        'status', 'processed_by', 'issued_at', 'remarks',
    ];

    protected $casts = ['issued_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
    public function barangay() { return $this->belongsTo(Barangay::class); }
    public function processor() { return $this->belongsTo(User::class, 'processed_by'); }

    public function verificationUrl(): string
    {
        return URL::signedRoute('documents.verify', ['documentRequest' => $this->tracking_number]);
    }

    public static function generateTrackingNumber(): string
    {
        do {
            $number = 'NS-' . now()->format('Y') . '-' . strtoupper(Str::random(8));
        } while (static::where('tracking_number', $number)->exists());

        return $number;
    }
}