<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:active,blocked,inactive'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Debe enviar el estado del usuario.',
            'status.in' => 'El estado debe ser active, blocked o inactive.',
        ];
    }
}
