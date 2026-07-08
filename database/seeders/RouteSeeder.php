<?php

namespace Database\Seeders;

use App\Models\Route;
use Illuminate\Database\Seeder;

class RouteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Route::create([
            'nombre' => 'Huánuco - Puños (completa)',
            'origen' => 'Huánuco',
            'destino' => 'Puños',
            'ubigeo_origen' => '100101',
            'ubigeo_destino' => '100801',
            'paradas' => ['Llata'],
            'activo' => true,
        ]);

        Route::create([
            'nombre' => 'Huánuco - Llata',
            'origen' => 'Huánuco',
            'destino' => 'Llata',
            'ubigeo_origen' => '100101',
            'ubigeo_destino' => '100301',
            'paradas' => [],
            'activo' => true,
        ]);

        Route::create([
            'nombre' => 'Llata - Puños',
            'origen' => 'Llata',
            'destino' => 'Puños',
            'ubigeo_origen' => '100301',
            'ubigeo_destino' => '100801',
            'paradas' => [],
            'activo' => true,
        ]);
    }
}
