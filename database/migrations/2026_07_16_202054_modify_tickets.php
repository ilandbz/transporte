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
        // ya que ->change() requeriría instalar doctrine/dbal.
        DB::statement('ALTER TABLE tickets MODIFY trip_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE tickets MODIFY numero_asiento INT NULL');
        DB::statement('ALTER TABLE tickets MODIFY origen_tramo VARCHAR(255) NULL');
        DB::statement('ALTER TABLE tickets MODIFY destino_tramo VARCHAR(255) NULL');
        DB::statement('ALTER TABLE tickets MODIFY ubigeo_origen VARCHAR(6) NULL');
        DB::statement('ALTER TABLE tickets MODIFY ubigeo_destino VARCHAR(6) NULL');
        DB::statement('ALTER TABLE tickets MODIFY placa_vehiculo VARCHAR(10) NULL');

        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->after('user_id')->constrained('clients')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->after('cliente_id')->constrained()->nullOnDelete();
            $table->foreignId('lugar_origen_id')->nullable()->after('vehicle_id')->constrained('lugars')->nullOnDelete();
            $table->foreignId('lugar_destino_id')->nullable()->after('lugar_origen_id')->constrained('lugars')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropForeign(['vehicle_id']);
            $table->dropForeign(['lugar_origen_id']);
            $table->dropForeign(['lugar_destino_id']);
            $table->dropColumn(['cliente_id', 'vehicle_id', 'lugar_origen_id', 'lugar_destino_id']);
        });
    }
};
