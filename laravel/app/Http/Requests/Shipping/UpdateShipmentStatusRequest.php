<?php

namespace App\Http\Requests\Shipping;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShipmentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('shipping.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['prepared', 'shipped', 'ready_for_pickup', 'delivered'])],
            'tracking_number' => ['nullable', 'string', 'max:191'],
        ];
    }
}
