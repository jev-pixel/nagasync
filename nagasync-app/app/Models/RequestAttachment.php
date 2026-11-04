<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestAttachment extends Model
{
    public const STATUSES = ['pending', 'verified', 'rejected'];

    protected $fillable = [
        'document_request_id', 'document_requirement_id', 'linked_document_request_id',
        'file_path', 'original_name', 'mime_type', 'size',
        'review_status', 'reviewer_remarks', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function documentRequest()
    {
        return $this->belongsTo(DocumentRequest::class);
    }

    public function requirement()
    {
        return $this->belongsTo(DocumentRequirement::class, 'document_requirement_id');
    }

    public function linkedDocument()
    {
        return $this->belongsTo(DocumentRequest::class, 'linked_document_request_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isLinked(): bool
    {
        return $this->linked_document_request_id !== null;
    }

    /**
     * An uploaded file is always "valid" here (staff verification decides).
     * A linked document is valid only while it still belongs to the same resident,
     * is the right type, is approved (not revoked) and is within the allowed age.
     */
    public function isStillValid(?int $ownerId = null): bool
    {
        if (! $this->isLinked()) {
            return true;
        }

        $linked = $this->linkedDocument;
        $req    = $this->requirement;
        $ownerId ??= $this->documentRequest?->user_id;

        return $linked
            && $req
            && $linked->user_id === $ownerId
            && $linked->document_type === $req->satisfied_by_type
            && $linked->isUsableAsPrerequisite($req->max_age_days);
    }

    /** Can the citizen replace this item (re-upload or re-link)? */
    public function isReplaceable(?int $ownerId = null): bool
    {
        return $this->review_status === 'rejected'
            || ($this->isLinked() && ! $this->isStillValid($ownerId));
    }
}
