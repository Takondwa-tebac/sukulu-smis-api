<?php

use App\Models\Role;
use App\Models\User;

test('public registration is disabled - users are created by admins', function () {
    // In this API, there is no public registration endpoint
    // Users are created by super-admins or school admins
    // This test verifies that the /register route does not exist
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertStatus(404);
});

test('super admin can create users via admin endpoint', function () {
    // Create super admin role
    $superAdminRole = Role::create([
        'name' => 'super-admin',
        'guard_name' => 'sanctum',
        'is_system' => true,
    ]);

    // Create super admin user
    $superAdmin = User::factory()->create([
        'school_id' => null,
    ]);
    $superAdmin->assignRole($superAdminRole);

    $response = $this->actingAs($superAdmin, 'sanctum')->postJson('/api/v1/admin/users', [
        'first_name' => 'New',
        'last_name' => 'User',
        'email' => 'newuser@example.com',
        'role' => 'super-admin',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
});
