<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE vehicles MODIFY tipo ENUM('minivan', 'bus', 'coaster', 'auto')");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverting this might drop 'auto' records, usually we don't fully reverse enum additions unless strictly necessary, 
        // but for safety we can revert to original if there are no autos.
        DB::statement("ALTER TABLE vehicles MODIFY tipo ENUM('minivan', 'bus', 'coaster')");
    }
};
