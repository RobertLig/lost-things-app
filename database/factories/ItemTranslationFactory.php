<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ItemTranslation>
 */
class ItemTranslationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'item_id' => \App\Models\Item::factory(),
            'locale' => 'pl',
            'title' => $this->polishTitle(),
            'description' => $this->polishDescription(),
        ];
    }

    public function en(): static
    {
        return $this->state(function () {
            return [
                'locale' => 'en',
                'title' => fake()->sentence(3),
                'description' => fake()->paragraph(),
            ];
        });
    }

    private function polishTitle(): string
    {
        return fake()->randomElement([
            'Zgubiłem portfel',
            'Zgubiłem klucze',
            'Zgubiłem telefon',
            'Zgubiłem plecak',
            'Zgubiłem torbę',
            'Zgubiłem dokumenty',
        ]);
    }

    private function polishDescription(): string
    {
        return fake()->randomElement([
            'Zgubiłem w okolicy przystanku autobusowego.',
            'Zgubiłem w parku, proszę o kontakt.',
            'Prawdopodobnie zgubiłem w centrum miasta.',
            'Zgubiłem w pobliżu sklepu.',
            'Zgubiłem wieczorem, ważne dokumenty w środku.',
        ]);
    }
}
