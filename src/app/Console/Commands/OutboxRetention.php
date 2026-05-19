<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Outbox\OutboxService;
use Illuminate\Console\Command;

class OutboxRetention extends Command
{
    protected $signature = 'app:outbox-retention';

    protected $description = 'Outbox retention';

    public function __construct(private readonly OutboxService $outboxService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->outboxService->deleteOutdatedMessages(config('outbox.retention_days'));
        return self::SUCCESS;
    }
}
