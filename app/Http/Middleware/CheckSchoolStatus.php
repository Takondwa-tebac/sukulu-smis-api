<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSchoolStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Super admins (no school_id) bypass this check
        if (!$user || !$user->school_id) {
            return $next($request);
        }

        $school = $user->school;

        if (!$school) {
            return response()->json([
                'message' => 'School not found.',
            ], 404);
        }

        // Check if school is suspended
        if ($school->isSuspended()) {
            return response()->json([
                'message' => 'Your school account has been suspended. Please contact support.',
                'status' => 'suspended',
            ], 403);
        }

        // Check if school is pending activation
        if ($school->status === 'pending') {
            return response()->json([
                'message' => 'Your school account is pending activation.',
                'status' => 'pending',
            ], 403);
        }

        // Check subscription validity
        if (!$school->hasValidSubscription()) {
            return response()->json([
                'message' => 'Your school subscription has expired. Please renew to continue.',
                'status' => 'subscription_expired',
                'expired_at' => $school->subscription_expires_at?->toISOString(),
            ], 402); // 402 Payment Required
        }

        return $next($request);
    }
}
