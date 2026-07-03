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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid_local')->unique();
            $table->foreignId('trip_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Remitente
            $table->string('remitente_nombre');
            $table->string('remitente_dni', 15)->nullable();
            $table->string('remitente_telefono')->nullable();

            // Destinatario
            $table->string('destinatario_nombre');
            $table->string('destinatario_dni', 15)->nullable();
            $table->string('destinatario_telefono')->nullable();

            // Carga
            $table->string('descripcion');
            $table->decimal('peso_kg', 8, 2)->nullable();
            $table->integer('cantidad_bultos')->default(1);
            $table->decimal('precio', 8, 2);
            $table->string('qr_code')->unique();
            $table->enum('estado', ['pendiente', 'en_transito', 'entregado'])->default('pendiente');
            $table->enum('estado_pago', ['pagado', 'por_cobrar'])->default('pagado');
            $table->timestamp('entregado_en')->nullable();

            // Auditoría asíncrona
            $table->enum('tipo_documento', ['BOLETA', 'FACTURA', 'TICKET_INTERNO'])->default('TICKET_INTERNO');
            $table->boolean('sincronizado')->default(false);
            $table->boolean('emitido_en_contingencia')->default(false);
            $table->timestamp('emitido_en')->nullable();
            $table->timestamp('sincronizado_en')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
