<?php

namespace App\Modules\Odds\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SportEventsQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sport_key' => ['nullable', 'string', 'max:100', 'exists:sports,sport_key'],
            'status' => ['nullable', 'string', 'in:scheduled,live'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'sport_key.exists' => 'El deporte seleccionado no existe.',
            'status.in' => 'El estado debe ser scheduled o live.',
            'date_to.after_or_equal' => 'La fecha final debe ser mayor o igual a la fecha inicial.',
        ];
    }
}
