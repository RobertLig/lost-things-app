<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Around Poland (rough bounds)
        return [
            'lat' => $this->faker->latitude(49.0, 54.5),
            'lng' => $this->faker->longitude(14.0, 24.5),
        ];
    }
}
