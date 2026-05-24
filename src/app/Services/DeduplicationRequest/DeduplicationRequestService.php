<?php

declare(strict_types=1);

namespace App\Services\DeduplicationRequest;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Support\Facades\Cache;

#[Singleton]
class DeduplicationRequestService implements DeduplicateRequestServiceInterface
{
    private const TTL = 300;

    public function __construct(
    ) {
    }

    public function isDuplicate(array $data): bool
    {
        return !Cache::add($this->generateKey($data), 1, self::TTL);
    }
    private function generateKey(array $data): string
    {
        ksort($data);
        return 'request:deduplicate:' . hash('sha256', json_encode($data));
    }
}
