<?php

namespace App\Http\Requests\Auctions;

use Illuminate\Foundation\Http\FormRequest;

class PlaceBidRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }
    public function rules(): array { return ['amount' => ['required', 'regex:/^\d{1,16}(\.\d{1,2})?$/']]; }
}
