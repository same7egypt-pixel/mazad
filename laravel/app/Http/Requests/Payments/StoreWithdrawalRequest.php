<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;

class StoreWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('wallet.withdraw') ?? false;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'regex:/^\d{1,16}(\.\d{1,2})?$/'],
            'destination_type' => ['required', 'string', 'max:64'],
            'destination_details' => ['required', 'array', 'min:1'],
        ];
    }
}
