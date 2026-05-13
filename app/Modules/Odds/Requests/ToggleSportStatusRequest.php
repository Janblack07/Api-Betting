<?php

namespace App\Modules\Odds\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ToggleSportStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        return [
            'active' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'active.required' => 'El estado del deporte es obligatorio.',
            'active.boolean' => 'El estado del deporte debe ser verdadero o falso.',
        ];
    }
}
