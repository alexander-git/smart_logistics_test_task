<?php

declare(strict_types=1);


namespace App\Http\Controllers\Api\V1\Test;

use App\Http\Resources\Api\V1\ReceiverResource;
use App\Models\Receiver;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class InitDataController
{
    public function __construct(
    ) {
    }

    public function __invoke(): AnonymousResourceCollection
    {
        DB::statement('TRUNCATE TABLE outbox RESTART IDENTITY CASCADE');
        DB::statement('TRUNCATE TABLE history RESTART IDENTITY CASCADE');
        DB::statement('TRUNCATE TABLE receiver_notification RESTART IDENTITY CASCADE');
        DB::statement('TRUNCATE TABLE notification RESTART IDENTITY CASCADE');
        DB::statement('TRUNCATE TABLE receiver RESTART IDENTITY CASCADE');

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

        return ReceiverResource::collection($receivers);
    }
}
