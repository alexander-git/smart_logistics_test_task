<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\OutboxMessageStatus;
use App\Models\OutboxMessage;
use Carbon\Carbon;
use Illuminate\Console\Command;

class OutboxRetention extends Command
{
    protected $signature = 'app:outbox-retention';

    protected $description = 'Outbox retention';

    private const RETENTION_DAYS = 7;

    public function handle(): int
    {
        $this->removeOutdatedMessages();
        return self::SUCCESS;
    }

    private function removeOutdatedMessages(): void
    {
        $deleteAfter = Carbon::now()->subDays(self::RETENTION_DAYS);
        OutboxMessage::query()
            ->where('status', OutboxMessageStatus::Sent)
            ->where('updated_at', '<', $deleteAfter)
            ->delete();
    }
}
