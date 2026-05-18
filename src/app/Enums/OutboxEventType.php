<?php

declare(strict_types=1);

namespace App\Enums;

enum OutboxEventType: string
{
    case SendNotification = 'sendNotification';
}

