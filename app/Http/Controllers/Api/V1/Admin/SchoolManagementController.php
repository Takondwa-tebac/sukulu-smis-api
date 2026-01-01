<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OnboardSchoolRequest;
use App\Http\Resources\Admin\SchoolResource;
use App\Jobs\SendWelcomeEmailJob;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @group Admin - School Management
 *
 * APIs for super-admin to manage schools (onboarding, activation, suspension, modules).
 * These endpoints require the `super-admin` role.
 */
class SchoolManagementController extends Controller
{
    /**
     * List all schools
     *
     * Get a paginated list of all schools in the system.
     *
     * @authenticated
     * @queryParam status string Filter by status (active, inactive, suspended). Example: active
     * @queryParam type string Filter by school type. Example: secondary
     * @queryParam search string Search by school name. Example: Academy
     * @queryParam per_page integer Items per page. Default: 15. Example: 20
     *
     * @response 200 scenario="Success" {"data": [], "links": {}, "meta": {}}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = School::query()
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->withCount('users')
            ->orderByDesc('created_at');

        return SchoolResource::collection($query->paginate($request->input('per_page', 15)));
    }

    public function show(School $school): SchoolResource
    {
        return new SchoolResource($school->loadCount('users'));
    }

    public function onboard(OnboardSchoolRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = DB::transaction(function () use ($validated) {
            // Create school
            $school = School::create([
                'name' => $validated['school_name'],
                'type' => $validated['school_type'],
                'address' => $validated['school_address'] ?? null,
                'city' => $validated['school_city'] ?? null,
                'region' => $validated['school_region'] ?? null,
                'country' => $validated['school_country'] ?? 'Malawi',
                'phone' => $validated['school_phone'] ?? null,
                'email' => $validated['school_email'] ?? null,
                'website' => $validated['school_website'] ?? null,
                'motto' => $validated['school_motto'] ?? null,
                'established_year' => $validated['school_established_year'] ?? null,
                'registration_number' => $validated['school_registration_number'] ?? null,
                'status' => School::STATUS_ACTIVE,
                'subscription_plan' => $validated['subscription_plan'] ?? 'basic',
                'subscription_expires_at' => isset($validated['subscription_months'])
                    ? now()->addMonths($validated['subscription_months'])
                    : now()->addYear(),
                'enabled_modules' => $validated['enabled_modules'] ?? School::getDefaultModules(),
            ]);

            // Generate temporary password if not provided
            $temporaryPassword = $validated['admin_password'] ?? Str::random(12);

            // Create admin user
            $adminUser = User::create([
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'phone' => $validated['admin_phone'] ?? null,
                'password' => Hash::make($temporaryPassword),
                'school_id' => $school->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            // Assign school-admin role
            $schoolAdminRole = Role::where('name', 'school-admin')->first();
            if ($schoolAdminRole) {
                $adminUser->assignRole($schoolAdminRole);
            }

            return [
                'school' => $school,
                'admin_user' => $adminUser,
                'temporary_password' => $temporaryPassword,
            ];
        });

        // Dispatch welcome email job
        SendWelcomeEmailJob::dispatch(
            $result['school']->id,
            $result['admin_user']->id,
            $result['temporary_password']
        );

        return response()->json([
            'message' => 'School onboarded successfully. Welcome email has been queued.',
            'data' => [
                'school' => new SchoolResource($result['school']),
                'admin_user' => [
                    'id' => $result['admin_user']->id,
                    'name' => $result['admin_user']->name,
                    'email' => $result['admin_user']->email,
                ],
            ],
        ], 201);
    }

    public function activate(School $school): JsonResponse
    {
        $school->update(['status' => School::STATUS_ACTIVE]);

        return response()->json([
            'message' => 'School activated successfully.',
            'data' => new SchoolResource($school),
        ]);
    }

    public function suspend(Request $request, School $school): JsonResponse
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $school->update([
            'status' => School::STATUS_SUSPENDED,
            'metadata' => array_merge($school->metadata ?? [], [
                'suspension_reason' => $request->reason,
                'suspended_at' => now()->toISOString(),
            ]),
        ]);

        return response()->json([
            'message' => 'School suspended successfully.',
            'data' => new SchoolResource($school),
        ]);
    }

    public function updateModules(Request $request, School $school): JsonResponse
    {
        $request->validate([
            'enabled_modules' => ['required', 'array'],
            'enabled_modules.*' => ['boolean'],
        ]);

        $school->update(['enabled_modules' => $request->enabled_modules]);

        return response()->json([
            'message' => 'School modules updated successfully.',
            'data' => new SchoolResource($school),
        ]);
    }

    public function extendSubscription(Request $request, School $school): JsonResponse
    {
        $request->validate([
            'months' => ['required', 'integer', 'min:1', 'max:36'],
            'plan' => ['nullable', 'string', 'in:free,basic,premium,enterprise'],
        ]);

        $currentExpiry = $school->subscription_expires_at ?? now();
        $newExpiry = $currentExpiry->isFuture()
            ? $currentExpiry->addMonths($request->months)
            : now()->addMonths($request->months);

        $school->update([
            'subscription_expires_at' => $newExpiry,
            'subscription_plan' => $request->plan ?? $school->subscription_plan,
        ]);

        return response()->json([
            'message' => 'Subscription extended successfully.',
            'data' => new SchoolResource($school),
        ]);
    }

    public function statistics(): JsonResponse
    {
        $stats = [
            'total_schools' => School::count(),
            'active_schools' => School::where('status', School::STATUS_ACTIVE)->count(),
            'suspended_schools' => School::where('status', School::STATUS_SUSPENDED)->count(),
            'pending_schools' => School::where('status', School::STATUS_PENDING)->count(),
            'schools_by_type' => School::selectRaw('type, count(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
            'schools_by_plan' => School::selectRaw('subscription_plan, count(*) as count')
                ->groupBy('subscription_plan')
                ->pluck('count', 'subscription_plan'),
            'expiring_soon' => School::where('subscription_expires_at', '<=', now()->addDays(30))
                ->where('subscription_expires_at', '>', now())
                ->count(),
            'total_users' => User::count(),
        ];

        return response()->json($stats);
    }
}
