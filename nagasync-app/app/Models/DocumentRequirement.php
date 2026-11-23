<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentRequirement extends Model
{
    public const KIND_UPLOAD = 'upload';
    public const KIND_ISSUED = 'issued_document';
    public const KIND_CAMERA = 'camera'; // live photo taken in the browser, no file picker

    protected $fillable = [
        'document_type', 'name', 'description', 'kind', 'satisfied_by_type',
        'allow_upload_fallback', 'max_age_days', 'is_required', 'is_active', 'sort_order', 'purpose_keys',
    ];

    protected $casts = [
        'is_required'           => 'boolean',
        'is_active'             => 'boolean',
        'allow_upload_fallback' => 'boolean',
        'purpose_keys'          => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    /** No purpose list = applies to every purpose; otherwise the chosen purpose must be listed. */
    public function scopeForPurpose($query, ?string $purposeKey)
    {
        return $query->where(function ($w) use ($purposeKey) {
            $w->whereNull('purpose_keys');

            if ($purposeKey) {
                $w->orWhereJsonContains('purpose_keys', $purposeKey);
            }
        });
    }

    public function isCameraKind(): bool
    {
        return $this->kind === self::KIND_CAMERA;
    }

    public function isIssuedKind(): bool
    {
        return $this->kind === self::KIND_ISSUED;
    }

    public function attachments()
    {
        return $this->hasMany(RequestAttachment::class);
    }
}
