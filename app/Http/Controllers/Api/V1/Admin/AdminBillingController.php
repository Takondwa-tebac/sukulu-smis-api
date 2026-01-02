<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RecordTenantPaymentRequest;
use App\Http\Requests\Admin\StoreTenantInvoiceRequest;
use App\Http\Requests\Admin\UpdateTenantInvoiceRequest;
use App\Http\Resources\Admin\TenantInvoiceResource;
use App\Http\Resources\Admin\TenantPaymentResource;
use App\Jobs\SendTenantInvoiceJob;
use App\Models\School;
use App\Models\TenantInvoice;
use App\Models\TenantInvoiceItem;
use App\Models\TenantPayment;
use App\Services\TenantInvoicePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * @group Admin - Billing
 *
 * APIs for super-admin to manage tenant (school) billing and invoices.
 * These endpoints require the `super-admin` role.
 */
class AdminBillingController extends Controller
{
    /**
     * Get billing statistics
     *
     * Get overall billing statistics for the platform.
     *
     * @authenticated
     * @response 200 scenario="Success" {"total_revenue": 1000000, "pending_invoices": 5, ...}
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_revenue' => (float) TenantPayment::sum('amount'),
            'revenue_this_month' => (float) TenantPayment::whereMonth('payment_date', now()->month)
                ->whereYear('payment_date', now()->year)
                ->sum('amount'),
            'pending_invoices' => TenantInvoice::whereIn('status', [
                TenantInvoice::STATUS_SENT,
                TenantInvoice::STATUS_PARTIALLY_PAID,
            ])->count(),
            'overdue_invoices' => TenantInvoice::where('due_date', '<', now())
                ->whereIn('status', [
                    TenantInvoice::STATUS_SENT,
                    TenantInvoice::STATUS_PARTIALLY_PAID,
                ])->count(),
            'total_outstanding' => (float) TenantInvoice::unpaid()->sum('balance'),
            'active_subscriptions' => School::where('status', 'active')
                ->where('subscription_expires_at', '>', now())
                ->count(),
            'invoices_this_month' => TenantInvoice::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        return response()->json(['data' => $stats]);
    }

    /**
     * List all tenant invoices
     *
     * Get a paginated list of all tenant invoices.
     *
     * @authenticated
     * @queryParam status string Filter by status. Example: sent
     * @queryParam school_id string Filter by school. Example: uuid
     * @queryParam per_page integer Items per page. Default: 15. Example: 20
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = TenantInvoice::query()
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->school_id, fn ($q, $id) => $q->where('school_id', $id))
            ->when($request->boolean('unpaid'), fn ($q) => $q->unpaid())
            ->when($request->boolean('overdue'), fn ($q) => $q->overdue())
            ->with(['school'])
            ->orderByDesc('invoice_date');

        return TenantInvoiceResource::collection($query->paginate($request->input('per_page', 15)));
    }

    /**
     * Create a tenant invoice
     *
     * Create a new invoice for a school/tenant.
     *
     * @authenticated
     */
    public function store(StoreTenantInvoiceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $invoice = DB::transaction(function () use ($validated, $request) {
            $invoiceData = collect($validated)->except('items')->toArray();
            $invoiceData['invoice_date'] = $invoiceData['invoice_date'] ?? now();
            $invoiceData['created_by'] = $request->user()->id;

            $invoice = TenantInvoice::create($invoiceData);

            foreach ($validated['items'] as $item) {
                TenantInvoiceItem::create([
                    'tenant_invoice_id' => $invoice->id,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['unit_price'],
                    'amount' => ($item['quantity'] ?? 1) * $item['unit_price'],
                ]);
            }

            $invoice->recalculateTotals();

            return $invoice;
        });

        return (new TenantInvoiceResource($invoice->load(['school', 'items'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get a tenant invoice
     *
     * Get details of a specific tenant invoice.
     *
     * @authenticated
     */
    public function show(TenantInvoice $tenantInvoice): TenantInvoiceResource
    {
        return new TenantInvoiceResource(
            $tenantInvoice->load(['school', 'items', 'payments', 'createdBy'])
        );
    }

    /**
     * Update a tenant invoice
     *
     * Update a draft tenant invoice.
     *
     * @authenticated
     */
    public function update(UpdateTenantInvoiceRequest $request, TenantInvoice $tenantInvoice): JsonResponse
    {
        if ($tenantInvoice->status !== TenantInvoice::STATUS_DRAFT) {
            return response()->json([
                'message' => 'Only draft invoices can be edited.',
            ], 422);
        }

        $tenantInvoice->update($request->validated());
        $tenantInvoice->recalculateTotals();

        return response()->json([
            'message' => 'Invoice updated successfully.',
            'data' => new TenantInvoiceResource($tenantInvoice),
        ]);
    }

    /**
     * Delete a tenant invoice
     *
     * Delete a draft tenant invoice.
     *
     * @authenticated
     */
    public function destroy(TenantInvoice $tenantInvoice): JsonResponse
    {
        if ($tenantInvoice->status !== TenantInvoice::STATUS_DRAFT) {
            return response()->json([
                'message' => 'Only draft invoices can be deleted.',
            ], 422);
        }

        $tenantInvoice->delete();

        return response()->json(['message' => 'Invoice deleted successfully.']);
    }

    /**
     * Send a tenant invoice
     *
     * Mark invoice as sent and email to school with PDF attachment.
     *
     * @authenticated
     */
    public function send(TenantInvoice $tenantInvoice): JsonResponse
    {
        if ($tenantInvoice->status !== TenantInvoice::STATUS_DRAFT) {
            return response()->json([
                'message' => 'Only draft invoices can be sent.',
            ], 422);
        }

        $tenantInvoice->update([
            'status' => TenantInvoice::STATUS_SENT,
            'sent_at' => now(),
        ]);

        // Dispatch job to generate PDF and send email
        SendTenantInvoiceJob::dispatch($tenantInvoice->id);

        return response()->json([
            'message' => 'Invoice sent successfully. Email will be delivered shortly.',
            'data' => new TenantInvoiceResource($tenantInvoice),
        ]);
    }

    /**
     * Download invoice PDF
     *
     * Generate and download the invoice as a PDF.
     *
     * @authenticated
     */
    public function downloadPdf(TenantInvoice $tenantInvoice, TenantInvoicePdfService $pdfService)
    {
        return $pdfService->download($tenantInvoice);
    }

    /**
     * Resend invoice email
     *
     * Resend the invoice email to the school.
     *
     * @authenticated
     */
    public function resend(TenantInvoice $tenantInvoice): JsonResponse
    {
        if ($tenantInvoice->status === TenantInvoice::STATUS_DRAFT) {
            return response()->json([
                'message' => 'Cannot resend a draft invoice. Please send it first.',
            ], 422);
        }

        if ($tenantInvoice->status === TenantInvoice::STATUS_VOID) {
            return response()->json([
                'message' => 'Cannot resend a voided invoice.',
            ], 422);
        }

        // Dispatch job to generate PDF and send email
        SendTenantInvoiceJob::dispatch($tenantInvoice->id);

        return response()->json([
            'message' => 'Invoice email queued for delivery.',
            'data' => new TenantInvoiceResource($tenantInvoice),
        ]);
    }

    /**
     * Mark invoice as paid
     *
     * Quick action to mark an invoice as fully paid.
     *
     * @authenticated
     */
    public function markPaid(Request $request, TenantInvoice $tenantInvoice): JsonResponse
    {
        if ($tenantInvoice->isPaid()) {
            return response()->json([
                'message' => 'Invoice is already paid.',
            ], 422);
        }

        $request->validate([
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($tenantInvoice, $request) {
            // Create payment for remaining balance
            TenantPayment::create([
                'tenant_invoice_id' => $tenantInvoice->id,
                'school_id' => $tenantInvoice->school_id,
                'amount' => $tenantInvoice->balance,
                'currency' => $tenantInvoice->currency,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'payment_date' => now(),
                'notes' => $request->notes,
                'recorded_by' => $request->user()->id,
            ]);
        });

        return response()->json([
            'message' => 'Invoice marked as paid.',
            'data' => new TenantInvoiceResource($tenantInvoice->fresh(['school', 'payments'])),
        ]);
    }

    /**
     * Void a tenant invoice
     *
     * Void an invoice that has no payments.
     *
     * @authenticated
     */
    public function void(TenantInvoice $tenantInvoice): JsonResponse
    {
        if ($tenantInvoice->amount_paid > 0) {
            return response()->json([
                'message' => 'Cannot void an invoice with payments.',
            ], 422);
        }

        $tenantInvoice->update(['status' => TenantInvoice::STATUS_VOID]);

        return response()->json([
            'message' => 'Invoice voided successfully.',
            'data' => new TenantInvoiceResource($tenantInvoice),
        ]);
    }

    /**
     * Record a payment
     *
     * Record a payment against a tenant invoice.
     *
     * @authenticated
     */
    public function recordPayment(RecordTenantPaymentRequest $request, TenantInvoice $tenantInvoice): JsonResponse
    {
        if ($tenantInvoice->isPaid()) {
            return response()->json([
                'message' => 'Invoice is already fully paid.',
            ], 422);
        }

        $validated = $request->validated();

        $payment = TenantPayment::create([
            'tenant_invoice_id' => $tenantInvoice->id,
            'school_id' => $tenantInvoice->school_id,
            'amount' => $validated['amount'],
            'currency' => $validated['currency'] ?? $tenantInvoice->currency,
            'payment_method' => $validated['payment_method'] ?? null,
            'reference_number' => $validated['reference_number'] ?? null,
            'payment_date' => $validated['payment_date'],
            'notes' => $validated['notes'] ?? null,
            'recorded_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Payment recorded successfully.',
            'data' => [
                'payment' => new TenantPaymentResource($payment),
                'invoice' => new TenantInvoiceResource($tenantInvoice->fresh()),
            ],
        ], 201);
    }

    /**
     * Get school billing details
     *
     * Get billing summary and invoices for a specific school.
     *
     * @authenticated
     */
    public function schoolBilling(School $school): JsonResponse
    {
        $invoices = TenantInvoice::where('school_id', $school->id)
            ->with('items')
            ->orderByDesc('invoice_date')
            ->get();

        $totalPaid = TenantPayment::where('school_id', $school->id)->sum('amount');
        $totalOutstanding = $invoices->where('status', '!=', TenantInvoice::STATUS_PAID)
            ->where('status', '!=', TenantInvoice::STATUS_VOID)
            ->sum('balance');

        return response()->json([
            'data' => [
                'school' => [
                    'id' => $school->id,
                    'name' => $school->name,
                    'code' => $school->code,
                    'email' => $school->email,
                    'status' => $school->status,
                ],
                'subscription' => [
                    'plan' => $school->subscription_plan,
                    'status' => $school->subscription_expires_at && $school->subscription_expires_at->isFuture() 
                        ? 'active' 
                        : 'expired',
                    'starts_at' => $school->created_at?->toDateString(),
                    'ends_at' => $school->subscription_expires_at?->toDateString(),
                    'auto_renew' => false,
                ],
                'total_paid' => (float) $totalPaid,
                'total_outstanding' => (float) $totalOutstanding,
                'invoices' => TenantInvoiceResource::collection($invoices),
            ],
        ]);
    }
}
