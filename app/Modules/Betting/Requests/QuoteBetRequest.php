<?php

namespace App\Modules\Betting\Requests;

use Illuminate\Foundation\Http\FormRequest;

class QuoteBetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('customer') === true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'selections' => ['required', 'array', 'min:1'],
            'selections.*.snapshot_id' => ['required', 'integer', 'exists:odds_snapshots,id'],
            'selections.*.expected_price' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }
}
