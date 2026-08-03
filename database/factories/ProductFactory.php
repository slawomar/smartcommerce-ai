<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => ucfirst(fake()->words(3, true)),
            'sku' => strtoupper(fake()->unique()->bothify('???-#####')),
            'description' => fake()->optional(0.8)->paragraph(),
            'price' => fake()->randomFloat(2, 5, 5000),
            'stock' => fake()->numberBetween(0, 250),
            'is_active' => fake()->boolean(85),
        ];
    }
}
