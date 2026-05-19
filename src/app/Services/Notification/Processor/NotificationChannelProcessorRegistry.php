<?php

declare(strict_types=1);

namespace App\Services\Notification\Processor;

use App\Enums\NotificationChannel;
use RuntimeException;

class NotificationChannelProcessorRegistry
{
    /**
     * @param array<NotificationChannelProcessorInterface> $processors
     */
    public function __construct(private readonly array $processors) {
    }

    public function get(NotificationChannel $channel): NotificationChannelProcessorInterface
    {
        foreach ($this->processors as $processor) {
            if ($processor->supports($channel)) {
                return $processor;
            }
        }

        throw new RuntimeException("Processor for channel {$channel->value} not found");
    }
}
