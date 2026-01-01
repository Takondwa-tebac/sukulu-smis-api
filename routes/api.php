<?php

use App\Http\Controllers\Api\V1\AcademicYearController;
use App\Http\Controllers\Api\V1\AdmissionApplicationController;
use App\Http\Controllers\Api\V1\AdmissionPeriodController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ExamController;
use App\Http\Controllers\Api\V1\GradingSystemController;
use App\Http\Controllers\Api\V1\GuardianController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ReportCardController;
use App\Http\Controllers\Api\V1\SchoolClassController;
use App\Http\Controllers\Api\V1\TimePeriodController;
use App\Http\Controllers\Api\V1\TimetableController;
use App\Http\Controllers\Api\V1\AttendanceController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\AnalyticsController;
use App\Http\Controllers\Api\V1\Admin\SchoolManagementController;
use App\Http\Controllers\Api\V1\Admin\UserManagementController;
use App\Http\Controllers\Api\V1\Admin\RolePermissionController;
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\StudentInvoiceController;
use App\Http\Controllers\Api\V1\StudentMarkController;
use App\Http\Controllers\Api\V1\SubjectController;
use App\Http\Controllers\Api\V1\TermController;
use App\Http\Controllers\Api\V1\StreamController;
use App\Http\Controllers\Api\V1\ClassSubjectController;
use App\Http\Controllers\Api\V1\FeeCategoryController;
use App\Http\Controllers\Api\V1\FeeStructureController;
use App\Http\Controllers\Api\V1\StudentPromotionController;
use App\Http\Controllers\Api\V1\BulkImportController;
use App\Http\Controllers\Api\V1\FileUploadController;
use App\Http\Controllers\Api\V1\SchoolSettingsController;
use App\Http\Controllers\Api\V1\DisciplineController;
use App\Http\Controllers\Api\V1\AuditLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All API routes are prefixed with /api/v1
|
*/

