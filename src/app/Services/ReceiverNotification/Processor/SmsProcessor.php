<?php

declare(strict_types=1);

namespace App\Services\ReceiverNotification\Processor;

use App\Enums\NotificationChannel;
use App\Enums\NotificationProcessStatus;
use App\Models\ReceiverNotification;
use App\Services\ReceiverNotification\ReceiverNotificationService;
use App\Services\SmsSender\SmsSenderException;
use App\Services\SmsSender\SmsSenderInterface;
use App\Services\SmsSender\SmsSendResult;

class SmsProcessor implements NotificationChannelProcessorInterface
{
    public function __construct(
        private readonly SmsSenderInterface $smsSender,
        private readonly ReceiverNotificationService $receiverNotificationService,
    ) {}

    public function supports(NotificationChannel $channel): bool
    {
        return $channel === NotificationChannel::Sms;
    }

    public function process(ReceiverNotification $receiverNotification): void
    {
        $this->receiverNotificationService->changeStatus(
            $receiverNotification,
            NotificationProcessStatus::Sent
        );

        try {
            $smsSendResult = $this->smsSender->sendSms(
                $receiverNotification->receiver->phone,
                $receiverNotification->notification->text
            );
        } catch (SmsSenderException) {
            $this->receiverNotificationService->markToRetryOrDiscard($receiverNotification);
            return;
        }

        match ($smsSendResult) {
            SmsSendResult::Success =>
                $this->receiverNotificationService->changeStatus(
                    $receiverNotification,
                    NotificationProcessStatus::Delivered
                ),
            SmsSendResult::NotCorrectPhone =>
                $this->receiverNotificationService->changeStatus(
                    $receiverNotification,
                    NotificationProcessStatus::Discarded
                ),
            SmsSendResult::TemporaryUnavailable =>
                $this->receiverNotificationService->markToRetryOrDiscard($receiverNotification),
        };
    }
}
