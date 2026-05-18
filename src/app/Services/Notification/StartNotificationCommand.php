<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;

readonly class StartNotificationCommand
{
    /**
     * @param int[] $receiverIds
     */
    public function __construct(
        public string              $text,
        public NotificationChannel $notificationChannel,
        public NotificationType    $notificationType,
        public array               $receiverIds
    ) {
    }
}
