<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $minivanLayout = json_encode([
            'filas' => 5,
            'columnas' => 2,
            'pasillo' => null,
            'asientos' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
        ]);

        $coasterLayout = json_encode([
            'filas' => 5,
            'columnas' => 4,
            'pasillo' => 2,
            'asientos' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20],
        ]);

        Vehicle::create([
            'placa' => 'ABC-123',
            'marca' => 'Toyota',
            'modelo' => 'Hiace',
            'tipo' => 'minivan',
            'capacidad_asientos' => 10,
            'layout_asientos' => $minivanLayout,
            'activo' => true,
        ]);

        Vehicle::create([
            'placa' => 'DEF-456',
            'marca' => 'Hyundai',
            'modelo' => 'County',
            'tipo' => 'coaster',
            'capacidad_asientos' => 20,
            'layout_asientos' => $coasterLayout,
            'activo' => true,
        ]);

        Vehicle::create([
            'placa' => 'GHI-789',
            'marca' => 'Toyota',
            'modelo' => 'Hiace',
            'tipo' => 'minivan',
            'capacidad_asientos' => 10,
            'layout_asientos' => $minivanLayout,
            'activo' => true,
        ]);
    }
}
