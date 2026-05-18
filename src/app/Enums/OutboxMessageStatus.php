<?php

declare(strict_types=1);

namespace App\Enums;

enum OutboxMessageStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Sent = 'sent';
}
