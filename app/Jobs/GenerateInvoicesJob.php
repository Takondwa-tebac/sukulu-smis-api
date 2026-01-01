<?php

namespace App\Jobs;

use App\Models\FeeStructure;
use App\Models\InvoiceItem;
use App\Models\StudentEnrollment;
use App\Models\StudentInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(
        protected string $schoolId,
        protected string $academicYearId,
        protected string $termId,
        protected ?string $classId = null,
        protected ?string $feeStructureId = null
    ) {}

    public function handle(): void
    {
        Log::info('Starting invoice generation job', [
            'school_id' => $this->schoolId,
            'academic_year_id' => $this->academicYearId,
            'term_id' => $this->termId,
        ]);

        $feeStructure = $this->feeStructureId
            ? FeeStructure::with('items')->find($this->feeStructureId)
            : FeeStructure::with('items')
                ->where('school_id', $this->schoolId)
                ->where('academic_year_id', $this->academicYearId)
                ->where('term_id', $this->termId)
                ->when($this->classId, fn ($q) => $q->where('class_id', $this->classId))
                ->first();

        if (!$feeStructure) {
            Log::warning('No fee structure found for invoice generation');
            return;
        }

        $enrollmentsQuery = StudentEnrollment::where('academic_year_id', $this->academicYearId)
            ->where('status', 'active')
            ->when($this->classId, fn ($q) => $q->where('class_id', $this->classId))
            ->with('student');

        $count = 0;
        $enrollmentsQuery->chunk(50, function ($enrollments) use ($feeStructure, &$count) {
            foreach ($enrollments as $enrollment) {
                $this->generateForStudent($enrollment, $feeStructure);
                $count++;
            }
        });

        Log::info('Invoice generation job completed', ['count' => $count]);
    }

    protected function generateForStudent($enrollment, FeeStructure $feeStructure): void
    {
        DB::transaction(function () use ($enrollment, $feeStructure) {
            $existingInvoice = StudentInvoice::where('student_id', $enrollment->student_id)
                ->where('academic_year_id', $feeStructure->academic_year_id)
                ->where('term_id', $feeStructure->term_id)
                ->first();

            if ($existingInvoice) {
                return;
            }

            $totalAmount = $feeStructure->items->sum('amount');

            $invoice = StudentInvoice::create([
                'school_id' => $feeStructure->school_id,
                'student_id' => $enrollment->student_id,
                'academic_year_id' => $feeStructure->academic_year_id,
                'term_id' => $feeStructure->term_id,
                'invoice_number' => StudentInvoice::generateInvoiceNumber($feeStructure->school_id),
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
                'total_amount' => $totalAmount,
                'paid_amount' => 0,
                'balance' => $totalAmount,
                'status' => StudentInvoice::STATUS_PENDING,
            ]);

            foreach ($feeStructure->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'fee_category_id' => $item->fee_category_id,
                    'description' => $item->description,
                    'amount' => $item->amount,
                ]);
            }
        });
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Invoice generation job failed', [
            'school_id' => $this->schoolId,
            'error' => $exception->getMessage(),
        ]);
    }
}
