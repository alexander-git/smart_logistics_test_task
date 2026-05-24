<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Outbox\OutboxService;
use Illuminate\Console\Command;
use Illuminate\Container\Attributes\Config;

class OutboxRetention extends Command
{
    protected $signature = 'app:outbox-retention';

    protected $description = 'Outbox retention';

    public function __construct(
        private readonly OutboxService $outboxService,
        #[Config('outbox.retention_days')]
        private readonly int $retentionDays,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->outboxService->deleteOutdatedMessages($this->retentionDays);
        return self::SUCCESS;
    }
}
