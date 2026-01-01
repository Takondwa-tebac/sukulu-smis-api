<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fee\GenerateInvoiceRequest;
use App\Http\Requests\Fee\StoreInvoiceRequest;
use App\Http\Requests\Fee\UpdateInvoiceRequest;
use App\Http\Resources\Fee\StudentInvoiceResource;
use App\Models\FeeStructure;
use App\Models\InvoiceItem;
use App\Models\Student;
use App\Models\StudentInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * @group Student Invoices
 *
 * APIs for managing student invoices
 */
class StudentInvoiceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = StudentInvoice::query()
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->student_id, fn ($q, $id) => $q->where('student_id', $id))
            ->when($request->academic_year_id, fn ($q, $id) => $q->where('academic_year_id', $id))
            ->when($request->term_id, fn ($q, $id) => $q->where('term_id', $id))
            ->when($request->boolean('unpaid'), fn ($q) => $q->unpaid())
            ->with(['student', 'academicYear', 'term'])
            ->orderByDesc('invoice_date');

        return StudentInvoiceResource::collection($query->paginate($request->input('per_page', 15)));
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $invoice = DB::transaction(function () use ($validated, $request) {
            $invoiceData = collect($validated)->except('items')->toArray();
            $invoiceData['school_id'] = $request->user()->school_id;

            $invoice = StudentInvoice::create($invoiceData);

            foreach ($validated['items'] as $item) {
                InvoiceItem::create([
                    'student_invoice_id' => $invoice->id,
                    'fee_category_id' => $item['fee_category_id'],
                    'description' => $item['description'],
                    'quantity' => $item['quantity'] ?? 1,
                    'unit_price' => $item['unit_price'],
                    'amount' => ($item['quantity'] ?? 1) * $item['unit_price'],
                ]);
            }

            $invoice->recalculateTotals();

            return $invoice;
        });

        return (new StudentInvoiceResource($invoice->load(['student', 'items.feeCategory'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(StudentInvoice $studentInvoice): StudentInvoiceResource
    {
        return new StudentInvoiceResource(
            $studentInvoice->load(['student', 'academicYear', 'term', 'items.feeCategory', 'payments'])
        );
    }

    public function update(UpdateInvoiceRequest $request, StudentInvoice $studentInvoice): JsonResponse
    {
        if ($studentInvoice->status !== StudentInvoice::STATUS_DRAFT) {
            return response()->json([
                'message' => 'Only draft invoices can be edited.',
            ], 422);
        }

        $studentInvoice->update($request->validated());
        $studentInvoice->recalculateTotals();

        return response()->json([
            'message' => 'Invoice updated successfully.',
            'data' => new StudentInvoiceResource($studentInvoice),
        ]);
    }

    public function destroy(StudentInvoice $studentInvoice): JsonResponse
    {
        if ($studentInvoice->status !== StudentInvoice::STATUS_DRAFT) {
            return response()->json([
                'message' => 'Only draft invoices can be deleted.',
            ], 422);
        }

        $studentInvoice->delete();

        return response()->json(['message' => 'Invoice deleted successfully.']);
    }

    public function send(StudentInvoice $studentInvoice): JsonResponse
    {
        if ($studentInvoice->status !== StudentInvoice::STATUS_DRAFT) {
            return response()->json([
                'message' => 'Only draft invoices can be sent.',
            ], 422);
        }

        $studentInvoice->update(['status' => StudentInvoice::STATUS_SENT]);

        return response()->json([
            'message' => 'Invoice sent successfully.',
            'data' => new StudentInvoiceResource($studentInvoice),
        ]);
    }

    public function void(Request $request, StudentInvoice $studentInvoice): JsonResponse
    {
        if ($studentInvoice->amount_paid > 0) {
            return response()->json([
                'message' => 'Cannot void an invoice with payments.',
            ], 422);
        }

        $studentInvoice->update(['status' => StudentInvoice::STATUS_VOID]);

        return response()->json([
            'message' => 'Invoice voided successfully.',
            'data' => new StudentInvoiceResource($studentInvoice),
        ]);
    }

    public function generateFromFeeStructure(GenerateInvoiceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $student = Student::with('currentEnrollment')->findOrFail($validated['student_id']);

        if (!$student->currentEnrollment) {
            return response()->json([
                'message' => 'Student is not enrolled in any class.',
            ], 422);
        }

        $feeStructures = FeeStructure::where('academic_year_id', $validated['academic_year_id'])
            ->where(function ($q) use ($validated) {
                $q->whereNull('term_id')
                    ->orWhere('term_id', $validated['term_id'] ?? null);
            })
            ->where(function ($q) use ($student) {
                $q->whereNull('class_id')
                    ->orWhere('class_id', $student->currentEnrollment->class_id);
            })
            ->where('is_active', true)
            ->with('feeCategory')
            ->get();

        if ($feeStructures->isEmpty()) {
            return response()->json([
                'message' => 'No fee structures found for this student.',
            ], 422);
        }

        $invoice = DB::transaction(function () use ($validated, $feeStructures, $request) {
            $invoice = StudentInvoice::create([
                'school_id' => $request->user()->school_id,
                'student_id' => $validated['student_id'],
                'academic_year_id' => $validated['academic_year_id'],
                'term_id' => $validated['term_id'] ?? null,
                'invoice_date' => now(),
                'due_date' => $validated['due_date'],
            ]);

            foreach ($feeStructures as $structure) {
                InvoiceItem::create([
                    'student_invoice_id' => $invoice->id,
                    'fee_structure_id' => $structure->id,
                    'fee_category_id' => $structure->fee_category_id,
                    'description' => $structure->feeCategory->name,
                    'quantity' => 1,
                    'unit_price' => $structure->amount,
                    'amount' => $structure->amount,
                ]);
            }

            $invoice->recalculateTotals();

            return $invoice;
        });

        return (new StudentInvoiceResource($invoice->load(['student', 'items.feeCategory'])))
            ->response()
            ->setStatusCode(201);
    }

    public function studentBalance(Student $student): JsonResponse
    {
        $totalInvoiced = $student->invoices()->sum('total_amount');
        $totalPaid = $student->invoices()->sum('amount_paid');
        $balance = $totalInvoiced - $totalPaid;

        $unpaidInvoices = $student->invoices()
            ->unpaid()
            ->with('items.feeCategory')
            ->get();

        return response()->json([
            'student_id' => $student->id,
            'total_invoiced' => (float) $totalInvoiced,
            'total_paid' => (float) $totalPaid,
            'balance' => (float) $balance,
            'unpaid_invoices' => StudentInvoiceResource::collection($unpaidInvoices),
        ]);
    }
}
