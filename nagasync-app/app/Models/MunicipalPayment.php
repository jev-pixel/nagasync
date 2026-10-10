<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MunicipalPayment extends Model
{
    use Auditable;

    protected $fillable = [
        'reference_no', 'user_id', 'barangay_id', 'payment_type', 'amount', 'convenience_fee',
        'total', 'currency', 'status', 'gateway', 'gateway_ref', 'paid_at', 'meta',
    ];

    protected $casts = ['paid_at' => 'datetime', 'meta' => 'array'];

    public function user() { return $this->belongsTo(User::class); }
    public function barangay() { return $this->belongsTo(Barangay::class); }

    public static function generateReference(): string
    {
        do {
            $ref = 'NSP-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (static::where('reference_no', $ref)->exists());

        return $ref;
    }
}