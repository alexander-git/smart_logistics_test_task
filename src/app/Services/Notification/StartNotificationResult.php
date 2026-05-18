<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;

readonly class StartNotificationResult
{
    /**
     * @param int[] $acceptedReceiverIds
     * @param int[] $skippedReceiverIds
     */
    public function __construct(
        public array $acceptedReceiverIds,
        public array $skippedReceiverIds,
    ) {
    }
}
