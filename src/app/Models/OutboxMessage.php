<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OutboxEventPriority;
use App\Enums\OutboxEventType;
use App\Enums\OutboxMessageStatus;
use Illuminate\Database\Eloquent\Model;

class OutboxMessage extends Model
{
    protected $table = 'outbox';

    protected $fillable = [
        'event_type',
        'payload',
        'priority',
        'send_after',
        'status'
    ];

    protected $attributes = [
        'status' => OutboxMessageStatus::Pending,
    ];

    protected function casts(): array
    {
        return [
            'event_type' => OutboxEventType::class,
            'priority' => OutboxEventPriority::class,
            'status' => OutboxMessageStatus::class,
            'payload' => 'array',
        ];
    }
}
