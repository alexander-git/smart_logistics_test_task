<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Receiver\ShowHistory;

use App\Http\Resources\Api\V1\ReceiverNotificationResource;
use App\Models\Receiver;
use Illuminate\Http\JsonResponse;

class ShowHistoryController
{
    public function __construct(
    ) {
    }

    public function __invoke(Receiver $receiver): JsonResponse
    {
        $receiverNotifications = $receiver
            ->receiverNotifications()
            ->orderBy('created_at', 'desc')
            ->with(['notification', 'historyItems'])
            ->get();

        return response()->json(ReceiverNotificationResource::collection($receiverNotifications));
    }
}
