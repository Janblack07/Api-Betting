<?php

namespace App\Modules\Betting\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ManualSettleBetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        return [
            'result' => ['required', 'string', 'in:won,lost,refunded'],
            'observation' => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'result.required' => 'Debe seleccionar el resultado de la liquidación.',
            'result.in' => 'El resultado debe ser won, lost o refunded.',
            'observation.required' => 'La observación es obligatoria para una liquidación manual.',
            'observation.min' => 'La observación debe tener al menos 5 caracteres.',
        ];
    }
}
