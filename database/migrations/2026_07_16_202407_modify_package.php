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
        DB::statement('ALTER TABLE packages MODIFY trip_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE packages MODIFY remitente_nombre VARCHAR(255) NULL');
        DB::statement('ALTER TABLE packages MODIFY destinatario_nombre VARCHAR(255) NULL');
        DB::statement("ALTER TABLE packages MODIFY estado ENUM('pendiente','en_transito','entregado','anulado') DEFAULT 'pendiente'");
        DB::statement("ALTER TABLE packages MODIFY estado_pago ENUM('pagado','por_cobrar','anulado') DEFAULT 'pagado'");

        Schema::table('packages', function (Blueprint $table) {
            $table->foreignId('cliente_remitente_id')->nullable()->after('user_id')->constrained('clients')->nullOnDelete();
            $table->foreignId('cliente_destinatario_id')->nullable()->after('cliente_remitente_id')->constrained('clients')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->after('cliente_destinatario_id')->constrained()->nullOnDelete();
            $table->foreignId('lugar_origen_id')->nullable()->after('vehicle_id')->constrained('lugars')->nullOnDelete();
            $table->foreignId('lugar_destino_id')->nullable()->after('lugar_origen_id')->constrained('lugars')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropForeign(['cliente_remitente_id']);
            $table->dropForeign(['cliente_destinatario_id']);
            $table->dropForeign(['vehicle_id']);
            $table->dropForeign(['lugar_origen_id']);
            $table->dropForeign(['lugar_destino_id']);
            $table->dropColumn([
                'cliente_remitente_id',
                'cliente_destinatario_id',
                'vehicle_id',
                'lugar_origen_id',
                'lugar_destino_id',
            ]);
        });
    }
};
