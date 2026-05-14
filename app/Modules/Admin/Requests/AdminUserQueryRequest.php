<?php

namespace App\Modules\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminUserQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', 'in:active,blocked,inactive'],
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'string', 'in:admin,operator,customer'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
