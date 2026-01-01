<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fee\AllocatePaymentRequest;
use App\Http\Requests\Fee\StorePaymentRequest;
use App\Http\Resources\Fee\PaymentResource;
use App\Models\Payment;
use App\Models\StudentInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * @group Payments
 *
 * APIs for managing payments
 */
class PaymentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Payment::query()
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->student_id, fn ($q, $id) => $q->where('student_id', $id))
            ->when($request->payment_method, fn ($q, $method) => $q->where('payment_method', $method))
            ->when($request->from_date, fn ($q, $date) => $q->where('payment_date', '>=', $date))
            ->when($request->to_date, fn ($q, $date) => $q->where('payment_date', '<=', $date))
            ->with(['student', 'invoice', 'receivedByUser'])
            ->orderByDesc('payment_date');

        return PaymentResource::collection($query->paginate($request->input('per_page', 15)));
    }

    public function store(StorePaymentRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $payment = DB::transaction(function () use ($validated, $request) {
            $paymentData = collect($validated)->except('allocations')->toArray();
            $paymentData['school_id'] = $request->user()->school_id;
            $paymentData['received_by'] = $request->user()->id;

            $payment = Payment::create($paymentData);

            // Auto-allocate to specified invoice if provided
            if (!empty($validated['student_invoice_id']) && empty($validated['allocations'])) {
                $invoice = StudentInvoice::find($validated['student_invoice_id']);
                if ($invoice && $invoice->balance > 0) {
                    $allocateAmount = min($validated['amount'], $invoice->balance);
                    $payment->allocateToInvoice($invoice, $allocateAmount);
                }
            }

            // Process manual allocations
            if (!empty($validated['allocations'])) {
                foreach ($validated['allocations'] as $allocation) {
                    $invoice = StudentInvoice::find($allocation['invoice_id']);
                    if ($invoice) {
                        $payment->allocateToInvoice($invoice, $allocation['amount']);
                    }
                }
            }

            return $payment;
        });

        return (new PaymentResource($payment->load(['student', 'invoice', 'allocations'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Payment $payment): PaymentResource
    {
        return new PaymentResource(
            $payment->load(['student', 'invoice', 'receivedByUser', 'allocations.invoice'])
        );
    }

    public function allocate(AllocatePaymentRequest $request, Payment $payment): JsonResponse
    {
        $validated = $request->validated();

        $unallocated = $payment->getUnallocatedAmount();
        if ($validated['amount'] > $unallocated) {
            return response()->json([
                'message' => "Cannot allocate more than the unallocated amount ({$unallocated}).",
            ], 422);
        }

        $invoice = StudentInvoice::findOrFail($validated['invoice_id']);

        if ($validated['amount'] > $invoice->balance) {
            return response()->json([
                'message' => "Cannot allocate more than the invoice balance ({$invoice->balance}).",
            ], 422);
        }

        $payment->allocateToInvoice($invoice, $validated['amount']);

        return response()->json([
            'message' => 'Payment allocated successfully.',
            'data' => new PaymentResource($payment->load('allocations')),
        ]);
    }

    public function refund(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->status !== Payment::STATUS_COMPLETED) {
            return response()->json([
                'message' => 'Only completed payments can be refunded.',
            ], 422);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($payment, $validated) {
            // Remove allocations and recalculate invoice totals
            foreach ($payment->allocations as $allocation) {
                $invoice = $allocation->invoice;
                $allocation->delete();
                $invoice->recalculateTotals();
            }

            $payment->update([
                'status' => Payment::STATUS_REFUNDED,
                'notes' => ($payment->notes ? $payment->notes . "\n" : '') . "Refunded: " . $validated['reason'],
            ]);
        });

        return response()->json([
            'message' => 'Payment refunded successfully.',
            'data' => new PaymentResource($payment),
        ]);
    }

    public function receipt(Payment $payment): JsonResponse
    {
        return response()->json([
            'receipt' => [
                'payment_number' => $payment->payment_number,
                'payment_date' => $payment->payment_date->toDateString(),
                'amount' => (float) $payment->amount,
                'payment_method' => $payment->payment_method,
                'reference_number' => $payment->reference_number,
                'student' => [
                    'id' => $payment->student->id,
                    'name' => $payment->student->full_name,
                    'admission_number' => $payment->student->admission_number,
                ],
                'allocations' => $payment->allocations->map(fn ($a) => [
                    'invoice_number' => $a->invoice->invoice_number,
                    'amount' => (float) $a->amount,
                ]),
                'received_by' => $payment->receivedByUser?->full_name,
            ],
        ]);
    }
}
