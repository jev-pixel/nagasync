<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentRequirement extends Model
{
    public const KIND_UPLOAD = 'upload';
    public const KIND_ISSUED = 'issued_document';

    protected $fillable = [
        'document_type', 'name', 'description', 'kind', 'satisfied_by_type',
        'allow_upload_fallback', 'max_age_days', 'is_required', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_required'           => 'boolean',
        'is_active'             => 'boolean',
        'allow_upload_fallback' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
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
