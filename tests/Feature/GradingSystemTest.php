<?php

use App\Models\GradeScale;
use App\Models\GradingSystem;
use App\Models\School;
use App\Models\User;
use App\Services\Grading\GradingEngine;
use Database\Seeders\GradingSystemSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(GradingSystemSeeder::class);
});

describe('Grading Engine', function () {
    it('calculates correct grade for a score', function () {
        $gradingSystem = GradingSystem::where('code', 'malawi-primary')->first();
        $engine = new GradingEngine($gradingSystem);

        $grade = $engine->calculateGrade(85);
        expect($grade)->not->toBeNull();
        expect($grade->grade)->toBe('A');
        expect($grade->is_passing)->toBeTrue();

        $grade = $engine->calculateGrade(45);
        expect($grade->grade)->toBe('F');
        expect($grade->is_passing)->toBeFalse();
    });

    it('calculates GPA correctly', function () {
        $gradingSystem = GradingSystem::where('code', 'us-standard')->first();
        $engine = new GradingEngine($gradingSystem);

        $gpa = $engine->calculateGPA([95, 85, 75, 65]);
        expect($gpa)->toBeGreaterThan(0);
        expect($gpa)->toBeLessThanOrEqual(4.0);
    });

    it('determines if score is passing', function () {
        $gradingSystem = GradingSystem::where('code', 'malawi-msce')->first();
        $engine = new GradingEngine($gradingSystem);

        expect($engine->isPassing(75))->toBeTrue();
        expect($engine->isPassing(35))->toBeFalse();
    });

    it('calculates overall result with multiple subjects', function () {
        $gradingSystem = GradingSystem::where('code', 'malawi-msce')->first();
        $engine = new GradingEngine($gradingSystem);

        $subjectScores = [
            'ENG' => 75,
            'MAT' => 65,
            'PHY' => 55,
            'CHE' => 70,
            'BIO' => 60,
            'GEO' => 80,
        ];

        $results = $engine->calculateOverallResult($subjectScores);

        expect($results)->toHaveKey('subjects');
        expect($results)->toHaveKey('summary');
        expect($results['summary']['total_subjects'])->toBe(6);
        expect($results['summary']['subjects_passed'])->toBe(6);
        expect($results['summary']['meets_pass_criteria'])->toBeTrue();
    });

    it('checks priority subject passing', function () {
        $gradingSystem = GradingSystem::where('code', 'malawi-msce')->first();
        $engine = new GradingEngine($gradingSystem);

        expect($engine->isSubjectPassing('ENG', 55))->toBeTrue();
        expect($engine->isSubjectPassing('ENG', 35))->toBeFalse();
    });

    it('fails promotion when priority subject fails', function () {
        $gradingSystem = GradingSystem::where('code', 'malawi-msce')->first();
        $engine = new GradingEngine($gradingSystem);

        $subjectScores = [
            'ENG' => 35, // Failing English (priority subject)
            'MAT' => 75,
            'PHY' => 65,
            'CHE' => 70,
            'BIO' => 60,
            'GEO' => 80,
        ];

        $results = $engine->calculateOverallResult($subjectScores);
        expect($results['summary']['meets_pass_criteria'])->toBeFalse();
    });

    it('ranks students correctly', function () {
        $gradingSystem = GradingSystem::where('code', 'malawi-primary')->first();
        $engine = new GradingEngine($gradingSystem);

        $scores = [
            'student1' => 85,
            'student2' => 92,
            'student3' => 78,
            'student4' => 92,
        ];

        $ranking = $engine->getRanking($scores);

        expect($ranking['student2']['rank'])->toBe(1);
        expect($ranking['student4']['rank'])->toBe(1); // Tie
        expect($ranking['student1']['rank'])->toBe(3);
        expect($ranking['student3']['rank'])->toBe(4);
    });
});

