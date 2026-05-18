<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Receiver;
use Illuminate\Database\Seeder;

class ReceiverSeeder extends Seeder
{
    public function run(): void
    {
        $factory = Receiver::factory();
        // 1
        $factory->create([
           'email' => 'user1@example.com',
           'phone' => '+1000000000',
        ]);

        // 2
        $factory->create([
            'email' => 'user2@example.com',
            'phone' => '+2000000000',
        ]);

        // 3
        $factory->create([
            'email' => 'user3@example.com',
            'phone' => '+3000000000',
        ]);

        // 4
        $factory->create([
            'email' => 'user4@example.com',
            'phone' => null,
        ]);

        // 5
        $factory->create([
            'email' => 'user5@example.com',
            'phone' => null,
        ]);

        // 6
        $factory->create([
            'email' => 'user6@example.com',
            'phone' => null,
        ]);

        // 7
        $factory->create([
            'email' => null,
            'phone' => '+7000000000',
        ]);

        // 8
        $factory->create([
            'email' => null,
            'phone' => '+8000000000',
        ]);

        // 9
        $factory->create([
            'email' => null,
            'phone' => '+9000000000',
        ]);

        // 10
        $factory->create([
            'email' => 'user10@example.com',
            'phone' => '+1000000010'
        ]);
    }
}

