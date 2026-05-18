<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Notification\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $receiverNotificationId,
    ) {
    }

    public function handle(NotificationService $notificationService): void
    {
        $notificationService->processNotificationForReceiver($this->receiverNotificationId);
    }
}
