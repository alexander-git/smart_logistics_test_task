<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\NotificationProcessStatus;
use App\Models\Notification;
use App\Models\Receiver;
use App\Models\ReceiverNotification;
use App\Services\Notification\Processor\NotificationChannelProcessorRegistry;
use App\Services\Outbox\OutboxService;
use App\Services\ReceiverNotification\ReceiverNotificationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationService
{
    private const PROCESS_NOTIFICATION_LOCK_SECONDS = 60;

    public function __construct(
        private readonly OutboxService $outboxService,
        private readonly ReceiverNotificationService $receiverNotificationService,
        private readonly NotificationChannelProcessorRegistry $notificationChannelProcessorRegistry,
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

        return DB::transaction(function () use ($command, $acceptedReceiverIds, $result) {
            $notification = Notification::create([
                'channel' => $command->notificationChannel,
                'type' => $command->notificationType,
                'text' => $command->text,
            ]);

            foreach ($acceptedReceiverIds as $receiverId) {
                $receiverNotification = $this->receiverNotificationService->create(
                    $receiverId,
                    $notification->id,
                );

                $this->outboxService->createSendNotificationMessage(
                    $command->notificationType,
                    $receiverNotification->id
                );
            }

            return $result;
        });
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

                $this->notificationChannelProcessorRegistry->get($receiverNotification->notification->channel)
                    ->process($receiverNotification);
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

    private function getNotificationProcessingLockKey(int $receiverNotificationId): string
    {
        return sprintf('notification:process:%d', $receiverNotificationId);
    }
}
