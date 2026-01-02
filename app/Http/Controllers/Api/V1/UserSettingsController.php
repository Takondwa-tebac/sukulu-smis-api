<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group User Settings
 *
 * APIs for managing user preferences and settings
 */
class UserSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get user settings
     *
     * Get current user settings and preferences.
     *
     * @authenticated
     *
     * @response 200 scenario="Success" {"user": {}, "settings": {}}
     * @response 404 scenario="Not Found" {"message": "User not found."}
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'middle_name' => $user->middle_name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'username' => $user->username,
                'roles' => $user->roles->pluck('name'),
                'school_id' => $user->school_id,
                'profile_photo_url' => $user->getFirstMediaUrl('profile_photo'),
            ],
            'settings' => $this->getDefaultSettings($user),
        ]);
    }

    /**
     * Update user settings
     *
     * Update user preferences and settings.
     *
     * @authenticated
     *
     * @bodyParam settings object required User settings object
     * @bodyParam settings.notifications.email boolean Email notifications enabled
     * @bodyParam settings.notifications.sms boolean SMS notifications enabled
     * @bodyParam settings.notifications.assignments boolean Assignment notifications enabled
     * @bodyParam settings.notifications.grades boolean Grade notifications enabled
     * @bodyParam settings.notifications.attendance boolean Attendance notifications enabled
     * @bodyParam settings.preferences.dark_mode boolean Dark mode enabled
     * @bodyParam settings.preferences.language string Preferred language
     * @bodyParam settings.preferences.timezone string Preferred timezone
     * @bodyParam settings.preferences.auto_save boolean Auto-save enabled
     * @bodyParam settings.profile.bio string User bio
     * @bodyParam settings.profile.phone string Phone number
     *
     * @response 200 scenario="Success" {"message": "Settings updated successfully.", "settings": {}}
     * @response 422 scenario="Validation Error" {"message": "The given data was invalid."}
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.notifications' => ['nullable', 'array'],
            'settings.notifications.email' => ['nullable', 'boolean'],
            'settings.notifications.sms' => ['nullable', 'boolean'],
            'settings.notifications.assignments' => ['nullable', 'boolean'],
            'settings.notifications.grades' => ['nullable', 'boolean'],
            'settings.notifications.attendance' => ['nullable', 'boolean'],
            'settings.notifications.fees' => ['nullable', 'boolean'],
            'settings.notifications.reports' => ['nullable', 'boolean'],
            'settings.preferences' => ['nullable', 'array'],
            'settings.preferences.dark_mode' => ['nullable', 'boolean'],
            'settings.preferences.language' => ['nullable', 'string', 'max:10'],
            'settings.preferences.timezone' => ['nullable', 'string', 'max:50'],
            'settings.preferences.auto_save' => ['nullable', 'boolean'],
            'settings.preferences.grade_scale' => ['nullable', 'string', 'max:20'],
            'settings.profile' => ['nullable', 'array'],
            'settings.profile.bio' => ['nullable', 'string', 'max:500'],
            'settings.profile.phone' => ['nullable', 'string', 'max:20'],
            'settings.profile.address' => ['nullable', 'string', 'max:255'],
        ]);

        // Update profile fields if provided
        if (isset($validated['settings']['profile'])) {
            $profileData = $validated['settings']['profile'];
            if (isset($profileData['phone'])) {
                $user->phone_number = $profileData['phone'];
            }
            unset($validated['settings']['profile']);
        }

        $currentSettings = $user->settings ?? [];
        $newSettings = array_merge($currentSettings, $validated['settings']);

        $user->settings = $newSettings;
        $user->save();

        return response()->json([
            'message' => 'Settings updated successfully.',
            'settings' => $newSettings,
        ]);
    }

    /**
     * Get default settings for user based on role
     */
    private function getDefaultSettings(User $user): array
    {
        $defaultSettings = [
            'notifications' => [
                'email' => true,
                'sms' => false,
                'assignments' => true,
                'grades' => true,
                'attendance' => true,
                'fees' => false,
                'reports' => true,
            ],
            'preferences' => [
                'dark_mode' => false,
                'language' => 'en',
                'timezone' => 'UTC',
                'auto_save' => true,
            ],
            'profile' => [
                'bio' => null,
                'phone' => $user->phone_number,
                'address' => null,
            ],
        ];

        // Role-specific defaults
        $userRole = $user->roles->first()?->name;
        
        switch ($userRole) {
            case 'teacher':
                $defaultSettings['preferences']['grade_scale'] = 'A-F';
                $defaultSettings['notifications']['students'] = true;
                break;
            case 'bursar':
                $defaultSettings['preferences']['currency'] = 'USD';
                $defaultSettings['notifications']['payments'] = true;
                break;
            case 'exams-officer':
                $defaultSettings['preferences']['auto_calculate_grades'] = true;
                $defaultSettings['notifications']['exams'] = true;
                break;
            case 'class-teacher':
                $defaultSettings['notifications']['discipline'] = true;
                $defaultSettings['notifications']['performance_alerts'] = true;
                break;
            case 'guardian':
                $defaultSettings['notifications']['children'] = true;
                $defaultSettings['notifications']['fee_reminders'] = true;
                break;
            case 'student':
                $defaultSettings['notifications']['study_reminders'] = true;
                $defaultSettings['preferences']['show_progress_charts'] = true;
                break;
        }

        return array_merge($defaultSettings, $user->settings ?? []);
    }
}
