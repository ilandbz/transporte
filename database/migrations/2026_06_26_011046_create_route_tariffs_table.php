<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('route_tariffs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained()->onDelete('cascade');
            $table->string('origen_tramo');
            $table->string('destino_tramo');
            $table->string('ubigeo_origen', 6);
            $table->string('ubigeo_destino', 6);
            $table->decimal('precio', 8, 2);
            $table->string('clase')->default('normal');
            $table->timestamps();

            $table->unique(['route_id', 'origen_tramo', 'destino_tramo', 'clase'], 'tariffs_route_tramo_clase_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('route_tariffs');
    }
};
