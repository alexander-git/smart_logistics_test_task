<?php

declare(strict_types=1);

namespace App\Services\DeduplicationRequest;

interface DeduplicateRequestServiceInterface
{
    public function isDuplicate(array $data): bool;
}
