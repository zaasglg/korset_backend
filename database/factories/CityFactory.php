<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\City>
 */
class CityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->city(),
            'description' => fake()->sentence(),
            'region_id' => function () {
                // Try to get the first region, or create one if none exists
                $region = \App\Models\Region::first();
                if (!$region) {
                    $region = \App\Models\Region::create([
                        'name' => 'Test Region'
                    ]);
                }
                return $region->id;
            },
        ];
    }
}
