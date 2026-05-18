<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReceiverNotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'notificationText' => $this->notification?->text,
            'channel' => $this->notification->channel->value,
            'status' => $this->status->value,

            'history' => HistoryItemResource::collection(
                $this->whenLoaded('historyItems')
            ),
        ];
    }
}
