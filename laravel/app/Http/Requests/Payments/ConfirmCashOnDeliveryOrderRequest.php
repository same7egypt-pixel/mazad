<?php

namespace App\Http\Requests\Payments;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmCashOnDeliveryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fulfilment_preference' => ['required', Rule::in(['external', 'self_pickup'])],
            'shipping_address' => ['nullable', 'array', 'required_if:fulfilment_preference,external'],
            'shipping_address.address_line' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['nullable', 'string', 'max:120'],
            'shipping_address.phone' => ['nullable', 'string', 'max:32'],
            'shipping_address.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
