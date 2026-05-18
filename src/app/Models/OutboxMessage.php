<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OutboxEventPriority;
use App\Enums\OutboxEventType;
use Illuminate\Database\Eloquent\Model;

class OutboxMessage extends Model
{
    protected $table = 'outbox';

    protected $fillable = [
        'event_type',
        'payload',
        'priority',
        'send_after',
        'is_sent',
    ];

    protected $attributes = [
        'is_sent' => false,
    ];

    protected function casts(): array
    {
        return [
            'event_type' => OutboxEventType::class,
            'priority' => OutboxEventPriority::class,
            'payload' => 'array',
        ];
    }
}
