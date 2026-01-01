<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class BulkPublishReportCardsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_card_ids' => ['required', 'array', 'min:1'],
            'report_card_ids.*' => ['uuid', 'exists:report_cards,id'],
        ];
    }
}
