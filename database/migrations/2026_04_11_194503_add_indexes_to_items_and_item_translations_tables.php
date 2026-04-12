<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 📦 items table
        Schema::table('items', function (Blueprint $table) {
            //$table->index('user_id');
            //$table->index('location_id');
            $table->index('lost_at');
        });

        // 🌍 item_translations table
        Schema::table('item_translations', function (Blueprint $table) {
            $table->index(['item_id', 'locale']);

            // 🔍 Fulltext index (MySQL / MariaDB)
            $table->fullText(['title', 'description']);
        });
    }

    public function down(): void
    {
        // 📦 items table
        Schema::table('items', function (Blueprint $table) {
            //$table->dropIndex(['user_id']);
            //$table->dropIndex(['location_id']);
            $table->dropIndex(['lost_at']);
        });

        // 🌍 item_translations table
        Schema::table('item_translations', function (Blueprint $table) {
            $table->dropIndex(['item_id', 'locale']);
            $table->dropFullText(['title', 'description']);
        });
    }
};