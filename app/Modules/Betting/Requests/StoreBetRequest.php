<?php

namespace App\Modules\Betting\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('customer') === true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'accept_odds_change' => ['nullable', 'boolean'],

            'selections' => ['required', 'array', 'min:1'],
            'selections.*.snapshot_id' => ['required', 'integer', 'exists:odds_snapshots,id'],
            'selections.*.expected_price' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'selections.required' => 'Debe seleccionar al menos una cuota.',
            'selections.*.snapshot_id.exists' => 'Una de las cuotas seleccionadas no existe.',
        ];
    }
}
