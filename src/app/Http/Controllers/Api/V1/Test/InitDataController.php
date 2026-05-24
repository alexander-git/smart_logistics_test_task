<?php

declare(strict_types=1);


namespace App\Http\Controllers\Api\V1\Test;

use App\Http\Resources\Api\V1\ReceiverResource;
use App\Services\InitService\InitService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InitDataController
{
    public function __construct(
        private  readonly InitService $initService
    ){
    }

    public function __invoke(): AnonymousResourceCollection
    {
        $this->initService->truncateAllTables();
        return ReceiverResource::collection($this->initService->createReceiversFixed());
    }
}
