<?php

namespace App\Modules\Betting\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncEventResultsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        return [
            'sport_key' => ['required', 'string', 'exists:sports,sport_key'],
            'days_from' => ['nullable', 'integer', 'min:1', 'max:3'],
        ];
    }

    public function messages(): array
    {
        return [
            'sport_key.required' => 'Debe indicar el deporte a sincronizar.',
            'sport_key.exists' => 'El deporte seleccionado no existe.',
            'days_from.min' => 'days_from debe ser mínimo 1.',
            'days_from.max' => 'days_from debe ser máximo 3.',
        ];
    }
}
