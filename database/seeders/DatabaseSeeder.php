<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\Item;
use App\Models\Location;
use App\Models\ItemTranslation;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */


    public function run(): void
    {
        // 👤 Users
        $users = User::factory(20)->create();

        // 📍 Locations (shared!)
        $locations = Location::factory(30)->create();

        // 📦 Items
        Item::factory(300)
            ->recycle($users)
            ->recycle($locations)
            ->create()
            ->each(function ($item) {

                // 🇵🇱 Polish
                ItemTranslation::factory()->create([
                    'item_id' => $item->id,
                    'locale' => 'pl',
                ]);

                // 🇬🇧 English
                ItemTranslation::factory()->en()->create([
                    'item_id' => $item->id,
                ]);
            });
    }
}
