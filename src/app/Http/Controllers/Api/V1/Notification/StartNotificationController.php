<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Notification;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Http\Requests\Api\V1\Notification\StartNotificationRequest;
use App\Services\Notification\NotificationService;
use App\Services\Notification\StartNotificationCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class StartNotificationController
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public function __invoke(StartNotificationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $result = $this->notificationService->startNotification(new StartNotificationCommand(
            text: $data['text'],
            notificationChannel: NotificationChannel::from($data['channel']),
            notificationType: NotificationType::from($data['type']),
            receiverIds: $data['receiverIds']
        ));

        $responseData = [
            'acceptedReceiverIds' => $result->acceptedReceiverIds,
            'skippedReceiverIds' => $result->skippedReceiverIds,
        ];

        if ($result->acceptedReceiverIds === []) {
            $responseData['message'] = 'There are no correct receiver IDs';
            $status = Response::HTTP_UNPROCESSABLE_ENTITY;
        } else {
            $responseData['message'] = 'Notification started successfully';
            $status = Response::HTTP_ACCEPTED;
        }

        return response()->json(
            data: $responseData,
            status: $status
        );
    }
}
