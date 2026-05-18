<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'notification';

    protected $fillable = [
        'channel',
        'type',
        'text',
    ];

    protected function casts(): array
    {
        return [
            'channel' => NotificationChannel::class,
            'type' => NotificationType::class,
        ];
    }
}
