<?php

namespace App\Models;

use App\Enums\NotificationProcessStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReceiverNotification extends Model
{
    protected $table = 'receiver_notification';

    protected $fillable = [
        'receiver_id',
        'notification_id',
        'status',
        'retry_count'
    ];

    protected $attributes = [
        'status' => NotificationProcessStatus::InQueue,
    ];

    protected function casts(): array
    {
        return [
            'status' => NotificationProcessStatus::class,
        ];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(Receiver::class);
    }

    public function historyItems(): HasMany
    {
        return $this->hasMany(HistoryItem::class);
    }
}
