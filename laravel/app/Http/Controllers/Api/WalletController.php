<?php

namespace App\Http\Controllers\Api;

use App\Domain\Core\Context\MarketplaceContext;
use App\Domain\Payments\Services\RequestWithdrawal;
use App\Domain\Payments\Services\ReviewWithdrawal;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\StoreWithdrawalRequest;
use App\Models\Wallet;
use App\Models\Withdrawal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function index(Request $request, MarketplaceContext $context): JsonResponse
    {
        if (! $request->user()->canUseMarketplaceCountry($context->id())) {
            abort(403);
        }

        $wallets = Wallet::query()->with('currency')->where('user_id', $request->user()->id)->get();

        return response()->json(['wallets' => $wallets]);
    }

    public function requestWithdrawal(Wallet $wallet, StoreWithdrawalRequest $request, MarketplaceContext $context, RequestWithdrawal $service): JsonResponse
    {
        $context->assertMatches($request->user()->country_id);
        $attributes = $request->validated();
        $withdrawal = $service->handle(
            $wallet->id,
            $request->user(),
            $attributes['amount'],
            $attributes['destination_type'],
            $attributes['destination_details'],
        );

        return response()->json(['withdrawal' => $withdrawal], 201);
    }

    public function approveWithdrawal(Withdrawal $withdrawal, Request $request, MarketplaceContext $context, ReviewWithdrawal $service): JsonResponse
    {
        $context->assertMatches($withdrawal->wallet()->with('user')->firstOrFail()->user->country_id);
        $withdrawal = $service->approve($withdrawal->id, $request->user());

        return response()->json(['withdrawal' => $withdrawal]);
    }

    public function rejectWithdrawal(Withdrawal $withdrawal, Request $request, MarketplaceContext $context, ReviewWithdrawal $service): JsonResponse
    {
        $context->assertMatches($withdrawal->wallet()->with('user')->firstOrFail()->user->country_id);
        $withdrawal = $service->reject($withdrawal->id, $request->user());

        return response()->json(['withdrawal' => $withdrawal]);
    }
}
