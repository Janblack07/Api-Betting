<?php

namespace App\Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModerateUserAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['blocked', 'suspended', 'active'])],
            'reason' => [
                'nullable',
                'string',
                'max:2000',
                Rule::requiredIf(fn () => in_array((string) $this->input('status'), ['blocked', 'suspended'], true)),
            ],
        ];
    }
}
