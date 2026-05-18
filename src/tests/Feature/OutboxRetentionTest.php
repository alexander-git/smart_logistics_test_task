<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Console\Commands\OutboxRetention;
use App\Enums\OutboxEventPriority;
use App\Enums\OutboxEventType;
use App\Enums\OutboxMessageStatus;
use App\Models\OutboxMessage;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutboxRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function testOutboxRetentionSuccess(): void
    {
        // Arrange
        $eventType = OutboxEventType::SendNotification;
        $priority = OutboxEventPriority::High;

        $outdatedOutboxMessageId = OutboxMessage::create([
            'event_type' => $eventType,
            'payload' => ['receiver_notification_id' => 1],
            'priority' => $priority,
            'send_after' => Carbon::now(),
            'status' => OutboxMessageStatus::Sent,
        ])->id;

        OutboxMessage::query()
            ->where('id', $outdatedOutboxMessageId)
            ->update(['updated_at' => Carbon::now()->subDays(8)]);

        $actualOutboxMessageId = OutboxMessage::create([
            'event_type' => $eventType,
            'payload' => ['receiver_notification_id' => 1],
            'priority' => $priority,
            'send_after' => Carbon::now(),
            'status' => OutboxMessageStatus::Sent,
        ])->id;

        $pendingOutboxMessageId = OutboxMessage::create([
            'event_type' => $eventType,
            'payload' => ['receiver_notification_id' => 1,],
            'priority' => $priority,
            'send_after' => Carbon::now(),
            'status' => OutboxMessageStatus::Pending,
        ])->id;

        // Act
        /**
         * @var OutboxRetention $command
         */
        $command = app(OutboxRetention::class);
        $command->handle();

        // Assert
        $this->assertDatabaseMissing(self::OUTBOX_TABLE, [
            'id' => $outdatedOutboxMessageId,
        ]);

        $this->assertDatabaseHas(self::OUTBOX_TABLE, [
            'id' => $actualOutboxMessageId,
            'status' => OutboxMessageStatus::Sent->value,
        ]);

        $this->assertDatabaseHas(self::OUTBOX_TABLE, [
            'id' => $pendingOutboxMessageId,
            'status' => OutboxMessageStatus::Pending->value,
        ]);
    }
}
