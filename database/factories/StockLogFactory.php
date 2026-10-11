<?php

namespace Database\Factories;

use App\Enums\LogType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockLog>
 */
class StockLogFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(LogType::cases());

        return [
            'type' => $type,
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'amount' => fake()->numberBetween(1, 200),
            'source_or_target' => fake()->company(),
            // Status diturunkan dari type yang BERLAKU, termasuk saat test
            // menimpanya, supaya tidak lahir pasangan mustahil seperti
            // KELUAR + "Selesai".
            'status' => function (array $attributes) {
                $actual = $attributes['type'] instanceof LogType
                    ? $attributes['type']
                    : LogType::from($attributes['type']);

                return fake()->randomElement($actual->allowedStatuses());
            },
            'note' => fake()->optional()->sentence(),
            'logged_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
        ];
    }
}
