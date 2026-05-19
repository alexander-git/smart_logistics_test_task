<?php

namespace App\Services\Notification\Processor;

use App\Enums\NotificationChannel;
use App\Models\ReceiverNotification;

interface NotificationChannelProcessorInterface
{
    public function supports(NotificationChannel $channel): bool;

    public function process(ReceiverNotification $receiverNotification): void;
}
