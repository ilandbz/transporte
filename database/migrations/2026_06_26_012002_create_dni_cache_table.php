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
        Schema::create('dni_cache', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['dni', 'ruc']);
            $table->string('numero', 15)->unique();
            $table->string('nombre');
            $table->json('extra_json')->nullable();
            $table->timestamp('consultado_en')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dni_cache');
    }
};
