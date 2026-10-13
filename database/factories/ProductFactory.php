<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $price = fake()->numberBetween(3000, 25000);

        return [
            'sku' => 'TLR-'.fake()->unique()->numerify('####'),
            'name' => 'Telur Asin '.fake()->word(),
            'category_id' => Category::factory(),
            'unit_id' => Unit::factory(),
            'price' => $price,
            'purchase_price' => (int) round($price * 0.8),
            'stock' => fake()->numberBetween(0, 500),
            'min_stock' => fake()->numberBetween(10, 100),
            'nearest_expiry_date' => fake()->optional()->dateTimeBetween('+1 week', '+1 month'),
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

    /** Mendekati kedaluwarsa (misal 2 hari lagi). */
    public function expiringSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'nearest_expiry_date' => today()->addDays(2),
        ]);
    }
}
