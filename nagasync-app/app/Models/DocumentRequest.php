<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentRequest extends Model
{
    use Auditable;

    public const LEVEL_BARANGAY = 'barangay';
    public const LEVEL_CITY     = 'city';

    // Issued by the resident's barangay
    public const BARANGAY_TYPES = [
        'Barangay Clearance',
        'Certificate of Residency',
        'Certificate of Indigency',
        'First-Time Jobseeker Certification',
        'Business Clearance',
    ];

    // Issued by City Hall. Confirm this list with the city's offices before launch.
    public const CITY_TYPES = [
        'Business Permit (New)',
        'Business Permit (Renewal)',
        'Community Tax Certificate',
        'Certificate of Good Moral Character',
        "Mayor's Clearance",
        'City Certificate of Indigency',
        'Medical Assistance Referral',
    ];

    // City Hall office that handles each city type (users.office for city_staff works the same way)
    public const OFFICE_FOR = [
        'Business Permit (New)'               => 'bplo',
        'Business Permit (Renewal)'           => 'bplo',
        'Community Tax Certificate'           => 'treasurer',
        'Certificate of Good Moral Character' => 'mayor',
        "Mayor's Clearance"                   => 'mayor',
        'City Certificate of Indigency'       => 'cswdo',
        'Medical Assistance Referral'         => 'lingkod',
    ];

    public const OFFICES = ['bplo', 'treasurer', 'mayor', 'cswdo', 'lingkod'];

    // Can be obtained once per resident (RA 11261)
    public const ONCE_ONLY = ['First-Time Jobseeker Certification'];

    // Everything a citizen can request (kept for code that validates against TYPES)
    public const TYPES = [
        ...self::BARANGAY_TYPES,
        ...self::CITY_TYPES,
    ];

    // Documents where "what is it for?" makes no sense (the business is the purpose)
    public const PURPOSELESS_TYPES = [
        'Business Permit (New)',
        'Business Permit (Renewal)',
    ];

    // Allowed status changes: current status => statuses it may move to.
    // approved -> released happens at the City Hall counter (payment collected, document handed over).
    public const TRANSITIONS = [
        'pending'            => ['processing', 'approved', 'rejected', 'needs_resubmission'],
        'processing'         => ['approved', 'rejected', 'needs_resubmission'],
        'needs_resubmission' => ['pending', 'rejected'],
        'approved'           => ['released', 'revoked'],
        'released'           => ['revoked'],
        'rejected'           => [],
        'revoked'            => [],
    ];

    protected $fillable = [
        'user_id', 'barangay_id', 'document_type', 'level', 'purpose', 'tracking_number',
        'status', 'processed_by', 'issued_at', 'remarks',
        // fee assessment and counter release
        'fee_total', 'fee_breakdown', 'fee_computed_at',
        'amount_paid', 'official_receipt_no', 'released_at', 'released_by',
        // office routing, purpose and the details printed on the certificate
        'office', 'purpose_key', 'personal_info', 'signatory_name', 'payment_id',
    ];

    protected $casts = [
        'issued_at'       => 'datetime',
        'fee_breakdown'   => 'array',
        'personal_info'   => 'array',
        'fee_computed_at' => 'datetime',
        'released_at'     => 'datetime',
        'fee_total'       => 'decimal:2',
        'amount_paid'     => 'decimal:2',
    ];

    public static function levelFor(string $type): string
    {
        return in_array($type, self::CITY_TYPES, true) ? self::LEVEL_CITY : self::LEVEL_BARANGAY;
    }

    protected static function booted(): void
    {
        // Level always follows the document type
        static::creating(function (self $doc) {
            $doc->level  = self::levelFor($doc->document_type);
            $doc->office = self::OFFICE_FOR[$doc->document_type] ?? null;
        });

        /**
         * Server-side status gates. They run for every code path that changes status,
         * so no controller, API route or tinker command can skip them.
         */
        static::updating(function (self $doc) {
            if (! $doc->isDirty('status')) {
                return;
            }

            if ($doc->status === 'approved') {
                if (! $doc->requirementsComplete()) {
                    throw ValidationException::withMessages([
                        'status' => 'All required documents must be verified before approval.',
                    ]);
                }

                // City Hall documents are approved with the fee already assessed, so the
                // citizen and the clerk at the counter see the same amount.
                if (! $doc->feeAssessed()) {
                    throw ValidationException::withMessages([
                        'status' => 'The fee must be assessed before this document can be approved.',
                    ]);
                }
            }

            // Only an approved document can be handed over.
            if ($doc->status === 'released' && $doc->getOriginal('status') !== 'approved') {
                throw ValidationException::withMessages([
                    'status' => 'Only an approved document can be released.',
                ]);
            }
        });
    }

    public function user() { return $this->belongsTo(User::class); }
    public function barangay() { return $this->belongsTo(Barangay::class); }
    public function processor() { return $this->belongsTo(User::class, 'processed_by'); }
    public function releaser() { return $this->belongsTo(User::class, 'released_by'); }
    public function attachments() { return $this->hasMany(RequestAttachment::class); }
    public function payment() { return $this->belongsTo(MunicipalPayment::class, 'payment_id'); }

    // ---- queue scopes (keep barangay and City Hall staff queues separate) ----

    public function scopeCityLevel($query)
    {
        return $query->where('level', self::LEVEL_CITY);
    }

    public function scopeBarangayLevel($query)
    {
        return $query->where('level', self::LEVEL_BARANGAY);
    }

    /** Approved and waiting to be claimed at the counter. */
    public function scopeAwaitingRelease($query)
    {
        return $query->where('status', 'approved');
    }

    public function isCityLevel(): bool
    {
        return $this->level === self::LEVEL_CITY;
    }

    public function isReleased(): bool
    {
        return $this->status === 'released';
    }

    public function feeAssessed(): bool
    {
        return $this->fee_total !== null;
    }

    /** A fee above zero means it must be collected (counter) before the document is released. */
    public function requiresPayment(): bool
    {
        return (float) $this->fee_total > 0;
    }

    /**
     * Documents a resident can use as a prerequisite for another request:
     * theirs, of the given type, approved or already released (not revoked),
     * and not older than $maxAgeDays.
     *
     * Released counts too: a document claimed at the counter must keep working as a
     * prerequisite (e.g. a cedula linked into a business permit request).
     */
    public static function usableFor(int $userId, string $type, ?int $maxAgeDays = null)
    {
        $query = static::query()
            ->where('user_id', $userId)
            ->where('document_type', $type)
            ->where('status', 'released');

        if ($maxAgeDays) {
            $cutoff = now()->subDays($maxAgeDays);
            $query->where(function ($w) use ($cutoff) {
                $w->where('issued_at', '>=', $cutoff)
                  ->orWhere(fn ($x) => $x->whereNull('issued_at')->where('updated_at', '>=', $cutoff));
            });
        }

        return $query->latest('id');
    }

    /** Is THIS document currently usable as a prerequisite? */
    public function isUsableAsPrerequisite(?int $maxAgeDays): bool
    {
        if ($this->status !== 'released') {
            return false;
        }
        if (! $maxAgeDays) {
            return true;
        }

        $issued = $this->issued_at ?? $this->updated_at;

        return $issued && $issued->gte(now()->subDays($maxAgeDays));
    }

    /**
     * Active, required requirements for this document type that are not yet satisfied.
     * Satisfied = verified AND, for a linked document, still valid right now.
     */
    public function missingRequirements()
    {
        $satisfiedIds = $this->attachments()
            ->with(['linkedDocument', 'requirement'])
            ->get()
            ->filter(fn ($a) => $a->review_status === 'verified' && $a->isStillValid($this->user_id))
            ->pluck('document_requirement_id');

        return DocumentRequirement::active()
            ->required()
            ->forPurpose($this->purpose_key)
            ->where('document_type', $this->document_type)
            ->whereNotIn('id', $satisfiedIds)
            ->get();
    }

    /**
     * True when every required requirement is satisfied.
     *
     * City Hall documents fail closed: if no requirements are configured for the type
     * there is nothing to verify, so the request cannot be approved. This is what stops a
     * permit from being approved when the requirements seeder has not been run.
     * Barangay types with no configured requirements are still treated as complete.
     */
    public function requirementsComplete(): bool
    {
        if ($this->isCityLevel()
            && ! DocumentRequirement::active()->where('document_type', $this->document_type)->exists()
        ) {
            return false;
        }

        return $this->missingRequirements()->isEmpty();
    }

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