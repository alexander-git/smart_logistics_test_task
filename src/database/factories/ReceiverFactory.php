<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Receiver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Receiver>
 */
class ReceiverFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->email(),
            'phone' => fake()->phoneNumber(),
        ];
    }
}
