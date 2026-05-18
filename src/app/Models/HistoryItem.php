<?php

namespace App\Models;

use App\Enums\NotificationProcessStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistoryItem extends Model
{
    protected $table = 'history';

    protected $fillable = [
        'receiver_notification_id',
        'status',
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

    public function receiverNotification(): BelongsTo
    {
        return $this->belongsTo(ReceiverNotification::class);
    }
}
