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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid_local')->unique();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');     // quien vendió
            $table->integer('numero_asiento');
            $table->string('origen_tramo');
            $table->string('destino_tramo');
            $table->string('ubigeo_origen', 6);
            $table->string('ubigeo_destino', 6);
            $table->string('dni_pasajero', 15)->nullable();
            $table->string('nombre_pasajero')->nullable();
            $table->string('placa_vehiculo', 10);            // desnormalizado para SUNAT
            $table->decimal('precio', 8, 2);
            $table->enum('metodo_pago', ['efectivo', 'yape', 'plin', 'transferencia'])->default('efectivo');

            // Facturación electrónica
            $table->enum('tipo_documento', ['BOLETA', 'FACTURA', 'TICKET_INTERNO'])->default('TICKET_INTERNO');
            $table->string('serie_cpe', 4)->nullable();       // B001, F001
            $table->integer('correlativo_cpe')->nullable();
            $table->string('hash_cpe')->nullable();
            $table->string('cdr_status')->nullable();         // 0 = aceptado
            $table->text('cdr_descripcion')->nullable();

            // Auditoría asíncrona
            $table->boolean('sincronizado')->default(false);
            $table->boolean('emitido_en_contingencia')->default(false);
            $table->timestamp('emitido_en')->nullable();      // fecha real de venta
            $table->timestamp('sincronizado_en')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
