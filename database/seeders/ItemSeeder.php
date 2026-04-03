<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Location;
use App\Models\Item;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = Location::factory()->count(10)->create();

        Item::factory()
            ->count(50)
            ->make()
            ->each(function ($item) use ($locations) {
                $item->location_id = $locations->random()->id;
                $item->save();
            });
    }
}
