<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use App\Enums\NotificationProcessStatus;
use App\Enums\NotificationType;
use App\Enums\OutboxEventPriority;
use App\Enums\OutboxEventType;
use App\Enums\OutboxMessageStatus;
use App\Models\Notification;
use App\Models\OutboxMessage;
use App\Models\ReceiverNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Receiver;

class StartNotificationControllerTest extends TestCase
{
    use RefreshDatabase;
    private const ROUTE = '/api/v1/notification/start';

    public function testStartNotificationSuccess(): void
    {
        // Arrange
        $receiverId = Receiver::factory()->create()->id;

        // Act
        $text = 'Маркетинговое уведомление';
        $notificationChannel = NotificationChannel::Sms;
        $notificationType = NotificationType::Marketing;
        $response = $this->post(self::ROUTE, [
            'text' => $text,
            'channel' => $notificationChannel->value,
            'type' => $notificationType->value,
            'receiverIds' => [$receiverId],
        ]);

        // Assert
        $response->assertAccepted();
        $expectedNotificationProcessStatus = NotificationProcessStatus::InQueue;
        $expectedEventType = OutboxEventType::SendNotification;
        $expectedPriority =  OutboxEventPriority::fromNotificationType($notificationType);

        $this->assertDatabaseHas(self::NOTIFICATION_TABLE, [
            'channel' => $notificationChannel->value,
            'type' =>  $notificationType->value,
            'text' => 'Маркетинговое уведомление',
        ]);

        $notificationId = Notification::query()->first()->id;
        $this->assertDatabaseHas(self::RECEIVER_NOTIFICATION_TABLE, [
            'receiver_id' => $receiverId,
            'notification_id' => $notificationId,
            'status' => $expectedNotificationProcessStatus->value,
        ]);

        $receiverNotificationId = ReceiverNotification::query()
            ->where([
                'receiver_id' => $receiverId,
                'notification_id' => $notificationId
            ])
            ->first()
            ->id;

        $this->assertDatabaseHas(self::HISTORY_TABLE, [
            'receiver_notification_id' => $receiverNotificationId,
            'status' => $expectedNotificationProcessStatus->value,
        ]);

        $this->assertDatabaseHas(self::OUTBOX_TABLE, [
            'event_type' => $expectedEventType->value,
            'priority' => $expectedPriority->value,
            'status' => OutboxMessageStatus::Pending->value,
        ]);

        $outbox = OutboxMessage::query()->first();

        $this->assertEquals($receiverNotificationId, $outbox->payload['receiver_notification_id']);
    }

    public function testStartNotificationFail(): void
    {
        // Arrange
        $receiverId = Receiver::factory()->create()->id;
        $wrongReceiverId = $receiverId + 100;

        // Act
        $text = 'Маркетинговое уведомление';
        $notificationChannel = NotificationChannel::Sms;
        $notificationType = NotificationType::Marketing;
        $response = $this->post(self::ROUTE, [
            'text' => $text,
            'channel' => $notificationChannel->value,
            'type' => $notificationType->value,
            'receiverIds' => [$wrongReceiverId],
        ]);

        // Assert
        $response->assertUnprocessable();
    }

    public function testDuplicateRequest(): void
    {
        // Arrange
        $receiverId = Receiver::factory()->create()->id;

        // Act
        $text = 'Маркетинговое уведомление';
        $notificationChannel = NotificationChannel::Sms;
        $notificationType = NotificationType::Marketing;
        $payload = [
            'text' => $text,
            'channel' => $notificationChannel->value,
            'type' => $notificationType->value,
            'receiverIds' => [$receiverId],
        ];

        $this->post(self::ROUTE, $payload);
        $response = $this->post(self::ROUTE, $payload);

        // Assert
        $response->assertStatus(429);
    }
}
