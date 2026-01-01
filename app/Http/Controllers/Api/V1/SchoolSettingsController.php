<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group School Settings
 *
 * APIs for managing school settings, configuration, and module enablement
 */
class SchoolSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:settings.view')->only(['show', 'getModules']);
        $this->middleware('permission:settings.manage')->only(['update', 'updateSettings', 'toggleModule']);
    }

    /**
     * Get school settings
     *
     * Get current school information, settings, and subscription details.
     *
     * @authenticated
     *
     * @response 200 scenario="Success" {"school": {}, "settings": {}, "enabled_modules": [], "subscription": {}}
     * @response 404 scenario="Not Found" {"message": "School not found."}
     */
    public function show(Request $request): JsonResponse
    {
        $school = School::find($request->user()->school_id);

        if (!$school) {
            return response()->json(['message' => 'School not found.'], 404);
        }

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
                'postal_code' => $school->postal_code,
                'phone' => $school->phone,
                'email' => $school->email,
                'website' => $school->website,
                'motto' => $school->motto,
                'established_year' => $school->established_year,
                'registration_number' => $school->registration_number,
                'logo_url' => $school->getFirstMediaUrl('logo'),
                'banner_url' => $school->getFirstMediaUrl('banner'),
            ],
            'settings' => $school->settings ?? [],
            'enabled_modules' => $school->enabled_modules ?? [],
            'subscription' => [
                'plan' => $school->subscription_plan,
                'expires_at' => $school->subscription_expires_at?->toISOString(),
                'is_valid' => $school->hasValidSubscription(),
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $school = School::find($request->user()->school_id);

        if (!$school) {
            return response()->json(['message' => 'School not found.'], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'motto' => ['nullable', 'string', 'max:255'],
        ]);

        $school->update($validated);

        return response()->json([
            'message' => 'School settings updated successfully.',
            'school' => $school->fresh(),
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $school = School::find($request->user()->school_id);

        if (!$school) {
            return response()->json(['message' => 'School not found.'], 404);
        }

        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.grading_system_id' => ['nullable', 'uuid', 'exists:grading_systems,id'],
            'settings.academic_year_format' => ['nullable', 'string', 'max:50'],
            'settings.term_names' => ['nullable', 'array'],
            'settings.attendance_tracking' => ['nullable', 'boolean'],
            'settings.sms_notifications' => ['nullable', 'boolean'],
            'settings.email_notifications' => ['nullable', 'boolean'],
            'settings.report_card_template' => ['nullable', 'string'],
            'settings.invoice_prefix' => ['nullable', 'string', 'max:10'],
            'settings.receipt_prefix' => ['nullable', 'string', 'max:10'],
            'settings.currency' => ['nullable', 'string', 'max:3'],
            'settings.timezone' => ['nullable', 'string', 'max:50'],
        ]);

        $currentSettings = $school->settings ?? [];
        $newSettings = array_merge($currentSettings, $validated['settings']);

        $school->update(['settings' => $newSettings]);

        return response()->json([
            'message' => 'Settings updated successfully.',
            'settings' => $newSettings,
        ]);
    }

    public function getModules(Request $request): JsonResponse
    {
        $school = School::find($request->user()->school_id);

        if (!$school) {
            return response()->json(['message' => 'School not found.'], 404);
        }

        $allModules = [
            'academic_structure' => ['name' => 'Academic Structure', 'description' => 'Classes, streams, subjects management'],
            'students' => ['name' => 'Students', 'description' => 'Student enrollment and management'],
            'grading' => ['name' => 'Grading', 'description' => 'Grading systems and scales'],
            'exams' => ['name' => 'Exams', 'description' => 'Exam management and marks entry'],
            'reports' => ['name' => 'Reports', 'description' => 'Report cards and transcripts'],
            'admissions' => ['name' => 'Admissions', 'description' => 'Online admission applications'],
            'fees' => ['name' => 'Fees', 'description' => 'Fee structures, invoices, payments'],
            'timetables' => ['name' => 'Timetables', 'description' => 'Class schedules and timetables'],
            'attendance' => ['name' => 'Attendance', 'description' => 'Student attendance tracking'],
            'discipline' => ['name' => 'Discipline', 'description' => 'Disciplinary records'],
            'notifications' => ['name' => 'Notifications', 'description' => 'SMS and email notifications'],
        ];

        $enabledModules = $school->enabled_modules ?? [];

        $modules = [];
        foreach ($allModules as $key => $module) {
            $modules[$key] = array_merge($module, [
                'enabled' => $enabledModules[$key] ?? false,
            ]);
        }

        return response()->json(['modules' => $modules]);
    }

    public function toggleModule(Request $request): JsonResponse
    {
        $school = School::find($request->user()->school_id);

        if (!$school) {
            return response()->json(['message' => 'School not found.'], 404);
        }

        $validated = $request->validate([
            'module' => ['required', 'string'],
            'enabled' => ['required', 'boolean'],
        ]);

        $coreModules = ['academic_structure', 'students', 'grading'];
        if (in_array($validated['module'], $coreModules) && !$validated['enabled']) {
            return response()->json([
                'message' => 'Core modules cannot be disabled.',
            ], 422);
        }

        $modules = $school->enabled_modules ?? [];
        $modules[$validated['module']] = $validated['enabled'];

        $school->update(['enabled_modules' => $modules]);

        return response()->json([
            'message' => 'Module ' . ($validated['enabled'] ? 'enabled' : 'disabled') . ' successfully.',
            'enabled_modules' => $modules,
        ]);
    }
}
