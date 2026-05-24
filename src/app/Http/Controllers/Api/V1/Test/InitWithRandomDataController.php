<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Test;

use App\Http\Resources\Api\V1\ReceiverResource;
use App\Services\InitService\InitService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InitWithRandomDataController
{
    public function __construct(
        private  readonly InitService $initService
    ){
    }

    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $receiverCount = $request->input('receiverCount', 10);
        $this->initService->truncateAllTables();
        return ReceiverResource::collection($this->initService->createReceiversRandom($receiverCount));
    }
}
