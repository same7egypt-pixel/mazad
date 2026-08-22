<?php

namespace App\Http\Requests\Auctions;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuctionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('auctions.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'starting_price' => ['required', 'regex:/^\d{1,16}(\.\d{1,2})?$/'],
            'reserve_price' => ['nullable', 'regex:/^\d{1,16}(\.\d{1,2})?$/'],
            'minimum_increment' => ['required', 'regex:/^\d{1,16}(\.\d{1,2})?$/', 'not_in:0,0.0,0.00'],
            'start_time' => ['required', 'date', 'after_or_equal:now'],
            'end_time' => ['required', 'date', 'after:start_time'],
        ];
    }
}
