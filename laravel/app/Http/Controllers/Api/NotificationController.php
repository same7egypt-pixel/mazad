<?php

namespace App\Http\Controllers\Api;

use App\Domain\Core\Context\MarketplaceContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Validation\ValidationException;

class NotificationController extends Controller
{
    public function index(Request $request, MarketplaceContext $context): JsonResponse
    {
        $notifications = $request->user()->notifications()
            ->whereRaw("data::jsonb ->> 'country_id' = ?", [(string) $context->id()])
            ->when($request->boolean('unread_only'), fn ($builder) => $builder->whereNull('read_at'))
            ->latest()
            ->paginate(30);

        return response()->json($notifications);
    }

    public function markRead(string $notification, Request $request, MarketplaceContext $context): JsonResponse
    {
        $record = DatabaseNotification::query()->whereKey($notification)->where('notifiable_id', $request->user()->id)->firstOrFail();
        if ((int) data_get($record->data, 'country_id') !== $context->id()) {
            throw ValidationException::withMessages(['notification' => 'This notification belongs to a different marketplace.']);
        }

        $record->markAsRead();

        return response()->json(['notification' => $record->fresh()]);
    }
}
