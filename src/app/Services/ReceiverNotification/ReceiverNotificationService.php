<?php

declare(strict_types=1);

namespace App\Services\ReceiverNotification;

use App\Enums\NotificationProcessStatus;
use App\Models\HistoryItem;
use App\Models\ReceiverNotification;
use App\Services\Outbox\OutboxService;
use Carbon\Carbon;
use Illuminate\Container\Attributes\Config;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\DB;
use Throwable;

#[Singleton]
class ReceiverNotificationService
{
    public function __construct(
        private readonly OutboxService $outboxService,
        #[Config('notification.max_retries')]
        private readonly int $maxRetries,
        #[Config('notification.retry_after_minutes')]
        private readonly int $retryAfterMinutes,
    ) {
    }

    public function create(int $receiverId, int $notificationId): ReceiverNotification
    {
        $receiverNotification = ReceiverNotification::create([
            'receiver_id' => $receiverId,
            'notification_id' => $notificationId,
            'status' => NotificationProcessStatus::InQueue,
        ]);

        HistoryItem::create([
            'receiver_notification_id' => $receiverNotification->id,
            'status' => NotificationProcessStatus::InQueue,
        ]);

        return $receiverNotification;
    }

    public function getForProcess(int $receiverNotificationId): ?ReceiverNotification
    {
        return ReceiverNotification::query()
            ->with(['notification', 'receiver'])
            ->where([
                'id' => $receiverNotificationId,
                'status' => NotificationProcessStatus::InQueue,
            ])
            ->first();
    }

    /**
     * @throws Throwable
     */
    public function changeStatus(
        ReceiverNotification $receiverNotification,
        NotificationProcessStatus $newStatus
    ): void {
        DB::transaction(function () use ($receiverNotification, $newStatus) {
            $receiverNotification->status = $newStatus;
            $receiverNotification->save();

            HistoryItem::create([
                'receiver_notification_id' => $receiverNotification->id,
                'status' => $newStatus,
            ]);
        });
    }

    /**
     * @throws Throwable
     */
    public function markToRetryOrDiscard(ReceiverNotification $receiverNotification): void
    {
        $retryCount = $receiverNotification->retry_count + 1;
        if ($retryCount > $this->maxRetries) {
            $this->changeStatus($receiverNotification, NotificationProcessStatus::Discarded);
            return;
        }

        DB::transaction(function () use ($receiverNotification, $retryCount) {
            $receiverNotification->retry_count = $retryCount;
            $receiverNotification->status = NotificationProcessStatus::InQueue;
            $receiverNotification->save();

            HistoryItem::create([
                'receiver_notification_id' => $receiverNotification->id,
                'status' => NotificationProcessStatus::InQueue,
            ]);

            $this->outboxService->createSendNotificationMessage(
                $receiverNotification->notification->type,
                $receiverNotification->id,
                Carbon::now()->modify("+{$this->retryAfterMinutes} minutes"),
            );
        });
    }
}
