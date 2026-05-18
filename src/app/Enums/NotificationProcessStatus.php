<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationProcessStatus: string
{
    case InQueue = 'inQueue';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Discarded = 'discarded';
}
