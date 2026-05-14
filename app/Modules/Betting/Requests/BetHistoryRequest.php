<?php

namespace App\Modules\Betting\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BetHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('customer') === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:pending,accepted,won,lost,cancelled,rejected,refunded'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
