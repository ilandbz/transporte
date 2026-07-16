<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jornadas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // conductor
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->date('fecha');
            $table->enum('estado', ['activa', 'cerrada'])->default('activa');
            $table->decimal('lat_inicio', 10, 7)->nullable();
            $table->decimal('lng_inicio', 10, 7)->nullable();
            $table->timestamp('iniciado_en')->nullable();
            $table->timestamp('cerrado_en')->nullable();
            $table->timestamps();
        });
        DB::statement('ALTER TABLE gps_tracks MODIFY trip_id BIGINT UNSIGNED NULL');

        Schema::table('gps_tracks', function (Blueprint $table) {
            $table->foreignId('jornada_id')->nullable()->after('trip_id')->constrained('jornadas')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gps_tracks', function (Blueprint $table) {
            $table->dropForeign(['jornada_id']);
            $table->dropColumn('jornada_id');
        });
        Schema::dropIfExists('jornadas');
    }
};
