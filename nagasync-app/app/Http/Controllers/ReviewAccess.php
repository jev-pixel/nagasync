<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Who may review which document request.
 *   admin           : everything
 *   city_staff      : City Hall requests, from any barangay
 *   barangay_staff  : barangay requests of their own barangay only
 * Anyone else (citizens, treasury, mdrrmo, ...) has no review access.
 *
 * NOTE: compare with your existing ReviewAccess.php before replacing it. If yours has other
 * methods that are used elsewhere, keep them and only adopt canReview() / scopeQueue().
 */
class ReviewAccess
{
    public const ROLES = ['admin', 'city_staff', 'barangay_staff'];

    public static function canReview(User $user, DocumentRequest $doc): bool
    {
        return match ($user->role) {
            'admin'          => true,
            'city_staff'     => $doc->isCityLevel(),
            'barangay_staff' => ! $doc->isCityLevel()
                && $user->barangay_id !== null
                && $doc->barangay_id === $user->barangay_id,
            default          => false,
        };
    }

    /** Restrict a DocumentRequest query to what this user is allowed to review. */
    public static function scopeQueue(User $user, Builder $query): Builder
    {
        return match ($user->role) {
            'admin'          => $query,
            'city_staff'     => $query->where('level', DocumentRequest::LEVEL_CITY),
            'barangay_staff' => $query
                ->where('level', DocumentRequest::LEVEL_BARANGAY)
                ->where('barangay_id', $user->barangay_id ?? 0),
            default          => $query->whereRaw('1 = 0'),
        };
    }
}