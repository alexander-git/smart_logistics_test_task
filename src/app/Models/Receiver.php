<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationChannel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Receiver extends Model
{
    use HasFactory;

    protected $table = 'receiver';

    protected $fillable = [
        'email',
        'phone',
    ];

    public function receiverNotifications(): HasMany
    {
        return $this->hasMany(ReceiverNotification::class);
    }

    /**
     * @param int[] $receiverIds
     * @param NotificationChannel $channel
     * @return int[]
     */
    public static function getExistingIdsAmongGivenByChannel(array $receiverIds, NotificationChannel $channel): array
    {
        $field = match ($channel) {
            NotificationChannel::Email => 'email',
            NotificationChannel::Sms => 'phone',
        };

        return self::whereIn('id', $receiverIds)
            ->whereNotNull($field)
            ->pluck('id')
            ->toArray();
    }
}
