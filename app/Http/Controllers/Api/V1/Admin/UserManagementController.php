<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\Users\UserResource;
use App\Jobs\SendPasswordResetEmailJob;
use App\Jobs\SendUserWelcomeEmailJob;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @group Admin - User Management
 *
 * APIs for super-admin user management across all schools.
 * These endpoints require the `super-admin` role.
 */
class UserManagementController extends Controller
{
    /**
     * List all users
     *
     * Get a paginated list of all users across all schools.
     *
     * @authenticated
     * @queryParam school_id uuid Filter by school. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @queryParam role string Filter by role name. Example: school-admin
     * @queryParam is_active boolean Filter by status (active, inactive). Example: true
     * @queryParam search string Search by name or email. Example: john
     * @queryParam per_page integer Items per page. Default: 15. Example: 20
     *
     * @response 200 scenario="Success" {"data": [], "links": {}, "meta": {}}
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = User::query()
            ->when($request->school_id, fn ($q, $id) => $q->where('school_id', $id))
            ->when($request->role, fn ($q, $role) => $q->role($role))
            ->when($request->is_active !== null, fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->when($request->search, fn ($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->with(['roles', 'school'])
            ->orderByDesc('created_at');

        return UserResource::collection($query->paginate($request->input('per_page', 15)));
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $temporaryPassword = $validated['password'] ?? Str::random(12);

        // Generate username from email if not provided
        $username = $validated['username'] ?? Str::before($validated['email'], '@') . '-' . Str::random(4);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? $validated['first_name'],
            'username' => $username,
            'email' => $validated['email'],
            'phone_number' => $validated['phone'] ?? null,
            'password' => Hash::make($temporaryPassword),
            'school_id' => $validated['school_id'] ?? null,
            'status' => ($validated['is_active'] ?? true) ? User::STATUS_ACTIVE : User::STATUS_INACTIVE,
        ]);

        if (!empty($validated['role'])) {
            $role = Role::where('name', $validated['role'])->first();
            if ($role) {
                $user->assignRole($role);
            }
        }

        // Send welcome email with credentials via background job
        if ($validated['send_welcome_email'] ?? true) {
            SendUserWelcomeEmailJob::dispatch(
                $user->id,
                empty($validated['password']) ? $temporaryPassword : null
            );
        }

        return response()->json([
            'message' => 'User created successfully.',
            'data' => new UserResource($user->load('roles')),
            'temporary_password' => empty($validated['password']) ? $temporaryPassword : null,
        ], 201);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user->load(['roles', 'school', 'permissions']));
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $validated = $request->validated();

        $updateData = collect($validated)->except(['role', 'password'])->toArray();

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        if (!empty($validated['role'])) {
            $user->syncRoles([$validated['role']]);
        }

        return new UserResource($user->load('roles'));
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->hasRole('super-admin')) {
            return response()->json(['message' => 'Cannot delete super-admin users.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }

    public function activate(User $user): JsonResponse
    {
        $user->update(['is_active' => true]);

        return response()->json([
            'message' => 'User activated successfully.',
            'data' => new UserResource($user),
        ]);
    }

    public function deactivate(User $user): JsonResponse
    {
        if ($user->hasRole('super-admin')) {
            return response()->json(['message' => 'Cannot deactivate super-admin users.'], 422);
        }

        $user->update(['is_active' => false]);

        return response()->json([
            'message' => 'User deactivated successfully.',
            'data' => new UserResource($user),
        ]);
    }

    public function assignRole(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'role' => ['required', 'string', 'exists:roles,name'],
        ]);

        $user->syncRoles([$request->role]);

        return response()->json([
            'message' => 'Role assigned successfully.',
            'data' => new UserResource($user->load('roles')),
        ]);
    }

    public function superAdmins(Request $request): AnonymousResourceCollection
    {
        $users = User::role('super-admin')
            ->whereNull('school_id')
            ->orderBy('name')
            ->paginate($request->input('per_page', 15));

        return UserResource::collection($users);
    }

    public function createSuperAdmin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'school_id' => null,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $superAdminRole = Role::where('name', 'super-admin')->first();
        if ($superAdminRole) {
            $user->assignRole($superAdminRole);
        }

        return response()->json([
            'message' => 'Super admin created successfully.',
            'data' => new UserResource($user->load('roles')),
        ], 201);
    }

    /**
     * Reset user password
     *
     * Admin can reset a user's password and optionally send them a notification.
     *
     * @authenticated
     * @urlParam user uuid required The user ID.
     * @bodyParam password string New password (auto-generated if not provided).
     * @bodyParam notify boolean Send password reset notification. Default: true.
     *
     * @response 200 scenario="Success" {"message": "Password reset successfully.", "temporary_password": "abc123"}
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['nullable', 'string', 'min:8'],
            'notify' => ['boolean'],
        ]);

        $newPassword = $validated['password'] ?? Str::random(12);

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        // Send password reset notification via background job if notify is true (default: true)
        if ($validated['notify'] ?? true) {
            SendPasswordResetEmailJob::dispatch($user->id, $newPassword);
        }

        return response()->json([
            'message' => 'Password reset successfully.',
            'temporary_password' => empty($validated['password']) ? $newPassword : null,
        ]);
    }
}
