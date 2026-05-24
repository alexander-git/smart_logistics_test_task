<?php

declare(strict_types=1);

namespace App\Services\ReceiverNotification\Processor;

use App\Enums\NotificationChannel;
use App\Enums\NotificationProcessStatus;
use App\Models\ReceiverNotification;
use App\Services\EmailSender\EmailSenderException;
use App\Services\EmailSender\EmailSenderInterface;
use App\Services\EmailSender\EmailSendResult;
use App\Services\ReceiverNotification\ReceiverNotificationService;

class EmailProcessor implements NotificationChannelProcessorInterface
{
    public function __construct(
        private readonly EmailSenderInterface $emailSender,
        private readonly ReceiverNotificationService $receiverNotificationService,
    ) {}

    public function supports(NotificationChannel $channel): bool
    {
        return $channel === NotificationChannel::Email;
    }

    public function process(ReceiverNotification $receiverNotification): void
    {
        $this->receiverNotificationService->changeStatus(
            $receiverNotification,
            NotificationProcessStatus::Sent
        );

        $notification = $receiverNotification->notification;
        $text = $notification->text;
        $subject = mb_substr($text, 0, 30);
        try {
            $emailSendResult = $this->emailSender->sendEmail($receiverNotification->receiver->email , $subject, $text);
        } catch (EmailSenderException) {
            $this->receiverNotificationService->markToRetryOrDiscard($receiverNotification);
            return;
        }

        match ($emailSendResult) {
            EmailSendResult::Success =>
                $this->receiverNotificationService->changeStatus(
                    $receiverNotification,
                    NotificationProcessStatus::Delivered
                ),
            EmailSendResult::NotCorrectEmail =>
                $this->receiverNotificationService->changeStatus(
                    $receiverNotification,
                    NotificationProcessStatus::Discarded
                ),
            EmailSendResult::TemporaryUnavailable =>
                $this->receiverNotificationService->markToRetryOrDiscard($receiverNotification),
        };
    }
}
