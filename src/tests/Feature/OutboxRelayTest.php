<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\OutboxRelay;
use App\Enums\NotificationChannel;
use App\Enums\NotificationProcessStatus;
use App\Enums\NotificationType;
use App\Enums\OutboxEventPriority;
use App\Enums\OutboxEventType;
use App\Enums\OutboxMessageStatus;
use App\Jobs\ProcessNotificationJob;
use App\Models\HistoryItem;
use App\Models\Notification;
use App\Models\OutboxMessage;
use App\Models\Receiver;
use App\Models\ReceiverNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class OutboxRelayTest extends TestCase
{
    use RefreshDatabase;

    public function testOutboxRelaySuccess(): void
    {
        // Arrange
        Queue::fake();

        $notificationChannel = NotificationChannel::Sms;
        $notificationType = NotificationType::Marketing;
        $notificationProcessStatus = NotificationProcessStatus::InQueue;
        $eventType = OutboxEventType::SendNotification;
        $priority = OutboxEventPriority::fromNotificationType($notificationType);

        $receiverId = Receiver::factory()->create()->id;

        $notification = Notification::create([
            'channel' => $notificationChannel,
            'type' => $notificationType,
            'text' => 'Маркетинговое уведомление',
        ]);

        $receiverNotification = ReceiverNotification::create([
            'receiver_id' => $receiverId,
            'notification_id' => $notification->id,
            'status' => $notificationProcessStatus,
        ]);

        HistoryItem::create([
            'receiver_notification_id' => $receiverNotification->id,
            'status' => $notificationProcessStatus,
        ]);

        $outboxMessageId = OutboxMessage::create([
            'event_type' => $eventType,
            'payload' => [
                'receiver_notification_id' => $receiverNotification->id,
            ],
            'priority' => $priority,
            'send_after' => Carbon::now()->subSecond(),
            'status' => OutboxMessageStatus::Pending->value,
        ])->id;

        // Act
        $command = app(OutboxRelay::class);
        $command->processMessageBatch();

        // Assert
        Queue::assertPushed(ProcessNotificationJob::class);

        $this->assertDatabaseHas(self::OUTBOX_TABLE, [
            'id' => $outboxMessageId,
            'status' => OutboxMessageStatus::Sent->value,
        ]);
    }
}
