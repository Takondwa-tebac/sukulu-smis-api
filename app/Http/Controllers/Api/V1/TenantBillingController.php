<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\TenantInvoiceResource;
use App\Http\Resources\Admin\TenantPaymentResource;
use App\Models\TenantInvoice;
use App\Models\TenantPayment;
use App\Services\TenantInvoicePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @group Billing (School)
 *
 * APIs for school admins to view their subscription invoices and billing history.
 * These endpoints are scoped to the authenticated user's school.
 */
class TenantBillingController extends Controller
{
    /**
     * Get billing summary
     *
     * Get billing summary for the current school including subscription status.
     *
     * @authenticated
     */
    public function summary(Request $request): JsonResponse
    {
        $school = $request->user()->school;

        if (!$school) {
            return response()->json([
                'message' => 'No school associated with this user.',
            ], 404);
        }

        $invoices = TenantInvoice::where('school_id', $school->id)->get();
        
        $totalPaid = TenantPayment::where('school_id', $school->id)->sum('amount');
        $totalOutstanding = $invoices
            ->whereNotIn('status', [TenantInvoice::STATUS_PAID, TenantInvoice::STATUS_VOID])
            ->sum('balance');
        
        $pendingInvoices = $invoices
            ->whereIn('status', [TenantInvoice::STATUS_SENT, TenantInvoice::STATUS_PARTIALLY_PAID])
            ->count();
        
        $overdueInvoices = $invoices
            ->filter(fn ($inv) => $inv->isOverdue())
            ->count();

        return response()->json([
            'data' => [
                'school' => [
                    'id' => $school->id,
                    'name' => $school->name,
                    'code' => $school->code,
                ],
                'subscription' => [
                    'plan' => $school->subscription_plan ?? 'free',
                    'status' => $school->subscription_expires_at && $school->subscription_expires_at->isFuture() 
                        ? 'active' 
                        : 'expired',
                    'expires_at' => $school->subscription_expires_at?->toDateString(),
                    'days_remaining' => $school->subscription_expires_at 
                        ? max(0, now()->diffInDays($school->subscription_expires_at, false))
                        : null,
                ],
                'billing' => [
                    'total_paid' => (float) $totalPaid,
                    'total_outstanding' => (float) $totalOutstanding,
                    'pending_invoices' => $pendingInvoices,
                    'overdue_invoices' => $overdueInvoices,
                ],
            ],
        ]);
    }

    /**
     * List invoices
     *
     * Get a paginated list of invoices for the current school.
     *
     * @authenticated
     * @queryParam status string Filter by status. Example: sent
     * @queryParam per_page integer Items per page. Default: 15. Example: 20
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $school = $request->user()->school;

        $query = TenantInvoice::where('school_id', $school->id)
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->boolean('unpaid'), fn ($q) => $q->unpaid())
            ->with(['items'])
            ->orderByDesc('invoice_date');

        return TenantInvoiceResource::collection($query->paginate($request->input('per_page', 15)));
    }

    /**
     * Get invoice details
     *
     * Get details of a specific invoice.
     *
     * @authenticated
     */
    public function show(Request $request, TenantInvoice $tenantInvoice): TenantInvoiceResource|JsonResponse
    {
        // Ensure the invoice belongs to the user's school
        if ($tenantInvoice->school_id !== $request->user()->school_id) {
            return response()->json([
                'message' => 'Invoice not found.',
            ], 404);
        }

        return new TenantInvoiceResource(
            $tenantInvoice->load(['items', 'payments'])
        );
    }

    /**
     * Download invoice PDF
     *
     * Download the invoice as a PDF.
     *
     * @authenticated
     */
    public function downloadPdf(Request $request, TenantInvoice $tenantInvoice, TenantInvoicePdfService $pdfService)
    {
        // Ensure the invoice belongs to the user's school
        if ($tenantInvoice->school_id !== $request->user()->school_id) {
            return response()->json([
                'message' => 'Invoice not found.',
            ], 404);
        }

        return $pdfService->download($tenantInvoice);
    }

    /**
     * List payments
     *
     * Get a paginated list of payments for the current school.
     *
     * @authenticated
     * @queryParam per_page integer Items per page. Default: 15. Example: 20
     */
    public function payments(Request $request): AnonymousResourceCollection
    {
        $school = $request->user()->school;

        $query = TenantPayment::where('school_id', $school->id)
            ->with(['invoice'])
            ->orderByDesc('payment_date');

        return TenantPaymentResource::collection($query->paginate($request->input('per_page', 15)));
    }
}
