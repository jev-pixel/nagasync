<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Citizens must be verified by barangay staff (users.resident_verified_at) before they can request
 * documents, pay, vote or list a business. Staff and other roles are never gated.
 * Unverified citizens are sent to the Guide page (news, guidelines, how to request).
 */
class EnsureResidentVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'citizen' || $user->resident_verified_at !== null) {
            return $next($request);
        }

        abort_if($request->expectsJson(), 403, 'Resident verification required.');

        return redirect()->route('guide.index');
    }
}