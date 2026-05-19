<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Test;

use App\Http\Resources\Api\V1\ReceiverResource;
use App\Models\Receiver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class InitWithRandomDataController
{
    public function __construct(
    ) {
    }

    public function __invoke(Request $request): AnonymousResourceCollection
    {
        DB::statement('TRUNCATE TABLE outbox RESTART IDENTITY CASCADE');
        DB::statement('TRUNCATE TABLE history RESTART IDENTITY CASCADE');
        DB::statement('TRUNCATE TABLE receiver_notification RESTART IDENTITY CASCADE');
        DB::statement('TRUNCATE TABLE notification RESTART IDENTITY CASCADE');
        DB::statement('TRUNCATE TABLE receiver RESTART IDENTITY CASCADE');

        $receiverCount = $request->input('receiverCount', 10);
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

        return ReceiverResource::collection($receivers);
    }
}
