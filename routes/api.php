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
use App\Http\Controllers\Api\V1\StudentController;
use App\Http\Controllers\Api\V1\StudentInvoiceController;
use App\Http\Controllers\Api\V1\StudentMarkController;
use App\Http\Controllers\Api\V1\SubjectController;
use App\Http\Controllers\Api\V1\TermController;
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
    Route::middleware(['auth:sanctum'])->group(function () {
        
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

        // Admission Periods
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

        // Exams
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

        // Student Invoices
        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::post('generate-from-structure', [StudentInvoiceController::class, 'generateFromFeeStructure'])->name('generate');
            Route::post('{student_invoice}/send', [StudentInvoiceController::class, 'send'])->name('send');
            Route::post('{student_invoice}/void', [StudentInvoiceController::class, 'void'])->name('void');
            Route::get('student/{student}/balance', [StudentInvoiceController::class, 'studentBalance'])->name('student-balance');
        });
        Route::apiResource('invoices', StudentInvoiceController::class)->parameters(['invoices' => 'student_invoice']);

        // Payments
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::post('{payment}/allocate', [PaymentController::class, 'allocate'])->name('allocate');
            Route::post('{payment}/refund', [PaymentController::class, 'refund'])->name('refund');
            Route::get('{payment}/receipt', [PaymentController::class, 'receipt'])->name('receipt');
        });
        Route::apiResource('payments', PaymentController::class)->only(['index', 'store', 'show']);

        // Report Cards
        Route::prefix('report-cards')->name('report-cards.')->group(function () {
            Route::post('generate', [ReportCardController::class, 'generate'])->name('generate');
            Route::post('bulk-approve', [ReportCardController::class, 'bulkApprove'])->name('bulk-approve');
            Route::post('bulk-publish', [ReportCardController::class, 'bulkPublish'])->name('bulk-publish');
            Route::post('{report_card}/approve', [ReportCardController::class, 'approve'])->name('approve');
            Route::post('{report_card}/publish', [ReportCardController::class, 'publish'])->name('publish');
            Route::get('student/{student}', [ReportCardController::class, 'studentReports'])->name('student-reports');
        });
        Route::apiResource('report-cards', ReportCardController::class)->only(['index', 'show', 'update']);

        // Time Periods
        Route::apiResource('time-periods', TimePeriodController::class);

        // Timetables
        Route::prefix('timetables')->name('timetables.')->group(function () {
            Route::post('{timetable}/activate', [TimetableController::class, 'activate'])->name('activate');
            Route::post('{timetable}/slots', [TimetableController::class, 'addSlots'])->name('add-slots');
            Route::get('{timetable}/day/{day}', [TimetableController::class, 'getByDay'])->name('by-day');
            Route::get('teacher-schedule', [TimetableController::class, 'getTeacherSchedule'])->name('teacher-schedule');
        });
        Route::apiResource('timetables', TimetableController::class);

        // Attendance
        Route::prefix('attendance')->name('attendance.')->group(function () {
            Route::get('class-report', [AttendanceController::class, 'classAttendanceReport'])->name('class-report');
            Route::get('student/{student}/report', [AttendanceController::class, 'studentAttendanceReport'])->name('student-report');
            Route::get('sessions/{attendance_session}/students', [AttendanceController::class, 'getStudentsForAttendance'])->name('session-students');
            Route::post('sessions/{attendance_session}/record', [AttendanceController::class, 'recordAttendance'])->name('record');
        });
        Route::apiResource('attendance/sessions', AttendanceController::class)->parameters(['sessions' => 'attendance_session'])->only(['index', 'store', 'show']);

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

    });
});
