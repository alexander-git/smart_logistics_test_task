<?php

declare(strict_types=1);

namespace App\Enums;

enum OutboxEventPriority: string
{
    case Normal = 'normal';
    case High = 'high';

    public static function fromNotificationType(NotificationType $notificationType): self
    {
        return match ($notificationType) {
            NotificationType::Transactional => self::High,
            NotificationType::Marketing => self::Normal,
        };
    }
}
