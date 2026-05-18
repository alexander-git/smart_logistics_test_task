<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use App\Enums\NotificationProcessStatus;
use App\Enums\NotificationType;
use App\Enums\OutboxEventPriority;
use App\Enums\OutboxEventType;
use App\Jobs\ProcessNotificationJob;
use App\Models\HistoryItem;
use App\Models\Notification;
use App\Models\Receiver;
use App\Models\ReceiverNotification;
use App\Services\Notification\NotificationService;
use App\Services\SmsSender\SmsSenderException;
use App\Services\SmsSender\SmsSendResult;
use App\Services\SmsSender\SmsSenderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProcessNotificationJobTest extends TestCase
{
    use RefreshDatabase;

    public function testSmsSentSuccess(): void
    {
        // Arrange
        [$receiver, $notification, $receiverNotification] = $this->init();

        $smsSenderMock = Mockery::mock(SmsSenderInterface::class);
        $smsSenderMock
            ->shouldReceive('sendSms')
            ->once()
            ->with($receiver->phone, $notification->text)
            ->andReturn(SmsSendResult::Success);

        $this->app->instance(SmsSenderInterface::class, $smsSenderMock);

        // Act
        $job = app()->make(ProcessNotificationJob::class, [
            'receiverNotificationId' => $receiverNotification->id,
        ]);

        $job->handle(app()->make(NotificationService::class));

        // Assert
        $this->assertDatabaseHas(self::RECEIVER_NOTIFICATION_TABLE, [
            'id' => $receiverNotification->id,
            'status' => NotificationProcessStatus::Delivered->value,
        ]);

        $this->assertDatabaseHas(self::HISTORY_TABLE, [
            'receiver_notification_id' => $receiverNotification->id,
            'status' => NotificationProcessStatus::Sent->value,
        ]);

        $this->assertDatabaseHas(self::HISTORY_TABLE, [
            'receiver_notification_id' => $receiverNotification->id,
            'status' => NotificationProcessStatus::Delivered->value,
        ]);
    }

    public function testSmsTemporaryUnavailable(): void
    {
        // Arrange
        [$receiver, $notification, $receiverNotification] = $this->init();

        $smsSenderMock = Mockery::mock(SmsSenderInterface::class);

        $smsSenderMock
            ->shouldReceive('sendSms')
            ->once()
            ->with($receiver->phone, $notification->text)
            ->andReturn(SmsSendResult::TemporaryUnavailable);

        $this->app->instance(SmsSenderInterface::class, $smsSenderMock);

        // Act
        $job = app()->make(ProcessNotificationJob::class, [
            'receiverNotificationId' => $receiverNotification->id,
        ]);

        $job->handle(app()->make(NotificationService::class));

        // Assert
        $expectedEventType = OutboxEventType::SendNotification;
        $expectedPriority =  OutboxEventPriority::fromNotificationType($notification->type);

        $this->assertDatabaseHas(self::RECEIVER_NOTIFICATION_TABLE, [
            'id' => $receiverNotification->id,
            'status' => NotificationProcessStatus::InQueue->value,
            'retry_count' => 1,
        ]);

        $this->assertDatabaseHas(self::HISTORY_TABLE, [
            'receiver_notification_id' => $receiverNotification->id,
            'status' => NotificationProcessStatus::Sent->value,
        ]);

        $this->assertDatabaseHas(self::HISTORY_TABLE, [
            'receiver_notification_id' => $receiverNotification->id,
            'status' => NotificationProcessStatus::InQueue->value,
        ]);

        $this->assertDatabaseHas(self::OUTBOX_TABLE, [
            'event_type' => $expectedEventType->value,
            'is_sent' => false,
            'priority' => $expectedPriority->value,
        ]);
    }

    public function testSmsNotCorrectPhone(): void
    {
        // Arrange
        [$receiver, $notification, $receiverNotification] = $this->init();

        $smsSenderMock = Mockery::mock(SmsSenderInterface::class);

        $smsSenderMock
            ->shouldReceive('sendSms')
            ->once()
            ->with($receiver->phone, $notification->text)
            ->andReturn(SmsSendResult::NotCorrectPhone);

        $this->app->instance(SmsSenderInterface::class, $smsSenderMock);

        // Act
        $job = app()->make(ProcessNotificationJob::class, [
            'receiverNotificationId' => $receiverNotification->id,
        ]);

        $job->handle(app()->make(NotificationService::class));

        // Assert
        $this->assertDatabaseHas(self::RECEIVER_NOTIFICATION_TABLE, [
            'id' => $receiverNotification->id,
            'status' => NotificationProcessStatus::Discarded->value,
        ]);

        $this->assertDatabaseHas(self::HISTORY_TABLE, [
            'receiver_notification_id' => $receiverNotification->id,
            'status' => NotificationProcessStatus::Sent->value,
        ]);

        $this->assertDatabaseHas(self::HISTORY_TABLE, [
            'receiver_notification_id' => $receiverNotification->id,
            'status' => NotificationProcessStatus::Discarded->value,
        ]);
    }

    public function testSmsSenderException(): void
    {
        // Arrange
        [$receiver, $notification, $receiverNotification] = $this->init();

        $smsSenderMock = Mockery::mock(SmsSenderInterface::class);
        $smsSenderMock
            ->shouldReceive('sendSms')
            ->once()
            ->with($receiver->phone, $notification->text)
            ->andThrow(new SmsSenderException());

        $this->app->instance(SmsSenderInterface::class, $smsSenderMock);

        // Act
        $job = app()->make(ProcessNotificationJob::class, [
            'receiverNotificationId' => $receiverNotification->id,
        ]);

        $job->handle(app()->make(NotificationService::class));

        // Assert
        $expectedEventType = OutboxEventType::SendNotification;
        $expectedPriority =  OutboxEventPriority::fromNotificationType($notification->type);

        $this->assertDatabaseHas(self::RECEIVER_NOTIFICATION_TABLE, [
            'id' => $receiverNotification->id,
            'status' => NotificationProcessStatus::InQueue->value,
            'retry_count' => 1,
        ]);

        $this->assertDatabaseHas(self::HISTORY_TABLE, [
            'receiver_notification_id' => $receiverNotification->id,
            'status' => NotificationProcessStatus::Sent->value,
        ]);

        $this->assertDatabaseHas(self::HISTORY_TABLE, [
            'receiver_notification_id' => $receiverNotification->id,
            'status' => NotificationProcessStatus::InQueue->value,
        ]);

        $this->assertDatabaseHas(self::OUTBOX_TABLE, [
            'event_type' => $expectedEventType->value,
            'is_sent' => false,
            'priority' => $expectedPriority->value,
        ]);
    }

    private function init(): array
    {
        $notificationChannel = NotificationChannel::Sms;
        $notificationType = NotificationType::Marketing;

        $initialNotificationProcessStatus = NotificationProcessStatus::InQueue;

        $receiver = Receiver::factory()->create();

        $notification = Notification::create([
            'channel' => $notificationChannel,
            'type' => $notificationType,
            'text' => 'Маркетинговое уведомление',
        ]);

        $receiverNotification = ReceiverNotification::create([
            'receiver_id' => $receiver->id,
            'notification_id' => $notification->id,
            'status' => $initialNotificationProcessStatus->value,
        ]);

        HistoryItem::create([
            'receiver_notification_id' => $receiverNotification->id,
            'status' => $initialNotificationProcessStatus,
        ]);

        return [$receiver, $notification, $receiverNotification];
    }
}
