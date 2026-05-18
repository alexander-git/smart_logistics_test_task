<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Receiver\ShowHistory;

use App\Http\Resources\Api\V1\ReceiverNotificationResource;
use App\Models\Receiver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ShowHistoryController
{
    public function __construct(
    ) {
    }

    public function __invoke(Request $request, Receiver $receiver): AnonymousResourceCollection
    {
        $page = $request->integer('page', 1);
        if ($page < 0) {
            $page = 1;
        }

        $perPage = $request->integer('perPage', 10);
        if ($perPage < 0) {
            $perPage = 10;
        } elseif ($perPage > 100) {
            $perPage = 100;
        }

        $receiverNotifications = $receiver
            ->receiverNotifications()
            ->orderBy('created_at', 'desc')
            ->with(['notification', 'historyItems'])
            ->paginate(perPage: $perPage, page: $page);

        return ReceiverNotificationResource::collection($receiverNotifications);
    }
}
