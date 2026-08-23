<?php

namespace App\Http\Controllers\Api;

use App\Domain\Core\Context\MarketplaceContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, MarketplaceContext $context): JsonResponse
    {
        $user = User::query()->create($request->safe()->except(['password', 'password_confirmation', 'device_name']) + [
            'password' => Hash::make($request->string('password')->toString()),
            'country_id' => $context->id(),
        ]);

        $user->assignRole('USER');
        event(new Registered($user));

        return response()->json([
            'user' => $user,
            'token' => $user->createToken($request->string('device_name')->toString(), ['marketplace:access'])->plainTextToken,
        ], 201);
    }

    public function login(LoginRequest $request, MarketplaceContext $context): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email')->lower()->toString())->first();

        if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages(['email' => 'The provided credentials are incorrect.']);
        }

        if ($user->status !== 'active' || ! $this->canUseMarketplaceCountry($user, $context->id())) {
            throw ValidationException::withMessages(['email' => 'This account is unavailable in the selected marketplace.']);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'user' => $user,
            'token' => $user->createToken($request->string('device_name')->toString(), ['marketplace:access'])->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user('sanctum')?->currentAccessToken()?->delete();

        return response()->json(status: 204);
    }

    public function user(Request $request, MarketplaceContext $context): JsonResponse
    {
        $user = $request->user();

        if (! $this->canUseMarketplaceCountry($user, $context->id())) {
            abort(403);
        }

        return response()->json($user);
    }

    private function canUseMarketplaceCountry(User $user, int $countryId): bool
    {
        return $user->canUseMarketplaceCountry($countryId);
    }
}
