<?php

namespace App\Console\Commands;

use App\Services\InitService\InitService;
use Illuminate\Console\Command;

class InitData extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:init-data';

    /**
     * @var string
     */
    protected $description = 'Init data';

    public function handle(InitService $initService)
    {
        $initService->truncateAllTables();
        $initService->createReceiversFixed();
        return self::SUCCESS;
    }
}
