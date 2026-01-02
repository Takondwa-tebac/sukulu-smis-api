<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Student;
use App\Models\TenantInvoice;
use App\Models\TenantPayment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group Admin - Analytics
 *
 * APIs for super-admin to view platform-wide analytics and statistics.
 * These endpoints require the `super-admin` role.
 */
class AdminAnalyticsController extends Controller
{
    /**
     * Get platform analytics
     *
     * Get comprehensive analytics data for the platform including
     * school growth, revenue trends, and user statistics.
     *
     * @authenticated
     * @response 200 scenario="Success" {"school_growth": [], "revenue_data": [], "schools_by_status": []}
     */
    public function index(Request $request): JsonResponse
    {
        $months = $request->input('months', 12);

        return response()->json([
            'school_growth' => $this->getSchoolGrowth($months),
            'revenue_data' => $this->getRevenueData($months),
            'schools_by_status' => $this->getSchoolsByStatus(),
            'schools_by_type' => $this->getSchoolsByType(),
            'schools_by_plan' => $this->getSchoolsByPlan(),
        ]);
    }

    /**
     * Get dashboard statistics
     *
     * Get summary statistics for the admin dashboard.
     *
     * @authenticated
     */
    public function dashboard(): JsonResponse
    {
        $now = now();
        $lastMonth = now()->subMonth();

        $totalSchools = School::count();
        $schoolsLastMonth = School::where('created_at', '<', $lastMonth)->count();
        $schoolsGrowth = $schoolsLastMonth > 0 
            ? round((($totalSchools - $schoolsLastMonth) / $schoolsLastMonth) * 100, 1) 
            : 0;

        $totalUsers = User::count();
        $totalStudents = Student::count();
        $totalTeachers = User::whereHas('roles', fn ($q) => $q->where('name', 'teacher'))->count();

        $monthlyRevenue = TenantPayment::whereMonth('payment_date', $now->month)
            ->whereYear('payment_date', $now->year)
            ->sum('amount');

        $lastMonthRevenue = TenantPayment::whereMonth('payment_date', $lastMonth->month)
            ->whereYear('payment_date', $lastMonth->year)
            ->sum('amount');

        $revenueGrowth = $lastMonthRevenue > 0 
            ? round((($monthlyRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1) 
            : 0;

        return response()->json([
            'data' => [
                'total_schools' => $totalSchools,
                'active_schools' => School::where('status', 'active')->count(),
                'pending_schools' => School::where('status', 'pending')->count(),
                'suspended_schools' => School::where('status', 'suspended')->count(),
                'total_users' => $totalUsers,
                'total_students' => $totalStudents,
                'total_teachers' => $totalTeachers,
                'monthly_revenue' => (float) $monthlyRevenue,
                'schools_this_month' => School::whereMonth('created_at', $now->month)
                    ->whereYear('created_at', $now->year)
                    ->count(),
                'schools_growth_percentage' => $schoolsGrowth,
                'revenue_growth_percentage' => $revenueGrowth,
                'expiring_subscriptions' => School::where('subscription_expires_at', '<=', now()->addDays(30))
                    ->where('subscription_expires_at', '>', now())
                    ->count(),
            ],
        ]);
    }

    /**
     * Get school growth data over time
     */
    private function getSchoolGrowth(int $months): array
    {
        $data = [];
        $startDate = now()->subMonths($months - 1)->startOfMonth();

        for ($i = 0; $i < $months; $i++) {
            $date = $startDate->copy()->addMonths($i);
            $endOfMonth = $date->copy()->endOfMonth();

            $schoolCount = School::where('created_at', '<=', $endOfMonth)->count();
            $studentCount = Student::where('created_at', '<=', $endOfMonth)->count();

            $data[] = [
                'month' => $date->format('M'),
                'year' => $date->format('Y'),
                'schools' => $schoolCount,
                'students' => $studentCount,
            ];
        }

        return $data;
    }

    /**
     * Get revenue data over time
     */
    private function getRevenueData(int $months): array
    {
        $data = [];
        $startDate = now()->subMonths($months - 1)->startOfMonth();

        for ($i = 0; $i < $months; $i++) {
            $date = $startDate->copy()->addMonths($i);

            $revenue = TenantPayment::whereMonth('payment_date', $date->month)
                ->whereYear('payment_date', $date->year)
                ->sum('amount');

            $invoiceCount = TenantInvoice::whereMonth('created_at', $date->month)
                ->whereYear('created_at', $date->year)
                ->count();

            $data[] = [
                'month' => $date->format('M'),
                'year' => $date->format('Y'),
                'revenue' => (float) $revenue,
                'invoices' => $invoiceCount,
            ];
        }

        return $data;
    }

    /**
     * Get schools grouped by status
     */
    private function getSchoolsByStatus(): array
    {
        $statusColors = [
            'active' => '#22c55e',
            'pending' => '#eab308',
            'suspended' => '#ef4444',
            'inactive' => '#6b7280',
        ];

        return School::selectRaw('status, count(*) as value')
            ->groupBy('status')
            ->get()
            ->map(fn ($item) => [
                'name' => ucfirst($item->status),
                'value' => $item->value,
                'color' => $statusColors[$item->status] ?? '#6b7280',
            ])
            ->toArray();
    }

    /**
     * Get schools grouped by type
     */
    private function getSchoolsByType(): array
    {
        return School::selectRaw('type, count(*) as value')
            ->groupBy('type')
            ->get()
            ->map(fn ($item) => [
                'name' => ucfirst($item->type ?? 'Unknown'),
                'value' => $item->value,
            ])
            ->toArray();
    }

    /**
     * Get schools grouped by subscription plan
     */
    private function getSchoolsByPlan(): array
    {
        $planColors = [
            'free' => '#6b7280',
            'basic' => '#3b82f6',
            'premium' => '#8b5cf6',
            'enterprise' => '#f59e0b',
        ];

        return School::selectRaw('subscription_plan, count(*) as value')
            ->groupBy('subscription_plan')
            ->get()
            ->map(fn ($item) => [
                'name' => ucfirst($item->subscription_plan ?? 'Free'),
                'value' => $item->value,
                'color' => $planColors[$item->subscription_plan] ?? '#6b7280',
            ])
            ->toArray();
    }
}
