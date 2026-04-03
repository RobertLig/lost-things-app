<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1️⃣ Insert unique locations
        DB::statement("
            INSERT INTO locations (lat, lng, created_at, updated_at)
            SELECT DISTINCT lat, lng, NOW(), NOW()
            FROM items
            WHERE lat IS NOT NULL AND lng IS NOT NULL
        ");

        // 2️⃣ Update items with location_id
        DB::statement("
            UPDATE items
            JOIN locations
              ON locations.lat = items.lat
             AND locations.lng = items.lng
            SET items.location_id = locations.id
        ");
    }
};