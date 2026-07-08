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
        $minivanLayoutNormal = json_encode([
            'filas' => 5,
            'columnas' => 2,
            'pasillo' => null,
            'asientos' => array_map(fn($i) => ['numero' => $i, 'clase' => 'normal'], range(1, 10)),
        ]);

        $minivanLayoutMixto = json_encode([
            'filas' => 5,
            'columnas' => 2,
            'pasillo' => null,
            'asientos' => [
                ['numero' => 1, 'clase' => 'vip'],
                ['numero' => 2, 'clase' => 'vip'],
                ['numero' => 3, 'clase' => 'normal'],
                ['numero' => 4, 'clase' => 'normal'],
                ['numero' => 5, 'clase' => 'normal'],
                ['numero' => 6, 'clase' => 'normal'],
                ['numero' => 7, 'clase' => 'normal'],
                ['numero' => 8, 'clase' => 'normal'],
                ['numero' => 9, 'clase' => 'normal'],
                ['numero' => 10, 'clase' => 'normal'],
            ],
        ]);

        $coasterLayoutMixto = json_encode([
            'filas' => 5,
            'columnas' => 4,
            'pasillo' => 2,
            'asientos' => array_map(function($i) {
                return ['numero' => $i, 'clase' => $i <= 4 ? 'vip' : 'normal'];
            }, range(1, 20)),
        ]);
        
        $busLayoutMixto = json_encode([
            'filas' => 10,
            'columnas' => 4,
            'pasillo' => 2,
            'asientos' => array_map(function($i) {
                return ['numero' => $i, 'clase' => $i <= 12 ? 'vip' : 'normal'];
            }, range(1, 40)),
        ]);

        // Minivans (10 pasajeros)
        Vehicle::create([
            'placa' => 'ABC-123',
            'marca' => 'Toyota',
            'modelo' => 'Hiace',
            'tipo' => 'minivan',
            'capacidad_asientos' => 10,
            'layout_asientos' => $minivanLayoutNormal,
            'activo' => true,
        ]);

        Vehicle::create([
            'placa' => 'GHI-789',
            'marca' => 'Toyota',
            'modelo' => 'Hiace',
            'tipo' => 'minivan',
            'capacidad_asientos' => 10,
            'layout_asientos' => $minivanLayoutMixto,
            'activo' => true,
        ]);
        
        Vehicle::create([
            'placa' => 'JKL-012',
            'marca' => 'Nissan',
            'modelo' => 'Urvan',
            'tipo' => 'minivan',
            'capacidad_asientos' => 10,
            'layout_asientos' => $minivanLayoutMixto,
            'activo' => true,
        ]);

        // Coasters (20 pasajeros)
        Vehicle::create([
            'placa' => 'DEF-456',
            'marca' => 'Hyundai',
            'modelo' => 'County',
            'tipo' => 'coaster',
            'capacidad_asientos' => 20,
            'layout_asientos' => $coasterLayoutMixto,
            'activo' => true,
        ]);
        
        Vehicle::create([
            'placa' => 'MNO-345',
            'marca' => 'Toyota',
            'modelo' => 'Coaster',
            'tipo' => 'coaster',
            'capacidad_asientos' => 20,
            'layout_asientos' => $coasterLayoutMixto,
            'activo' => true,
        ]);

        // Buses (40 pasajeros)
        Vehicle::create([
            'placa' => 'PQR-678',
            'marca' => 'Mercedes-Benz',
            'modelo' => 'O500',
            'tipo' => 'bus',
            'capacidad_asientos' => 40,
            'layout_asientos' => $busLayoutMixto,
            'activo' => true,
        ]);
        
        Vehicle::create([
            'placa' => 'STU-901',
            'marca' => 'Volvo',
            'modelo' => 'B430R',
            'tipo' => 'bus',
            'capacidad_asientos' => 40,
            'layout_asientos' => $busLayoutMixto,
            'activo' => true,
        ]);
    }
}
