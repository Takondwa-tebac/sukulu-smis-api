<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleEnabled
{
    protected array $moduleRouteMap = [
        'admissions' => ['admission-periods', 'admission-applications'],
        'fees' => ['fee-categories', 'fee-structures', 'invoices', 'payments'],
        'timetables' => ['timetables', 'time-periods'],
        'attendance' => ['attendance'],
        'notifications' => ['notifications'],
        'exams' => ['exams', 'exam-subjects', 'marks', 'student-marks'],
        'reports' => ['report-cards'],
        'discipline' => ['discipline'],
    ];

    public function handle(Request $request, Closure $next, ?string $module = null): Response
    {
        $user = $request->user();

        if (!$user || !$user->school_id) {
            return $next($request);
        }

        $school = $user->school;

        if (!$school) {
            return $next($request);
        }

        $moduleToCheck = $module ?? $this->detectModuleFromRoute($request);

        if (!$moduleToCheck) {
            return $next($request);
        }

        if (!$school->isModuleEnabled($moduleToCheck)) {
            return response()->json([
                'message' => "The {$moduleToCheck} module is not enabled for your school.",
                'module' => $moduleToCheck,
            ], 403);
        }

        return $next($request);
    }

    protected function detectModuleFromRoute(Request $request): ?string
    {
        $path = $request->path();

        foreach ($this->moduleRouteMap as $module => $routes) {
            foreach ($routes as $route) {
                if (str_contains($path, $route)) {
                    return $module;
                }
            }
        }

        return null;
    }
}
