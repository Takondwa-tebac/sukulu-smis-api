<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Roles & Permissions
 *
 * APIs for managing roles and permissions. Role management requires appropriate permissions.
 */
class RolePermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:roles.view')->only(['roles', 'showRole', 'permissions', 'modules']);
        $this->middleware('permission:roles.create')->only(['storeRole']);
        $this->middleware('permission:roles.update')->only(['updateRole', 'assignPermissions']);
        $this->middleware('permission:roles.delete')->only(['destroyRole']);
    }

    /**
     * List all roles
     *
     * Get all roles with their permissions grouped by module.
     *
     * @authenticated
     * @queryParam school_id uuid Filter by school. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @queryParam is_system boolean Filter system roles. Example: true
     *
     * @response 200 scenario="Success" {"data": [{"id": "uuid", "name": "school-admin", "permissions": []}]}
     */
    public function roles(Request $request): JsonResponse
    {
        $query = Role::query()
            ->when($request->school_id, fn ($q, $id) => $q->where('school_id', $id))
            ->when($request->has('is_system'), fn ($q) => $q->where('is_system', $request->boolean('is_system')))
            ->withCount('users')
            ->with('permissions:id,name,module')
            ->orderBy('name');

        $roles = $query->get()->map(fn ($role) => [
            'id' => $role->id,
            'name' => $role->name,
            'description' => $role->description,
            'is_system' => $role->is_system,
            'school_id' => $role->school_id,
            'users_count' => $role->users_count,
            'permissions' => $role->permissions->pluck('name'),
            'permissions_by_module' => $role->permissions->groupBy('module')->map->pluck('name'),
        ]);

        return response()->json(['data' => $roles]);
    }

    public function storeRole(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'school_id' => ['nullable', 'uuid', 'exists:schools,id'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'school_id' => $validated['school_id'] ?? null,
            'guard_name' => 'sanctum',
            'is_system' => false,
        ]);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json([
            'message' => 'Role created successfully.',
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'permissions' => $role->permissions->pluck('name'),
            ],
        ], 201);
    }

    public function showRole(Role $role): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'is_system' => $role->is_system,
                'school_id' => $role->school_id,
                'permissions' => $role->permissions->pluck('name'),
                'permissions_by_module' => $role->permissions->groupBy('module')->map->pluck('name'),
            ],
        ]);
    }

    public function updateRole(Request $request, Role $role): JsonResponse
    {
        if ($role->is_system) {
            return response()->json(['message' => 'System roles cannot be modified.'], 422);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->update([
            'name' => $validated['name'] ?? $role->name,
            'description' => $validated['description'] ?? $role->description,
        ]);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json([
            'message' => 'Role updated successfully.',
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'permissions' => $role->permissions->pluck('name'),
            ],
        ]);
    }

    public function destroyRole(Role $role): JsonResponse
    {
        if ($role->is_system) {
            return response()->json(['message' => 'System roles cannot be deleted.'], 422);
        }

        if ($role->users()->count() > 0) {
            return response()->json(['message' => 'Cannot delete role with assigned users.'], 422);
        }

        $role->delete();

        return response()->json(['message' => 'Role deleted successfully.']);
    }

    public function permissions(Request $request): JsonResponse
    {
        $permissions = Permission::query()
            ->when($request->module, fn ($q, $module) => $q->where('module', $module))
            ->orderBy('module')
            ->orderBy('name')
            ->get(['id', 'name', 'module', 'description']);

        $grouped = $permissions->groupBy('module');

        return response()->json([
            'data' => $permissions,
            'by_module' => $grouped,
        ]);
    }

    public function assignPermissions(Request $request, Role $role): JsonResponse
    {
        if ($role->is_system && $role->name !== 'super-admin') {
            return response()->json(['message' => 'Cannot modify permissions of system roles.'], 422);
        }

        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($validated['permissions']);

        return response()->json([
            'message' => 'Permissions assigned successfully.',
            'data' => [
                'role' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
            ],
        ]);
    }

    public function modules(): JsonResponse
    {
        $modules = Permission::distinct()->pluck('module')->sort()->values();

        return response()->json(['data' => $modules]);
    }
}
