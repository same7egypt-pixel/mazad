<?php

namespace App\Http\Controllers\Api;

use App\Domain\Core\Context\MarketplaceContext;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\UserActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GovernanceController extends Controller
{
    public function fraudSignals(Request $request, MarketplaceContext $context): JsonResponse
    {
        $this->authorizeCountryPermission($request, $context, 'fraud.review');
        $signals = UserActivity::query()
            ->with('user')
            ->where('country_id', $context->id())
            ->where('event', 'like', 'fraud.%')
            ->latest('created_at')
            ->paginate(50);

        return response()->json($signals);
    }

    public function auditLogs(Request $request, MarketplaceContext $context): JsonResponse
    {
        $this->authorizeCountryPermission($request, $context, 'audit.view');
        $logs = AuditLog::query()
            ->with('actor')
            ->where('country_id', $context->id())
            ->latest('created_at')
            ->paginate(50);

        return response()->json($logs);
    }

    private function authorizeCountryPermission(Request $request, MarketplaceContext $context, string $permission): void
    {
        $user = $request->user();
        if (! $user->can($permission) || (! $user->hasRole('GLOBAL_SUPER_ADMIN') && $user->country_id !== $context->id())) {
            throw ValidationException::withMessages(['authorization' => 'You are not permitted to access this country-scoped governance data.']);
        }
    }
}