Route::prefix('v1')->group(function () {
    
    // Public routes (no authentication required)
    Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');

    // Protected routes (authentication required)
    Route::middleware(['auth:sanctum', 'school.status'])->group(function () {
        
        // Authentication
        Route::prefix('auth')->name('auth.')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::post('logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
            Route::get('me', [AuthController::class, 'me'])->name('me');
            Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
            Route::post('change-password', [AuthController::class, 'changePassword'])->name('change-password');
        });

        // Grading Systems
        Route::prefix('grading-systems')->name('grading-systems.')->group(function () {
            Route::get('system-defaults', [GradingSystemController::class, 'systemDefaults'])->name('system-defaults');
            Route::post('calculate', [GradingSystemController::class, 'calculate'])->name('calculate');
            Route::put('{grading_system}/grade-scales', [GradingSystemController::class, 'updateGradeScales'])->name('update-grade-scales');
        });
        Route::apiResource('grading-systems', GradingSystemController::class);

        // Academic Years
        Route::prefix('academic-years')->name('academic-years.')->group(function () {
            Route::get('current', [AcademicYearController::class, 'current'])->name('current');
            Route::post('{academic_year}/set-current', [AcademicYearController::class, 'setCurrent'])->name('set-current');
        });
        Route::apiResource('academic-years', AcademicYearController::class);

        // Terms
        Route::prefix('terms')->name('terms.')->group(function () {
            Route::get('current', [TermController::class, 'current'])->name('current');
            Route::post('{term}/set-current', [TermController::class, 'setCurrent'])->name('set-current');
        });
        Route::apiResource('terms', TermController::class);

        // Classes
        Route::apiResource('classes', SchoolClassController::class)->parameters(['classes' => 'class']);
        Route::get('classes/{class}/streams', [StreamController::class, 'byClass'])->name('classes.streams');
        Route::get('classes/{class}/subjects', [ClassSubjectController::class, 'byClass'])->name('classes.subjects');

        // Streams
        Route::apiResource('streams', StreamController::class);

        // Class Subjects (assign subjects to classes)
        Route::prefix('class-subjects')->name('class-subjects.')->group(function () {
            Route::post('bulk-assign', [ClassSubjectController::class, 'bulkAssign'])->name('bulk-assign');
            Route::post('{class_subject}/assign-teacher', [ClassSubjectController::class, 'assignTeacher'])->name('assign-teacher');
        });
        Route::apiResource('class-subjects', ClassSubjectController::class);

        // Subjects
        Route::apiResource('subjects', SubjectController::class);

        // Students
        Route::prefix('students')->name('students.')->group(function () {
            Route::post('{student}/guardians', [StudentController::class, 'attachGuardian'])->name('attach-guardian');
            Route::delete('{student}/guardians/{guardian}', [StudentController::class, 'detachGuardian'])->name('detach-guardian');
            Route::post('{student}/enroll', [StudentController::class, 'enroll'])->name('enroll');
        });
        Route::apiResource('students', StudentController::class);

        // Guardians
        Route::apiResource('guardians', GuardianController::class);

        // Admission Periods (requires admissions module)
        Route::middleware('module:admissions')->group(function () {
            Route::prefix('admission-periods')->name('admission-periods.')->group(function () {
                Route::post('{admission_period}/open', [AdmissionPeriodController::class, 'open'])->name('open');
                Route::post('{admission_period}/close', [AdmissionPeriodController::class, 'close'])->name('close');
            });
            Route::apiResource('admission-periods', AdmissionPeriodController::class);

            // Admission Applications
            Route::prefix('admission-applications')->name('admission-applications.')->group(function () {
                Route::post('{admission_application}/submit', [AdmissionApplicationController::class, 'submit'])->name('submit');
                Route::post('{admission_application}/status', [AdmissionApplicationController::class, 'updateStatus'])->name('update-status');
                Route::post('{admission_application}/approve', [AdmissionApplicationController::class, 'approve'])->name('approve');
                Route::post('{admission_application}/reject', [AdmissionApplicationController::class, 'reject'])->name('reject');
                Route::post('{admission_application}/enroll', [AdmissionApplicationController::class, 'enroll'])->name('enroll');
                Route::post('{admission_application}/comments', [AdmissionApplicationController::class, 'addComment'])->name('add-comment');
                Route::post('{admission_application}/schedule-interview', [AdmissionApplicationController::class, 'scheduleInterview'])->name('schedule-interview');
                Route::post('{admission_application}/record-interview', [AdmissionApplicationController::class, 'recordInterview'])->name('record-interview');
            });
            Route::apiResource('admission-applications', AdmissionApplicationController::class);
        });

        // Exams (requires exams module)
        Route::middleware('module:exams')->group(function () {
            Route::prefix('exams')->name('exams.')->group(function () {
                Route::post('{exam}/publish', [ExamController::class, 'publish'])->name('publish');
                Route::post('{exam}/subjects', [ExamController::class, 'addSubjects'])->name('add-subjects');
            });
            Route::apiResource('exams', ExamController::class);

            // Student Marks
            Route::prefix('exam-subjects/{exam_subject}/marks')->name('marks.')->group(function () {
                Route::get('/', [StudentMarkController::class, 'index'])->name('index');
                Route::post('/', [StudentMarkController::class, 'store'])->name('store');
                Route::post('submit', [StudentMarkController::class, 'submitMarks'])->name('submit');
                Route::post('bulk-moderate', [StudentMarkController::class, 'bulkModerate'])->name('bulk-moderate');
                Route::post('approve', [StudentMarkController::class, 'approve'])->name('approve');
                Route::post('lock', [StudentMarkController::class, 'lock'])->name('lock');
                Route::post('calculate-grades', [StudentMarkController::class, 'calculateGrades'])->name('calculate-grades');
            });
            Route::post('student-marks/{student_mark}/moderate', [StudentMarkController::class, 'moderate'])->name('marks.moderate');
        });

        // Fees Module (requires fees module)
        Route::middleware('module:fees')->group(function () {
            // Student Invoices
            Route::prefix('invoices')->name('invoices.')->group(function () {
                Route::post('generate-from-structure', [StudentInvoiceController::class, 'generateFromFeeStructure'])->name('generate');
                Route::post('{student_invoice}/send', [StudentInvoiceController::class, 'send'])->name('send');
                Route::post('{student_invoice}/void', [StudentInvoiceController::class, 'void'])->name('void');
                Route::get('student/{student}/balance', [StudentInvoiceController::class, 'studentBalance'])->name('student-balance');
            });
            Route::apiResource('invoices', StudentInvoiceController::class)->parameters(['invoices' => 'student_invoice']);

            // Fee Categories
            Route::apiResource('fee-categories', FeeCategoryController::class);

            // Fee Structures
            Route::prefix('fee-structures')->name('fee-structures.')->group(function () {
                Route::post('{fee_structure}/generate-invoices', [FeeStructureController::class, 'generateInvoices'])->name('generate-invoices');
                Route::post('{fee_structure}/duplicate', [FeeStructureController::class, 'duplicate'])->name('duplicate');
            });
            Route::apiResource('fee-structures', FeeStructureController::class);

            // Payments
            Route::prefix('payments')->name('payments.')->group(function () {
                Route::post('{payment}/allocate', [PaymentController::class, 'allocate'])->name('allocate');
                Route::post('{payment}/refund', [PaymentController::class, 'refund'])->name('refund');
                Route::get('{payment}/receipt', [PaymentController::class, 'receipt'])->name('receipt');
            });
            Route::apiResource('payments', PaymentController::class)->only(['index', 'store', 'show']);
        });

        // Report Cards (requires reports module)
        Route::middleware('module:reports')->group(function () {
            Route::prefix('report-cards')->name('report-cards.')->group(function () {
                Route::post('generate', [ReportCardController::class, 'generate'])->name('generate');
                Route::post('bulk-approve', [ReportCardController::class, 'bulkApprove'])->name('bulk-approve');
                Route::post('bulk-publish', [ReportCardController::class, 'bulkPublish'])->name('bulk-publish');
                Route::post('{report_card}/approve', [ReportCardController::class, 'approve'])->name('approve');
                Route::post('{report_card}/publish', [ReportCardController::class, 'publish'])->name('publish');
                Route::get('student/{student}', [ReportCardController::class, 'studentReports'])->name('student-reports');
                Route::get('{report_card}/download', [ReportCardController::class, 'downloadPdf'])->name('download');
                Route::post('download-bulk', [ReportCardController::class, 'downloadBulkPdf'])->name('download-bulk');
                Route::get('student/{student}/transcript', [ReportCardController::class, 'downloadTranscript'])->name('transcript');
                Route::get('class-summary', [ReportCardController::class, 'downloadClassSummary'])->name('class-summary');
            });
            Route::apiResource('report-cards', ReportCardController::class)->only(['index', 'show', 'update']);
        });

        // Timetables (requires timetables module)
        Route::middleware('module:timetables')->group(function () {
            Route::apiResource('time-periods', TimePeriodController::class);

            Route::prefix('timetables')->name('timetables.')->group(function () {
                Route::post('{timetable}/activate', [TimetableController::class, 'activate'])->name('activate');
                Route::post('{timetable}/slots', [TimetableController::class, 'addSlots'])->name('add-slots');
                Route::get('{timetable}/day/{day}', [TimetableController::class, 'getByDay'])->name('by-day');
                Route::get('teacher-schedule', [TimetableController::class, 'getTeacherSchedule'])->name('teacher-schedule');
            });
            Route::apiResource('timetables', TimetableController::class);
        });

        // Attendance (requires attendance module - added to default modules)
        Route::middleware('module:attendance')->group(function () {
            Route::prefix('attendance')->name('attendance.')->group(function () {
                Route::get('class-report', [AttendanceController::class, 'classAttendanceReport'])->name('class-report');
                Route::get('student/{student}/report', [AttendanceController::class, 'studentAttendanceReport'])->name('student-report');
                Route::get('sessions/{attendance_session}/students', [AttendanceController::class, 'getStudentsForAttendance'])->name('session-students');
                Route::post('sessions/{attendance_session}/record', [AttendanceController::class, 'recordAttendance'])->name('record');
            });
            Route::apiResource('attendance/sessions', AttendanceController::class)->parameters(['sessions' => 'attendance_session'])->only(['index', 'store', 'show']);
        });

        // Discipline (requires discipline module)
        Route::middleware('module:discipline')->group(function () {
            Route::prefix('discipline')->name('discipline.')->group(function () {
                // Categories
                Route::get('categories', [DisciplineController::class, 'categories'])->name('categories.index');
                Route::post('categories', [DisciplineController::class, 'storeCategory'])->name('categories.store');
                Route::get('categories/{category}', [DisciplineController::class, 'showCategory'])->name('categories.show');
                Route::put('categories/{category}', [DisciplineController::class, 'updateCategory'])->name('categories.update');
                Route::delete('categories/{category}', [DisciplineController::class, 'destroyCategory'])->name('categories.destroy');

                // Actions
                Route::get('actions', [DisciplineController::class, 'actions'])->name('actions.index');
                Route::post('actions', [DisciplineController::class, 'storeAction'])->name('actions.store');
                Route::get('actions/{action}', [DisciplineController::class, 'showAction'])->name('actions.show');
                Route::put('actions/{action}', [DisciplineController::class, 'updateAction'])->name('actions.update');
                Route::delete('actions/{action}', [DisciplineController::class, 'destroyAction'])->name('actions.destroy');

                // Incidents
                Route::get('incidents', [DisciplineController::class, 'incidents'])->name('incidents.index');
                Route::post('incidents', [DisciplineController::class, 'storeIncident'])->name('incidents.store');
                Route::get('incidents/{incident}', [DisciplineController::class, 'showIncident'])->name('incidents.show');
                Route::put('incidents/{incident}', [DisciplineController::class, 'updateIncident'])->name('incidents.update');
                Route::post('incidents/{incident}/resolve', [DisciplineController::class, 'resolveIncident'])->name('incidents.resolve');
                Route::post('incidents/{incident}/actions', [DisciplineController::class, 'addIncidentAction'])->name('incidents.add-action');
                Route::post('incidents/{incident}/notify-guardian', [DisciplineController::class, 'notifyGuardian'])->name('incidents.notify-guardian');

                // Incident Actions
                Route::post('incident-actions/{incidentAction}/approve', [DisciplineController::class, 'approveAction'])->name('incident-actions.approve');
                Route::post('incident-actions/{incidentAction}/complete', [DisciplineController::class, 'completeAction'])->name('incident-actions.complete');

                // Student History
                Route::get('students/{student}/history', [DisciplineController::class, 'studentHistory'])->name('student-history');
            });
        });

        // Notifications
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('templates', [NotificationController::class, 'templates'])->name('templates.index');
            Route::post('templates', [NotificationController::class, 'storeTemplate'])->name('templates.store');
            Route::get('templates/{notification_template}', [NotificationController::class, 'showTemplate'])->name('templates.show');
            Route::put('templates/{notification_template}', [NotificationController::class, 'updateTemplate'])->name('templates.update');
            Route::delete('templates/{notification_template}', [NotificationController::class, 'destroyTemplate'])->name('templates.destroy');
            Route::post('send', [NotificationController::class, 'send'])->name('send');
            Route::get('my', [NotificationController::class, 'userNotifications'])->name('my');
            Route::get('unread-count', [NotificationController::class, 'unreadCount'])->name('unread-count');
            Route::post('mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-read');
            Route::post('recipients/{notification_recipient}/read', [NotificationController::class, 'markAsRead'])->name('mark-read');
        });
        Route::apiResource('notifications', NotificationController::class)->only(['index', 'show']);

        // Analytics / Dashboard
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('school-admin', [AnalyticsController::class, 'schoolAdminDashboard'])->name('school-admin');
            Route::get('teacher', [AnalyticsController::class, 'teacherDashboard'])->name('teacher');
            Route::get('bursar', [AnalyticsController::class, 'bursarDashboard'])->name('bursar');
            Route::get('exams-officer', [AnalyticsController::class, 'examsOfficerDashboard'])->name('exams-officer');
        });

        // Roles & Permissions
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RolePermissionController::class, 'roles'])->name('index');
            Route::post('/', [RolePermissionController::class, 'storeRole'])->name('store');
            Route::get('{role}', [RolePermissionController::class, 'showRole'])->name('show');
            Route::put('{role}', [RolePermissionController::class, 'updateRole'])->name('update');
            Route::delete('{role}', [RolePermissionController::class, 'destroyRole'])->name('destroy');
            Route::post('{role}/permissions', [RolePermissionController::class, 'assignPermissions'])->name('assign-permissions');
        });
        Route::get('permissions', [RolePermissionController::class, 'permissions'])->name('permissions.index');
        Route::get('permissions/modules', [RolePermissionController::class, 'modules'])->name('permissions.modules');

        // Student Promotions
        Route::prefix('promotions')->name('promotions.')->group(function () {
            Route::get('/', [StudentPromotionController::class, 'index'])->name('index');
            Route::post('preview', [StudentPromotionController::class, 'preview'])->name('preview');
            Route::post('promote', [StudentPromotionController::class, 'promote'])->name('promote');
            Route::post('repeat', [StudentPromotionController::class, 'repeat'])->name('repeat');
        });

        // Bulk Import (CSV/Excel file uploads for data migration)
        Route::prefix('import')->name('import.')->group(function () {
            Route::post('students', [BulkImportController::class, 'importStudents'])->name('students');
            Route::post('marks', [BulkImportController::class, 'importMarks'])->name('marks');
            Route::post('guardians', [BulkImportController::class, 'importGuardians'])->name('guardians');
            
            // Template info (JSON)
            Route::get('templates/students', [BulkImportController::class, 'getStudentTemplate'])->name('templates.students');
            Route::get('templates/marks', [BulkImportController::class, 'getMarksTemplate'])->name('templates.marks');
            Route::get('templates/guardians', [BulkImportController::class, 'getGuardiansTemplate'])->name('templates.guardians');
            
            // Downloadable templates (Excel/CSV)
            Route::get('templates/students/download', [BulkImportController::class, 'downloadStudentTemplate'])->name('templates.students.download');
            Route::get('templates/marks/download', [BulkImportController::class, 'downloadMarksTemplate'])->name('templates.marks.download');
            Route::get('templates/guardians/download', [BulkImportController::class, 'downloadGuardiansTemplate'])->name('templates.guardians.download');
        });

        // File Uploads
        Route::prefix('uploads')->name('uploads.')->group(function () {
            Route::post('students/{student}/photo', [FileUploadController::class, 'uploadStudentPhoto'])->name('student-photo');
            Route::post('students/{student}/documents', [FileUploadController::class, 'uploadStudentDocument'])->name('student-document');
            Route::get('students/{student}/documents', [FileUploadController::class, 'getStudentDocuments'])->name('student-documents');
            Route::delete('students/{student}/documents/{mediaId}', [FileUploadController::class, 'deleteStudentDocument'])->name('delete-student-document');
            Route::post('profile-photo', [FileUploadController::class, 'uploadUserPhoto'])->name('profile-photo');
            Route::post('school/logo', [FileUploadController::class, 'uploadSchoolLogo'])->name('school-logo');
            Route::post('school/banner', [FileUploadController::class, 'uploadSchoolBanner'])->name('school-banner');
            Route::post('school/documents', [FileUploadController::class, 'uploadSchoolDocument'])->name('school-document');
        });

        // School Settings
        Route::prefix('school-settings')->name('school-settings.')->group(function () {
            Route::get('/', [SchoolSettingsController::class, 'show'])->name('show');
            Route::put('/', [SchoolSettingsController::class, 'update'])->name('update');
            Route::put('settings', [SchoolSettingsController::class, 'updateSettings'])->name('update-settings');
            Route::get('modules', [SchoolSettingsController::class, 'getModules'])->name('modules');
            Route::post('modules/toggle', [SchoolSettingsController::class, 'toggleModule'])->name('toggle-module');
        });

        // Audit Logs
        Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
            Route::get('activity', [AuditLogController::class, 'recentActivity'])->name('activity');
            Route::get('summary', [AuditLogController::class, 'activitySummary'])->name('summary');
            Route::get('users/{user}', [AuditLogController::class, 'userActivity'])->name('user-activity');
            Route::get('model-history', [AuditLogController::class, 'modelHistory'])->name('model-history');
        });

        // Super Admin Routes (school management, user management)
        Route::prefix('admin')->name('admin.')->middleware('role:super-admin')->group(function () {
            // School Management
            Route::get('schools', [SchoolManagementController::class, 'index'])->name('schools.index');
            Route::get('schools/statistics', [SchoolManagementController::class, 'statistics'])->name('schools.statistics');
            Route::post('schools/onboard', [SchoolManagementController::class, 'onboard'])->name('schools.onboard');
            Route::get('schools/{school}', [SchoolManagementController::class, 'show'])->name('schools.show');
            Route::post('schools/{school}/activate', [SchoolManagementController::class, 'activate'])->name('schools.activate');
            Route::post('schools/{school}/suspend', [SchoolManagementController::class, 'suspend'])->name('schools.suspend');
            Route::put('schools/{school}/modules', [SchoolManagementController::class, 'updateModules'])->name('schools.modules');
            Route::post('schools/{school}/extend-subscription', [SchoolManagementController::class, 'extendSubscription'])->name('schools.extend-subscription');

            // User Management
            Route::get('users', [UserManagementController::class, 'index'])->name('users.index');
            Route::post('users', [UserManagementController::class, 'store'])->name('users.store');
            Route::get('users/super-admins', [UserManagementController::class, 'superAdmins'])->name('users.super-admins');
            Route::post('users/super-admins', [UserManagementController::class, 'createSuperAdmin'])->name('users.create-super-admin');
            Route::get('users/{user}', [UserManagementController::class, 'show'])->name('users.show');
            Route::put('users/{user}', [UserManagementController::class, 'update'])->name('users.update');
            Route::delete('users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
            Route::post('users/{user}/activate', [UserManagementController::class, 'activate'])->name('users.activate');
            Route::post('users/{user}/deactivate', [UserManagementController::class, 'deactivate'])->name('users.deactivate');
            Route::post('users/{user}/assign-role', [UserManagementController::class, 'assignRole'])->name('users.assign-role');
            Route::post('users/{user}/reset-password', [UserManagementController::class, 'resetPassword'])->name('users.reset-password');
        });

    });
});
