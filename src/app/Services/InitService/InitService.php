<?php

declare(strict_types=1);

namespace App\Services\InitService;

use App\Models\Receiver;
use Illuminate\Support\Facades\DB;

class InitService
{
    public function truncateAllTables(): void
    {
        DB::statement('TRUNCATE TABLE outbox RESTART IDENTITY CASCADE');
        DB::statement('TRUNCATE TABLE history RESTART IDENTITY CASCADE');
        DB::statement('TRUNCATE TABLE receiver_notification RESTART IDENTITY CASCADE');
        DB::statement('TRUNCATE TABLE notification RESTART IDENTITY CASCADE');
        DB::statement('TRUNCATE TABLE receiver RESTART IDENTITY CASCADE');
    }

    /**
     * @return Receiver[]
     */
    public function createReceiversFixed(): array
    {
        $receiversAttributes = [
            ['email' => 'user1@example.com', 'phone' => '+1000000000'],
            ['email' => 'user2@example.com', 'phone' => '+2000000000'],
            ['email' => 'user3@example.com', 'phone' => '+3000000000'],
            ['email' => 'user4@example.com', 'phone' => null],
            ['email' => 'user5@example.com', 'phone' => null],
            ['email' => 'user6@example.com', 'phone' => null],
            ['email' => null, 'phone' => '+7000000000'],
            ['email' => null, 'phone' => '+8000000000'],
            ['email' => null, 'phone' => '+9000000000'],
            ['email' => 'user10@example.com', 'phone' => '+1000000010'],
        ];

        $receivers = [];
        foreach ($receiversAttributes as $receiverAtributes) {
            $receivers[] = Receiver::factory()->create($receiverAtributes);
        }

        return  $receivers;
    }

    /**
     * @return Receiver[]
     */
    public function createReceiversRandom(int $receiverCount): array
    {
        $receivers = [];
        for ($i = 0; $i < $receiverCount; $i++) {
            $attributes = [];
            $rand = rand(1, 3);
            if ($rand === 1) {
                $attributes['email'] = null;
            } elseif ($rand === 2) {
                $attributes['phone'] = null;
            }

            $receivers[] = Receiver::factory()->create($attributes);
        }

        return  $receivers;
    }
}
