<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'city_id' => City::factory(),
            'name' => $this->faker->words(3, true),
            'slug' => $this->faker->slug(),
            'description' => $this->faker->paragraph(),
            'main_photo' => 'products/sample.jpg', // Provide a default value
            'video' => null,
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'address' => $this->faker->address(),
            'whatsapp_number' => $this->faker->phoneNumber(),
            'phone_number' => $this->faker->phoneNumber(),
            'is_video_call_available' => $this->faker->boolean(),
            'ready_for_video_demo' => $this->faker->boolean(),
            'views_count' => $this->faker->numberBetween(0, 1000),
            'expires_at' => $this->faker->dateTimeBetween('now', '+30 days'),
            'status' => 'active'
        ];
    }
}
