<?php

namespace App\Console\Commands;

use App\Services\InitService\InitService;
use Illuminate\Console\Command;

class InitWithRandomData extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:init-with-random-data {--receiverCount=10}';

    /**
     * @var string
     */
    protected $description = 'Init with random data';

    public function handle(InitService $initService)
    {
        $receiverCount = $this->option('receiverCount');
        $initService->truncateAllTables();
        $initService->createReceiversRandom($receiverCount);
        return self::SUCCESS;
    }
}
