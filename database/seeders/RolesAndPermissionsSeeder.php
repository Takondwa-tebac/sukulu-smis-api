<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $this->createPermissions();
        $this->createSystemRoles();
    }

    protected function createPermissions(): void
    {
        $permissions = [
            // School Management
            ['name' => 'schools.view', 'module' => 'schools', 'description' => 'View school details'],
            ['name' => 'schools.create', 'module' => 'schools', 'description' => 'Create new schools'],
            ['name' => 'schools.update', 'module' => 'schools', 'description' => 'Update school details'],
            ['name' => 'schools.delete', 'module' => 'schools', 'description' => 'Delete schools'],
            ['name' => 'schools.manage-modules', 'module' => 'schools', 'description' => 'Enable/disable school modules'],

            // User Management
            ['name' => 'users.view', 'module' => 'users', 'description' => 'View users'],
            ['name' => 'users.create', 'module' => 'users', 'description' => 'Create users'],
            ['name' => 'users.update', 'module' => 'users', 'description' => 'Update users'],
            ['name' => 'users.delete', 'module' => 'users', 'description' => 'Delete users'],
            ['name' => 'users.assign-roles', 'module' => 'users', 'description' => 'Assign roles to users'],

            // Grading Systems
            ['name' => 'grading-systems.view', 'module' => 'grading', 'description' => 'View grading systems'],
            ['name' => 'grading-systems.create', 'module' => 'grading', 'description' => 'Create grading systems'],
            ['name' => 'grading-systems.update', 'module' => 'grading', 'description' => 'Update grading systems'],
            ['name' => 'grading-systems.delete', 'module' => 'grading', 'description' => 'Delete grading systems'],
            ['name' => 'grading-systems.configure', 'module' => 'grading', 'description' => 'Configure school grading'],

            // Academic Structure
            ['name' => 'academic-years.view', 'module' => 'academic', 'description' => 'View academic years'],
            ['name' => 'academic-years.manage', 'module' => 'academic', 'description' => 'Manage academic years'],
            ['name' => 'terms.view', 'module' => 'academic', 'description' => 'View terms'],
            ['name' => 'terms.manage', 'module' => 'academic', 'description' => 'Manage terms'],
            ['name' => 'classes.view', 'module' => 'academic', 'description' => 'View classes'],
            ['name' => 'classes.manage', 'module' => 'academic', 'description' => 'Manage classes'],
            ['name' => 'streams.view', 'module' => 'academic', 'description' => 'View streams'],
            ['name' => 'streams.manage', 'module' => 'academic', 'description' => 'Manage streams'],
            ['name' => 'subjects.view', 'module' => 'academic', 'description' => 'View subjects'],
            ['name' => 'subjects.manage', 'module' => 'academic', 'description' => 'Manage subjects'],

            // Students
            ['name' => 'students.view', 'module' => 'students', 'description' => 'View students'],
            ['name' => 'students.create', 'module' => 'students', 'description' => 'Create students'],
            ['name' => 'students.update', 'module' => 'students', 'description' => 'Update students'],
            ['name' => 'students.delete', 'module' => 'students', 'description' => 'Delete students'],
            ['name' => 'students.import', 'module' => 'students', 'description' => 'Bulk import students'],
            ['name' => 'students.promote', 'module' => 'students', 'description' => 'Promote students'],

            // Guardians
            ['name' => 'guardians.view', 'module' => 'students', 'description' => 'View guardians'],
            ['name' => 'guardians.manage', 'module' => 'students', 'description' => 'Manage guardians'],

            // Admissions
            ['name' => 'admissions.view', 'module' => 'admissions', 'description' => 'View admissions'],
            ['name' => 'admissions.create', 'module' => 'admissions', 'description' => 'Create admissions'],
            ['name' => 'admissions.process', 'module' => 'admissions', 'description' => 'Process admissions'],
            ['name' => 'admissions.approve', 'module' => 'admissions', 'description' => 'Approve admissions'],

            // Exams & Assessments
            ['name' => 'exams.view', 'module' => 'exams', 'description' => 'View exams'],
            ['name' => 'exams.create', 'module' => 'exams', 'description' => 'Create exams'],
            ['name' => 'exams.manage', 'module' => 'exams', 'description' => 'Manage exams'],
            ['name' => 'marks.view', 'module' => 'exams', 'description' => 'View marks'],
            ['name' => 'marks.enter', 'module' => 'exams', 'description' => 'Enter marks'],
            ['name' => 'marks.import', 'module' => 'exams', 'description' => 'Import marks'],
            ['name' => 'marks.moderate', 'module' => 'exams', 'description' => 'Moderate marks'],
            ['name' => 'marks.approve', 'module' => 'exams', 'description' => 'Approve marks'],
            ['name' => 'marks.lock', 'module' => 'exams', 'description' => 'Lock approved marks'],

            // Reports
            ['name' => 'reports.view', 'module' => 'reports', 'description' => 'View reports'],
            ['name' => 'reports.generate', 'module' => 'reports', 'description' => 'Generate reports'],
            ['name' => 'reports.download', 'module' => 'reports', 'description' => 'Download reports'],
            ['name' => 'transcripts.generate', 'module' => 'reports', 'description' => 'Generate transcripts'],

            // Timetables
            ['name' => 'timetables.view', 'module' => 'timetables', 'description' => 'View timetables'],
            ['name' => 'timetables.manage', 'module' => 'timetables', 'description' => 'Manage timetables'],

            // Fees & Billing
            ['name' => 'fees.view', 'module' => 'fees', 'description' => 'View fees'],
            ['name' => 'fees.manage', 'module' => 'fees', 'description' => 'Manage fee structures'],
            ['name' => 'payments.view', 'module' => 'fees', 'description' => 'View payments'],
            ['name' => 'payments.record', 'module' => 'fees', 'description' => 'Record payments'],
            ['name' => 'invoices.generate', 'module' => 'fees', 'description' => 'Generate invoices'],

            // Discipline
            ['name' => 'discipline.view', 'module' => 'discipline', 'description' => 'View disciplinary records'],
            ['name' => 'discipline.create', 'module' => 'discipline', 'description' => 'Create disciplinary records'],
            ['name' => 'discipline.manage', 'module' => 'discipline', 'description' => 'Manage disciplinary records'],

            // Notifications & Announcements
            ['name' => 'notifications.view', 'module' => 'notifications', 'description' => 'View notifications'],
            ['name' => 'notifications.send', 'module' => 'notifications', 'description' => 'Send notifications'],
            ['name' => 'announcements.view', 'module' => 'notifications', 'description' => 'View announcements'],
            ['name' => 'announcements.create', 'module' => 'notifications', 'description' => 'Create announcements'],
            ['name' => 'announcements.manage', 'module' => 'notifications', 'description' => 'Manage announcements'],

            // Settings
            ['name' => 'settings.view', 'module' => 'settings', 'description' => 'View settings'],
            ['name' => 'settings.manage', 'module' => 'settings', 'description' => 'Manage settings'],

            // Audit Logs
            ['name' => 'audit-logs.view', 'module' => 'audit', 'description' => 'View audit logs'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'sanctum'],
                [
                    'module' => $permission['module'],
                    'description' => $permission['description'],
                ]
            );
        }
    }

    protected function createSystemRoles(): void
    {
        // Super Admin - SaaS Owner (no school_id, has all permissions)
        $superAdmin = Role::updateOrCreate(
            ['name' => 'super-admin', 'guard_name' => 'sanctum', 'school_id' => null],
            [
                'description' => 'SaaS Super Administrator with full system access',
                'is_system' => true,
            ]
        );
        $superAdmin->syncPermissions(Permission::all());

        // School Admin
        $schoolAdminPermissions = Permission::whereIn('module', [
            'users', 'grading', 'academic', 'students', 'admissions', 
            'exams', 'reports', 'timetables', 'fees', 'discipline', 
            'notifications', 'settings'
        ])->pluck('name')->toArray();

        $this->createSchoolRole('school-admin', 'School Administrator', $schoolAdminPermissions, true);

        // Bursar
        $bursarPermissions = [
            'fees.view', 'fees.manage', 'payments.view', 'payments.record', 
            'invoices.generate', 'students.view', 'reports.view', 'reports.generate'
        ];
        $this->createSchoolRole('bursar', 'School Bursar', $bursarPermissions);

        // Accountant
        $accountantPermissions = [
            'fees.view', 'payments.view', 'payments.record', 'invoices.generate',
            'students.view', 'reports.view'
        ];
        $this->createSchoolRole('accountant', 'School Accountant', $accountantPermissions);

        // Secretary
        $secretaryPermissions = [
            'students.view', 'students.create', 'students.update', 'guardians.view',
            'guardians.manage', 'admissions.view', 'admissions.create', 'admissions.process',
            'notifications.view', 'announcements.view'
        ];
        $this->createSchoolRole('secretary', 'School Secretary', $secretaryPermissions);

        // Teacher
        $teacherPermissions = [
            'students.view', 'classes.view', 'subjects.view', 'exams.view',
            'marks.view', 'marks.enter', 'reports.view', 'timetables.view',
            'discipline.view', 'discipline.create', 'notifications.view',
            'announcements.view', 'grading-systems.view'
        ];
        $this->createSchoolRole('teacher', 'Teacher', $teacherPermissions);

        // HOD (Head of Department)
        $hodPermissions = array_merge($teacherPermissions, [
            'marks.moderate', 'subjects.manage', 'reports.generate'
        ]);
        $this->createSchoolRole('hod', 'Head of Department', $hodPermissions);

        // Exams Officer
        $examsOfficerPermissions = [
            'exams.view', 'exams.create', 'exams.manage', 'marks.view', 
            'marks.import', 'marks.moderate', 'marks.approve', 'marks.lock',
            'students.view', 'classes.view', 'subjects.view', 'reports.view',
            'reports.generate', 'grading-systems.view', 'grading-systems.configure'
        ];
        $this->createSchoolRole('exams-officer', 'Exams Officer', $examsOfficerPermissions);

        // ICT Officer
        $ictOfficerPermissions = [
            'users.view', 'users.create', 'users.update', 'users.assign-roles',
            'settings.view', 'settings.manage', 'students.import', 'marks.import',
            'audit-logs.view'
        ];
        $this->createSchoolRole('ict-officer', 'ICT Officer', $ictOfficerPermissions);

        // Parent/Guardian
        $parentPermissions = [
            'students.view', 'marks.view', 'reports.view', 'reports.download',
            'fees.view', 'payments.view', 'timetables.view', 'discipline.view',
            'notifications.view', 'announcements.view'
        ];
        $this->createSchoolRole('parent', 'Parent/Guardian', $parentPermissions);
    }

    protected function createSchoolRole(string $name, string $description, array $permissions, bool $isSystem = false): Role
    {
        $role = Role::updateOrCreate(
            ['name' => $name, 'guard_name' => 'sanctum', 'school_id' => null],
            [
                'description' => $description,
                'is_system' => $isSystem,
            ]
        );

        $role->syncPermissions($permissions);

        return $role;
    }
}
