<?php

namespace App\Http\Requests\Shipping;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('shipping.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'fulfilment_type' => ['required', Rule::in(['internal', 'external', 'self_pickup'])],
            'provider_id' => ['nullable', 'integer', 'exists:shipping_providers,id'],
            'tracking_number' => ['nullable', 'string', 'max:191'],
            'shipping_address' => ['nullable', 'array'],
        ];
    }
}
