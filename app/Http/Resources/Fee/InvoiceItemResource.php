<?php

namespace App\Http\Resources\Fee;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_invoice_id' => $this->student_invoice_id,
            'fee_structure_id' => $this->fee_structure_id,
            'fee_category_id' => $this->fee_category_id,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'amount' => (float) $this->amount,
            'fee_category' => new FeeCategoryResource($this->whenLoaded('feeCategory')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
