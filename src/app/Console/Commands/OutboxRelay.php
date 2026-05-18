<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OutboxEventType;
use App\Enums\OutboxMessageStatus;
use App\Jobs\ProcessNotificationJob;
use App\Models\OutboxMessage;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Throwable;
use Carbon\Carbon;

class OutboxRelay extends Command
{
    private const BATCH_SIZE = 100;

    protected $signature = 'app:outbox-relay';

    protected $description = 'Outbox relay process';

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
            $message = DB::transaction(function(): ?OutboxMessage {
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

            if ($message === null) {
                break;
            }

            try {
                dispatch($this->getJobByMessage($message))->onQueue($message->priority->value);
                OutboxMessage::query()
                    ->whereKey($message->id)
                    ->where('status', OutboxMessageStatus::Processing)
                    ->update(['status' => OutboxMessageStatus::Sent]);
            } catch (Throwable) {
                OutboxMessage::query()
                    ->whereKey($message->id)
                    ->where('status', OutboxMessageStatus::Processing)
                    ->update(['status' => OutboxMessageStatus::Pending]);
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
