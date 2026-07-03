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
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained()->onDelete('cascade');
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // conductor
            $table->string('placa_vehiculo', 10);
            $table->string('numero_manifiesto')->nullable();
            $table->dateTime('fecha_salida');
            $table->dateTime('fecha_llegada_estimada')->nullable();
            $table->enum('estado', ['abierto', 'en_ruta', 'cerrado'])->default('abierto');
            $table->json('asientos_ocupados')->nullable();
            $table->decimal('lat_inicio', 10, 7)->nullable();
            $table->decimal('lng_inicio', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
