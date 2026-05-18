<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use App\Enums\NotificationProcessStatus;
use App\Enums\NotificationType;
use App\Models\HistoryItem;
use App\Models\Notification;
use App\Models\Receiver;
use App\Models\ReceiverNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowHistoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private const ROUTE = 'api/v1/receiver/show-history/%d';

    public function testShowHistorySuccess(): void
    {
        // Arrange
        $receiver = Receiver::factory()->create();

        $notificationChannel = NotificationChannel::Sms;
        $notificationType = NotificationType::Marketing;

        $statuses = [
            NotificationProcessStatus::InQueue,
            NotificationProcessStatus::Sent,
            NotificationProcessStatus::Delivered,
            NotificationProcessStatus::Discarded,
        ];

        $texts = [
            'Маркетинговое уведомление 1',
            'Маркетинговое уведомление 2',
            'Маркетинговое уведомление 3',
            'Маркетинговое уведомление 4',
        ];

        $i = 0;
        foreach ($statuses as $index => $status) {
            $notification = Notification::create([
                'channel' => $notificationChannel,
                'type' => $notificationType,
                'text' => $texts[$i++]
            ]);

            $receiverNotification = ReceiverNotification::create([
                'receiver_id' => $receiver->id,
                'notification_id' => $notification->id,
                'status' => $status,
            ]);

            HistoryItem::create([
                'receiver_notification_id' => $receiverNotification->id,
                'status' => $status,
            ]);
        }

        // Act
        $response = $this->getJson(sprintf(self::ROUTE, $receiver->id));

        // Assert
        $response->assertOk();
        $response->assertJsonCount(4);

        $response->assertJsonFragment([
            'notificationText' => $texts[0],
            'channel' => $notificationChannel->value,
            'status' => NotificationProcessStatus::InQueue->value,
        ]);

        $response->assertJsonFragment([
            'notificationText' => $texts[1],
            'channel' => $notificationChannel->value,
            'status' => NotificationProcessStatus::Sent->value,
        ]);

        $response->assertJsonFragment([
            'notificationText' => $texts[2],
            'channel' => $notificationChannel->value,
            'status' => NotificationProcessStatus::Delivered->value,
        ]);

        $response->assertJsonFragment([
            'notificationText' => $texts[3],
            'channel' => $notificationChannel->value,
            'status' => NotificationProcessStatus::Discarded->value,
        ]);
    }
}
