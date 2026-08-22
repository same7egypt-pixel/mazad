<?php

namespace App\Http\Requests\Search;

use Illuminate\Foundation\Http\FormRequest;

class SearchListingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'min:2', 'max:120'],
            'city_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'condition' => ['nullable', 'in:new,like_new,good,fair,poor'],
            'price_min' => ['nullable', 'regex:/^\d{1,16}(\.\d{1,2})?$/'],
            'price_max' => ['nullable', 'regex:/^\d{1,16}(\.\d{1,2})?$/'],
        ];
    }
}
