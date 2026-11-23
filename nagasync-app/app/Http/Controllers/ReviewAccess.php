<?php

namespace App\Http\Controllers;

use App\Models\DocumentRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Who may review, assess and release which document request.
 *   admin           : everything
 *   city_staff      : City Hall requests. users.office = null means every office; otherwise only
 *                     requests routed to that office (bplo, treasurer, mayor, cswdo, lingkod)
 *   barangay_staff  : barangay-level requests of their own barangay only
 * Anyone else (citizens, treasury, mdrrmo, ...) has no review access.
 */
class ReviewAccess
{
    public const ROLES = ['admin', 'city_staff', 'barangay_staff'];

    public static function canReview(User $user, DocumentRequest $doc): bool
    {
        return match ($user->role) {
            'admin'          => true,
            'city_staff'     => $doc->isCityLevel() && ($user->office === null || $user->office === $doc->office),
            'barangay_staff' => ! $doc->isCityLevel()
                && $user->barangay_id !== null
                && (int) $doc->barangay_id === (int) $user->barangay_id,
            default          => false,
        };
    }

    /** Restrict a DocumentRequest query to what this user is allowed to review. */
    public static function scopeQueue(User $user, Builder $query): Builder
    {
        return match ($user->role) {
            'admin'          => $query,
            'city_staff'     => $query
                ->where('level', DocumentRequest::LEVEL_CITY)
                ->when($user->office, fn ($q) => $q->where('office', $user->office)),
            'barangay_staff' => $query
                ->where('level', DocumentRequest::LEVEL_BARANGAY)
                ->where('barangay_id', $user->barangay_id ?? 0),
            default          => $query->whereRaw('1 = 0'),
        };
    }
}