describe('Grading System API', function () {
    it('lists grading systems for authenticated user', function () {
        $user = User::factory()->create();
        $permission = \App\Models\Permission::where('name', 'grading-systems.view')->first();
        $user->permissions()->attach($permission->id);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/grading-systems');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'code', 'type', 'scale_type'],
            ],
        ]);
    });

    it('shows system defaults', function () {
        $user = User::factory()->create();
        $permission = \App\Models\Permission::where('name', 'grading-systems.view')->first();
        $user->permissions()->attach($permission->id);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/grading-systems/system-defaults');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'code', 'is_system_default'],
            ],
        ]);

        $data = $response->json('data');
        expect(count($data))->toBeGreaterThan(0);
        
        foreach ($data as $system) {
            expect($system['is_system_default'])->toBeTrue();
        }
    });

    it('shows a specific grading system with grade scales', function () {
        $user = User::factory()->create();
        $permission = \App\Models\Permission::where('name', 'grading-systems.view')->first();
        $user->permissions()->attach($permission->id);

        $gradingSystem = GradingSystem::where('code', 'malawi-msce')->first();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/v1/grading-systems/{$gradingSystem->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'code',
                'grade_scales' => [
                    '*' => ['id', 'grade', 'min_score', 'max_score', 'is_passing'],
                ],
            ],
        ]);
    });

    it('calculates grades via API', function () {
        $user = User::factory()->create();
        $permission = \App\Models\Permission::where('name', 'grading-systems.view')->first();
        $user->permissions()->attach($permission->id);

        $gradingSystem = GradingSystem::where('code', 'malawi-msce')->first();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/grading-systems/calculate', [
                'grading_system_id' => $gradingSystem->id,
                'scores' => [
                    ['subject_code' => 'ENG', 'score' => 75],
                    ['subject_code' => 'MAT', 'score' => 65],
                    ['subject_code' => 'PHY', 'score' => 55],
                ],
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'grading_system' => ['id', 'name', 'code'],
            'results' => [
                'subjects',
                'summary' => [
                    'total_subjects',
                    'subjects_passed',
                    'average_score',
                    'meets_pass_criteria',
                ],
            ],
        ]);
    });

    it('creates a custom grading system', function () {
        $school = School::factory()->create();
        $user = User::factory()->forSchool($school)->create();
        $permission = \App\Models\Permission::where('name', 'grading-systems.create')->first();
        $user->permissions()->attach($permission->id);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/grading-systems', [
                'name' => 'Custom School Grading',
                'code' => 'custom-school-grading',
                'description' => 'Custom grading system for our school',
                'type' => 'primary',
                'scale_type' => 'percentage',
                'min_score' => 0,
                'max_score' => 100,
                'pass_mark' => 50,
                'min_subjects_to_pass' => 5,
                'grade_scales' => [
                    ['grade' => 'A', 'grade_label' => 'Excellent', 'min_score' => 80, 'max_score' => 100, 'is_passing' => true],
                    ['grade' => 'B', 'grade_label' => 'Good', 'min_score' => 60, 'max_score' => 79.99, 'is_passing' => true],
                    ['grade' => 'C', 'grade_label' => 'Pass', 'min_score' => 50, 'max_score' => 59.99, 'is_passing' => true],
                    ['grade' => 'F', 'grade_label' => 'Fail', 'min_score' => 0, 'max_score' => 49.99, 'is_passing' => false],
                ],
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Custom School Grading');
        $response->assertJsonPath('data.school_id', $school->id);

        $this->assertDatabaseHas('grading_systems', [
            'code' => 'custom-school-grading',
            'school_id' => $school->id,
        ]);
    });

    it('prevents deleting system default grading systems', function () {
        $user = User::factory()->create();
        $permission = \App\Models\Permission::where('name', 'grading-systems.delete')->first();
        $user->permissions()->attach($permission->id);

        $gradingSystem = GradingSystem::where('is_system_default', true)->first();

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/v1/grading-systems/{$gradingSystem->id}");

        $response->assertForbidden();
    });

    it('requires authentication for protected routes', function () {
        $response = $this->getJson('/api/v1/grading-systems');
        $response->assertUnauthorized();
    });
});

describe('Authentication', function () {
    it('logs in with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'user',
            'token',
            'token_type',
        ]);
    });

    it('rejects invalid credentials', function () {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertUnprocessable();
    });

    it('rejects login for inactive users', function () {
        $user = User::factory()->inactive()->create([
            'email' => 'inactive@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable();
    });

    it('returns authenticated user profile', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me');

        $response->assertOk();
        $response->assertJsonStructure([
            'user' => ['id', 'email', 'first_name', 'last_name'],
            'permissions',
            'roles',
        ]);
    });

    it('logs out successfully', function () {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/logout');

        $response->assertOk();
    });

    it('changes password successfully', function () {
        $user = User::factory()->create([
            'password' => bcrypt('oldpassword'),
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/auth/change-password', [
                'current_password' => 'oldpassword',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ]);

        $response->assertOk();
        
        // Verify new password works
        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('newpassword123', $user->fresh()->password)
        );
    });
});
