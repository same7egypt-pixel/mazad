<?php

namespace App\Http\Requests\Products;

use App\Domain\Core\Context\MarketplaceContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('products.create') ?? false; }

    public function rules(): array
    {
        $countryId = app(MarketplaceContext::class)->id();

        return [
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')->where(fn ($q) => $q->where('country_id', $countryId)->where('is_active', true))],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where(fn ($q) => $q->where('is_active', true)->where(fn ($nested) => $nested->where('country_id', $countryId)->orWhereNull('country_id')))],
            'title' => ['required', 'string', 'min:4', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:20000'],
            'condition' => ['required', Rule::in(['new', 'like_new', 'good', 'fair', 'poor'])],
        ];
    }
}
