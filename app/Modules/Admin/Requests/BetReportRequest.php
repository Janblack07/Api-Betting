<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BetReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'string', 'in:pending,accepted,won,lost,cancelled,rejected,refunded'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_from.required' => 'Debe seleccionar la fecha de inicio.',
            'date_to.required' => 'Debe seleccionar la fecha de fin.',
            'date_to.after_or_equal' => 'La fecha fin debe ser igual o posterior a la fecha inicio.',
        ];
    }
}
