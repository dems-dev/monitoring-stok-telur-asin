<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sku' => 'TLR-'.fake()->unique()->numerify('####'),
            'name' => 'Telur Asin '.fake()->word(),
            'category' => fake()->randomElement(Product::CATEGORIES),
            'price' => fake()->numberBetween(3000, 25000),
            'stock' => fake()->numberBetween(0, 500),
            'min_stock' => fake()->numberBetween(10, 100),
        ];
    }

    /** Stok di bawah batas minimum, untuk menguji peringatan stok rendah. */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 5,
            'min_stock' => 50,
        ]);
    }
}
