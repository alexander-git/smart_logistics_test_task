<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\NotificationChannel;
use App\Enums\NotificationProcessStatus;
use App\Enums\OutboxEventPriority;
use App\Enums\OutboxEventType;
use App\Models\HistoryItem;
use App\Models\Notification;
use App\Models\OutboxMessage;
use App\Models\Receiver;
use App\Models\ReceiverNotification;
use App\Services\EmailSender\EmailSenderException;
use App\Services\EmailSender\EmailSenderInterface;
use App\Services\EmailSender\EmailSendResult;
use App\Services\SmsSender\SmsSenderException;
use App\Services\SmsSender\SmsSenderInterface;
use App\Services\SmsSender\SmsSendResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationService
{
    private const MAX_RETRIES = 3;
    private const PROCESS_NOTIFICATION_LOCK_SECONDS = 60;

    public function __construct(
        private readonly EmailSenderInterface $emailSender,
        private readonly SmsSenderInterface $smsSender,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function startNotification(StartNotificationCommand $command): StartNotificationResult
    {
        $acceptedReceiverIds = Receiver::getExistingIdsAmongGivenByChannel(
            $command->receiverIds,
            $command->notificationChannel
        );
        $skippedReceiverIds = array_values(array_diff($command->receiverIds, $acceptedReceiverIds));
        $result =  new StartNotificationResult($acceptedReceiverIds, $skippedReceiverIds);
        if ($acceptedReceiverIds === []) {
            return $result;
        }

        $now = Carbon::now();
        $priority = OutboxEventPriority::fromNotificationType($command->notificationType);
        DB::beginTransaction();
        try {
            $notification = Notification::create([
                'channel' => $command->notificationChannel,
                'type' => $command->notificationType,
                'text' => $command->text,
            ]);

            foreach ($acceptedReceiverIds as $receiverId) {
                $receiverNotification = ReceiverNotification::create([
                    'receiver_id' => $receiverId,
                    'notification_id' => $notification->id,
                    'status' => NotificationProcessStatus::InQueue,
                ]);

                HistoryItem::create([
                    'receiver_notification_id' => $receiverNotification->id,
                    'status' => NotificationProcessStatus::InQueue,
                ]);

                OutboxMessage::create([
                    'event_type' => OutboxEventType::SendNotification,
                    'payload' => [
                        'receiver_notification_id' => $receiverNotification->id,
                    ],
                    'priority' =>  $priority,
                    'send_after' => $now,
                ]);
            }

            DB::commit();
            return $result;
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    public function processNotificationForReceiver(int $receiverNotificationId): void
    {
        try {
            Cache::lock(
                $this->getNotificationProcessingLockKey($receiverNotificationId),
                self::PROCESS_NOTIFICATION_LOCK_SECONDS
            )->get(function () use ($receiverNotificationId): void {
                $receiverNotification = ReceiverNotification::query()
                    ->with(['notification', 'receiver'])
                    ->where([
                        'id' => $receiverNotificationId,
                        'status' => NotificationProcessStatus::InQueue,
                    ])
                    ->first();

                if ($receiverNotification === null) {
                    return;
                }

                match ($receiverNotification->notification->channel) {
                    NotificationChannel::Email => $this->sendEmail($receiverNotification),
                    NotificationChannel::Sms => $this->sendSms($receiverNotification),
                };
            });
        } catch (Throwable $e) {
            // Если в процессе обработки статус ReceiverNotification был изменён на отличный от InQueue, а затем
            // произошло какое-либо непредвиденное исключение(отличное от EmailSenderException и SmsSenderException)
            // джоба снова попадёт в очередь. Учитывая как извлекается receiverNotification выше в дальнейшем обработка
            // такой джобы будет заканчиваться сразу и после трех попыток(настройка --tries=3) сообщение
            // пропадёт из очереди. В идеале надо настроить его попадание в dead letter queue для дальнейшего анализа.
            Log::error("receiverNotificationId: $receiverNotificationId, message: {$e->getMessage()}");
            throw $e;
        }
    }

    private function sendEmail(ReceiverNotification $receiverNotification): void
    {
        $this->changeReceiverNotificationStatus($receiverNotification, NotificationProcessStatus::Sent);
        $notification = $receiverNotification->notification;
        $text = $notification->text;
        $subject = mb_substr($text, 0, 30);
        try {
            $emailSendResult = $this->emailSender->sendEmail($receiverNotification->receiver->email , $subject, $text);
        } catch (EmailSenderException) {
            $this->markToRetryOrDiscard($receiverNotification);
            return;
        }

        match ($emailSendResult) {
            EmailSendResult::Success =>
                $this->changeReceiverNotificationStatus(
                    $receiverNotification,
                    NotificationProcessStatus::Delivered
                ),
            EmailSendResult::NotCorrectEmail =>
                $this->changeReceiverNotificationStatus(
                    $receiverNotification,
                    NotificationProcessStatus::Discarded
                ),
            EmailSendResult::TemporaryUnavailable =>
                $this->markToRetryOrDiscard($receiverNotification),
        };
    }

    private function sendSms(ReceiverNotification $receiverNotification): void
    {
        $this->changeReceiverNotificationStatus($receiverNotification, NotificationProcessStatus::Sent);

        try {
            $smsSendResult = $this->smsSender->sendSms(
                $receiverNotification->receiver->phone,
                $receiverNotification->notification->text
            );
        } catch (SmsSenderException) {
            $this->markToRetryOrDiscard($receiverNotification);
            return;
        }

        match ($smsSendResult) {
            SmsSendResult::Success =>
                $this->changeReceiverNotificationStatus(
                    $receiverNotification,
                    NotificationProcessStatus::Delivered
                ),
            SmsSendResult::NotCorrectPhone =>
                $this->changeReceiverNotificationStatus(
                    $receiverNotification,
                    NotificationProcessStatus::Discarded
                ),
            SmsSendResult::TemporaryUnavailable =>
                $this->markToRetryOrDiscard($receiverNotification),
        };
    }

    /**
     * @throws Throwable
     */
    private function markToRetryOrDiscard(ReceiverNotification $receiverNotification): void
    {

        $retryCount = $receiverNotification->retry_count + 1;
        if ($retryCount > self::MAX_RETRIES) {
            $this->changeReceiverNotificationStatus($receiverNotification, NotificationProcessStatus::Discarded);
            return;
        }

        DB::beginTransaction();
        try {
            $receiverNotification->retry_count = $retryCount;
            $receiverNotification->status = NotificationProcessStatus::InQueue;
            $receiverNotification->save();

            HistoryItem::create([
                'receiver_notification_id' => $receiverNotification->id,
                'status' => NotificationProcessStatus::InQueue,
            ]);

            OutboxMessage::create([
                'event_type' => OutboxEventType::SendNotification,
                'payload' => [
                    'receiver_notification_id' => $receiverNotification->id,
                ],
                'priority' =>  OutboxEventPriority::fromNotificationType($receiverNotification->notification->type),
                'send_after' => Carbon::now()->modify('+5 minutes'),
            ]);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * @throws Throwable
     */
    private function changeReceiverNotificationStatus(
        ReceiverNotification $receiverNotification,
        NotificationProcessStatus $newStatus
    ): void {
        DB::beginTransaction();
        try {
            $receiverNotification->status = $newStatus;
            $receiverNotification->save();
            HistoryItem::create([
                'receiver_notification_id' => $receiverNotification->id,
                'status' => $newStatus,
            ]);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function getNotificationProcessingLockKey(int $receiverNotificationId): string
    {
        return sprintf('notification:process:%d', $receiverNotificationId);
    }

}
