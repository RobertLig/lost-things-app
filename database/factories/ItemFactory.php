<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'location_id' => \App\Models\Location::factory(),
            'lost_at' => $this->faker->dateTimeBetween('-30 days', 'now'),

            // 📸 Fake image library (JSON)
            'library' => $this->fakeLibrary(),
        ];
    }

    private function fakeLibrary(): array
    {
        if ($this->faker->boolean(50)) {
            return [];
        }

        $files = Storage::disk('public')->files('item-photos');

        if (empty($files)) {
            return [];
        }

        $images = [];
        $count = $this->faker->numberBetween(1, 3);

        for ($i = 0; $i < $count; $i++) {
            $path = $this->faker->randomElement($files);

            $images[] = [
                'url' => Storage::url($path),
                'path' => $path,
            ];
        }

        return $images;
    }
}
