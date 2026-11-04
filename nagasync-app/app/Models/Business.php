<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Business extends Model
{
    use Auditable;

    public const CATEGORIES = ['cafe', 'restaurant', 'shop', 'services', 'accommodation', 'attraction', 'tours', 'health', 'other'];

    public const TAGS = ['wifi', 'air-conditioned', 'family-friendly', 'pet-friendly', 'parking',
                         'accepts-gcash', 'delivery', 'local-products', 'pwd-accessible'];

    // Changing any of these sends a live listing back for review
    public const MATERIAL = ['name', 'category', 'description', 'address', 'barangay_id', 'latitude', 'longitude'];

    public const TRANSITIONS = [
        'pending'   => ['approved', 'rejected'],
        'approved'  => ['suspended'],
        'suspended' => ['approved'],
        'rejected'  => [], // the owner edits and resubmits, which makes it pending again
    ];

    // Mirror the database defaults so a freshly created model has them without a refresh
    protected $attributes = ['status' => 'pending', 'is_featured' => false];

    // status, is_featured, owner_id, slug, cover_path and reviewed_* are set explicitly, never mass-assigned
    protected $fillable = [
        'name', 'category', 'tagline', 'description', 'barangay_id', 'address', 'latitude', 'longitude',
        'phone', 'email', 'website', 'facebook_url', 'hours', 'price_range', 'tags', 'permit_no',
    ];

    protected $casts = [
        'tags' => 'array', 'latitude' => 'float', 'longitude' => 'float',
        'is_featured' => 'boolean', 'reviewed_at' => 'datetime',
    ];

    public array $auditHidden = ['permit_no'];

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function owner() { return $this->belongsTo(User::class, 'owner_id'); }
    public function barangay() { return $this->belongsTo(Barangay::class); }
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'listing';
        $slug = $base;

        for ($i = 2; static::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }
}