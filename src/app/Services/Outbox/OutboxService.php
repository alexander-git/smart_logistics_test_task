<?php

declare(strict_types=1);

namespace App\Services\Outbox;

use App\Enums\NotificationType;
use App\Enums\OutboxEventPriority;
use App\Enums\OutboxEventType;
use App\Enums\OutboxMessageStatus;
use App\Models\OutboxMessage;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

class OutboxService
{
    public function createSendNotificationMessage(
        NotificationType $notificationType,
        int $receiverNotificationId,
        ?DateTimeInterface $sendAfter = null
    ): OutboxMessage {
        if ($sendAfter === null) {
            $sendAfter = Carbon::now();
        }
        return OutboxMessage::create([
            'event_type' => OutboxEventType::SendNotification,
            'payload' => [
                'receiver_notification_id' => $receiverNotificationId,
            ],
            'priority' =>  OutboxEventPriority::fromNotificationType($notificationType),
            'send_after' => $sendAfter,
        ]);
    }

    public function getMessageForProcessing(): ?OutboxMessage
    {
        return DB::transaction(function(): ?OutboxMessage {
            $message = OutboxMessage::query()
                ->where('status', OutboxMessageStatus::Pending)
                ->where('send_after', '<', Carbon::now())
                ->orderByRaw("
                        CASE
                            WHEN priority = 'high' THEN 0
                            WHEN priority = 'normal' THEN 1
                            ELSE 2
                        END
                    ")
                ->orderBy('created_at', 'asc')
                ->lock('FOR UPDATE SKIP LOCKED')
                ->first();

            if ($message === null) {
                return null;
            }

            $message->status = OutboxMessageStatus::Processing;
            $message->save();
            return $message;
        });
    }

    public function setMessageSent(int $messageId): void
    {
        OutboxMessage::query()
            ->whereKey($messageId)
            ->where('status', OutboxMessageStatus::Processing)
            ->update(['status' => OutboxMessageStatus::Sent]);
    }

    public function setMessagePending(int $messageId): void
    {
        OutboxMessage::query()
            ->whereKey($messageId)
            ->where('status', OutboxMessageStatus::Processing)
            ->update(['status' => OutboxMessageStatus::Pending]);
    }

    public function deleteOutdatedMessages(int $retentionDays): void
    {
        OutboxMessage::query()
            ->where('status', OutboxMessageStatus::Sent)
            ->where('updated_at', '<', Carbon::now()->subDays($retentionDays))
            ->delete();
    }

}
