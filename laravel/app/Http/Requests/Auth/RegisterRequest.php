<?php

namespace App\Http\Requests\Auth;

use App\Domain\Core\Context\MarketplaceContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $countryId = app(MarketplaceContext::class)->id();

        return [
            'country_id' => ['required', 'integer', Rule::in([$countryId])],
            'city_id' => ['required', 'integer', Rule::exists('cities', 'id')->where(fn ($query) => $query->where('country_id', $countryId)->where('is_active', true))],
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32', 'unique:users,phone'],
            'password' => ['required', 'string', 'confirmed', 'min:12', 'max:128'],
            'device_name' => ['required', 'string', 'max:80'],
        ];
    }
}
