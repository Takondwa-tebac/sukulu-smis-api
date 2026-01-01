<?php

namespace App\Http\Resources\Fee;

use App\Http\Resources\Student\StudentResource;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'student_id' => $this->student_id,
            'student_invoice_id' => $this->student_invoice_id,
            'payment_number' => $this->payment_number,
            'payment_date' => $this->payment_date?->toDateString(),
            'amount' => (float) $this->amount,
            'payment_method' => $this->payment_method,
            'reference_number' => $this->reference_number,
            'bank_name' => $this->bank_name,
            'cheque_number' => $this->cheque_number,
            'mobile_money_number' => $this->mobile_money_number,
            'transaction_id' => $this->transaction_id,
            'status' => $this->status,
            'notes' => $this->notes,
            'unallocated_amount' => $this->getUnallocatedAmount(),
            'student' => new StudentResource($this->whenLoaded('student')),
            'invoice' => new StudentInvoiceResource($this->whenLoaded('invoice')),
            'received_by' => new UserResource($this->whenLoaded('receivedByUser')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
