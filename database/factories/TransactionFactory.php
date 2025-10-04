<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {

        return [
            'sender_id' => User::factory()->withBalance(fake()->randomFloat(2, 1, 10000)),
            'receiver_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 1, 1000),
            'commission_fee' => fake()->randomFloat(2, 1, 100),
        ];
    }
}
