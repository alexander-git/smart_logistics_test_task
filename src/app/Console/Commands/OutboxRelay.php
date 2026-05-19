<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OutboxEventType;
use App\Jobs\ProcessNotificationJob;
use App\Models\OutboxMessage;
use App\Services\Outbox\OutboxService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Throwable;

class OutboxRelay extends Command
{
    private const BATCH_SIZE = 100;

    protected $signature = 'app:outbox-relay';

    protected $description = 'Outbox relay process';

    public function __construct(private readonly OutboxService $outboxService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        while (true) {
            $this->processMessageBatch();
            sleep(1);
        }
    }

    public function processMessageBatch(): void
    {
        for ($i = 0; $i < self::BATCH_SIZE; $i++) {
            $message = $this->outboxService->getMessageForProcessing();
            if ($message === null) {
                break;
            }

            try {
                dispatch($this->getJobByMessage($message))->onQueue($message->priority->value);
                $this->outboxService->setMessageSent($message->id);
            } catch (Throwable) {
                $this->outboxService->setMessagePending($message->id);
            }
        }
    }

    private function getJobByMessage(OutboxMessage $message): ShouldQueue
    {
        return match ($message->event_type) {
            OutboxEventType::SendNotification =>
                new ProcessNotificationJob($message->payload['receiver_notification_id']),
        };
    }
}
