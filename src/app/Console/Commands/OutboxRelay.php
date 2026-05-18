<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OutboxEventType;
use App\Jobs\ProcessNotificationJob;
use App\Models\OutboxMessage;
use Illuminate\Console\Command;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Throwable;
use Carbon\Carbon;

class OutboxRelay extends Command
{
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
        DB::transaction(function() {
            $now = Carbon::now();
            $messages = OutboxMessage::query()->where('is_sent', false)
                ->where('send_after', '<', $now)
                ->orderByRaw("
                    CASE
                        WHEN priority = 'high' THEN 0
                        WHEN priority = 'normal' THEN 1
                        ELSE 2
                    END
                ")
                ->orderBy('created_at', 'asc')
                ->limit(100)
                ->lock('FOR UPDATE SKIP LOCKED')
                ->get();

            foreach ($messages as $message) {
                dispatch($this->getJobByMessage($message))->onQueue($message->priority->value);
                $message->is_sent = true;
                $message->save();
            }
        });
    }

    private function getJobByMessage(OutboxMessage $message): ShouldQueue
    {
        return match ($message->event_type) {
            OutboxEventType::SendNotification =>
                new ProcessNotificationJob($message->payload['receiver_notification_id']),
        };
    }
}
