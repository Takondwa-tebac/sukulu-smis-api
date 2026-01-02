<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\SchoolResource;
use App\Models\School;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Admin - Subscriptions
 *
 * APIs for managing school subscriptions and billing.
 * These endpoints require the `super-admin` role.
 */
class AdminSubscriptionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:subscriptions.view');
        $this->middleware('permission:subscriptions.manage')->only(['update', 'extend']);
    }

    /**
     * Get subscription statistics
     *
     * Get overall subscription statistics for the platform.
     *
     * @authenticated
     * @response 200 scenario="Success" {"total_schools": 150, "active_subscriptions": 120, ...}
     */
    public function stats(): JsonResponse
    {
        $totalSchools = School::count();
        $activeSubscriptions = School::where(function ($query) {
            $query->whereNull('subscription_expires_at')
                  ->orWhere('subscription_expires_at', '>', now());
        })->count();

        $expiringThisMonth = School::whereMonth('subscription_expires_at', now()->month)
            ->whereYear('subscription_expires_at', now()->year)
            ->where('subscription_expires_at', '>', now())
            ->count();

        $expiredSubscriptions = School::where('subscription_expires_at', '<', now())->count();

        // Calculate total students across all schools
        $totalStudents = Student::count();

        // Estimated monthly revenue (assuming MWK 500 per student per term)
        $estimatedMonthlyRevenue = $totalStudents * 500;

        return response()->json([
            'total_schools' => $totalSchools,
            'active_subscriptions' => $activeSubscriptions,
            'expiring_this_month' => $expiringThisMonth,
            'expired_subscriptions' => $expiredSubscriptions,
            'total_students' => $totalStudents,
            'estimated_monthly_revenue' => $estimatedMonthlyRevenue,
        ]);
    }

    /**
     * Get all subscriptions
     *
     * Get a list of all school subscriptions with their details.
     *
     * @authenticated
     * @queryParam per_page integer Number of items per page. Example: 15
     * @queryParam page integer Page number. Example: 1
     * @queryParam search string Search by school name or code. Example: "St. Mary's"
     * @queryParam status string Filter by subscription status. Example: "active"
     * @queryParam plan string Filter by subscription plan. Example: "premium"
     *
     * @response 200 scenario="Success" {"data": [...], "meta": {...}}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = School::withCount('students');

        // Search by school name or code
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $status = $request->input('status');
            switch ($status) {
                case 'active':
                    $query->where(function ($q) {
                        $q->whereNull('subscription_expires_at')
                          ->orWhere('subscription_expires_at', '>', now());
                    });
                    break;
                case 'expired':
                    $query->where('subscription_expires_at', '<', now());
                    break;
                case 'expiring':
                    $query->whereMonth('subscription_expires_at', now()->month)
                          ->whereYear('subscription_expires_at', now()->year)
                          ->where('subscription_expires_at', '>', now());
                    break;
            }
        }

        // Filter by plan
        if ($request->filled('plan')) {
            $query->where('subscription_plan', $request->input('plan'));
        }

        $schools = $query->paginate($request->input('per_page', 15));

        // Transform the data to include subscription information
        $data = $schools->getCollection()->map(function ($school) {
            $subscriptionStatus = $this->getSubscriptionStatus($school);
            $studentCount = $school->students_count;
            
            // Calculate estimated monthly cost (MWK 500 per student per term)
            $estimatedMonthlyCost = $studentCount * 500;
            
            // Calculate next billing date
            $nextBillingDate = $school->subscription_expires_at 
                ? $school->subscription_expires_at->addMonth() 
                : now()->addMonth();

            return [
                'id' => $school->id,
                'school' => [
                    'id' => $school->id,
                    'name' => $school->name,
                    'code' => $school->code,
                    'type' => $school->type,
                    'city' => $school->city,
                    'region' => $school->region,
                ],
                'subscription_plan' => $school->subscription_plan ?: 'basic',
                'subscription_expires_at' => $school->subscription_expires_at?->toISOString(),
                'student_count' => $studentCount,
                'status' => $subscriptionStatus,
                'estimated_monthly_cost' => $estimatedMonthlyCost,
                'last_payment_date' => null, // TODO: Implement payment tracking
                'next_billing_date' => $nextBillingDate->toISOString(),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $schools->currentPage(),
                'last_page' => $schools->lastPage(),
                'per_page' => $schools->perPage(),
                'total' => $schools->total(),
            ],
        ]);
    }

    /**
     * Get subscription details
     *
     * Get detailed information about a specific school's subscription.
     *
     * @authenticated
     * @urlParam school string The ID of the school. Example: "uuid"
     *
     * @response 200 scenario="Success" {"school": {...}, "subscription": {...}}
     */
    public function show(Request $request, string $schoolId): JsonResponse
    {
        $school = School::withCount('students')->findOrFail($schoolId);

        $subscriptionStatus = $this->getSubscriptionStatus($school);
        $studentCount = $school->students_count;
        $estimatedMonthlyCost = $studentCount * 500;

        return response()->json([
            'school' => [
                'id' => $school->id,
                'name' => $school->name,
                'code' => $school->code,
                'type' => $school->type,
                'address' => $school->address,
                'city' => $school->city,
                'region' => $school->region,
                'country' => $school->country,
                'phone' => $school->phone,
                'email' => $school->email,
                'established_year' => $school->established_year,
                'registration_number' => $school->registration_number,
            ],
            'subscription' => [
                'plan' => $school->subscription_plan ?: 'basic',
                'expires_at' => $school->subscription_expires_at?->toISOString(),
                'status' => $subscriptionStatus,
                'student_count' => $studentCount,
                'estimated_monthly_cost' => $estimatedMonthlyCost,
                'next_billing_date' => $school->subscription_expires_at?->addMonth()->toISOString(),
                'is_active' => $school->hasValidSubscription(),
            ],
            'billing_history' => [], // TODO: Implement billing history
        ]);
    }

    /**
     * Update subscription
     *
     * Update a school's subscription plan and expiry date.
     *
     * @authenticated
     * @urlParam school string The ID of the school. Example: "uuid"
     * @bodyParam plan string required Subscription plan. Example: "premium"
     * @bodyParam expires_at date required Subscription expiry date. Example: "2024-12-31"
     *
     * @response 200 scenario="Success" {"message": "Subscription updated successfully."}
     */
    public function update(Request $request, string $schoolId): JsonResponse
    {
        $school = School::findOrFail($schoolId);

        $validated = $request->validate([
            'plan' => ['required', 'string', 'in:basic,standard,premium,enterprise'],
            'expires_at' => ['required', 'date', 'after:today'],
        ]);

        $school->update([
            'subscription_plan' => $validated['plan'],
            'subscription_expires_at' => $validated['expires_at'],
        ]);

        return response()->json([
            'message' => 'Subscription updated successfully.',
            'subscription' => [
                'plan' => $school->subscription_plan,
                'expires_at' => $school->subscription_expires_at->toISOString(),
                'status' => $this->getSubscriptionStatus($school),
            ],
        ]);
    }

    /**
     * Extend subscription
     *
     * Extend a school's subscription by a specified number of months.
     *
     * @authenticated
     * @urlParam school string The ID of the school. Example: "uuid"
     * @bodyParam months integer required Number of months to extend. Example: 3
     *
     * @response 200 scenario="Success" {"message": "Subscription extended successfully."}
     */
    public function extend(Request $request, string $schoolId): JsonResponse
    {
        $school = School::findOrFail($schoolId);

        $validated = $request->validate([
            'months' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $currentExpiry = $school->subscription_expires_at ?: now();
        $newExpiry = $currentExpiry->addMonths($validated['months']);

        $school->update([
            'subscription_expires_at' => $newExpiry,
        ]);

        return response()->json([
            'message' => 'Subscription extended successfully.',
            'subscription' => [
                'expires_at' => $school->subscription_expires_at->toISOString(),
                'status' => $this->getSubscriptionStatus($school),
            ],
        ]);
    }

    /**
     * Get subscription status for a school
     */
    private function getSubscriptionStatus(School $school): string
    {
        if (!$school->subscription_expires_at) {
            return 'trial';
        }

        if ($school->subscription_expires_at->isPast()) {
            return 'expired';
        }

        if ($school->subscription_expires_at->diffInDays(now()) <= 30) {
            return 'expiring';
        }

        return 'active';
    }
}
