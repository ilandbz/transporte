<?php

namespace Database\Seeders;

use App\Models\Route;
use App\Models\RouteTariff;
use Illuminate\Database\Seeder;

class RouteTariffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $huanucoPunosId = Route::where('nombre', 'Huánuco - Puños (completa)')->first()->id;
        $huanucoLlataId = Route::where('nombre', 'Huánuco - Llata')->first()->id;
        $llataPunosId = Route::where('nombre', 'Llata - Puños')->first()->id;

        // Tariffs for Huánuco - Puños
        RouteTariff::create([
            'route_id' => $huanucoPunosId,
            'origen_tramo' => 'Huánuco',
            'destino_tramo' => 'Puños',
            'ubigeo_origen' => '100101',
            'ubigeo_destino' => '100801',
            'precio' => 25.00,
            'clase' => 'normal',
        ]);

        RouteTariff::create([
            'route_id' => $huanucoPunosId,
            'origen_tramo' => 'Puños',
            'destino_tramo' => 'Huánuco',
            'ubigeo_origen' => '100801',
            'ubigeo_destino' => '100101',
            'precio' => 25.00,
            'clase' => 'normal',
        ]);

        RouteTariff::create([
            'route_id' => $huanucoPunosId,
            'origen_tramo' => 'Huánuco',
            'destino_tramo' => 'Llata',
            'ubigeo_origen' => '100101',
            'ubigeo_destino' => '100301',
            'precio' => 15.00,
            'clase' => 'normal',
        ]);

        RouteTariff::create([
            'route_id' => $huanucoPunosId,
            'origen_tramo' => 'Llata',
            'destino_tramo' => 'Puños',
            'ubigeo_origen' => '100301',
            'ubigeo_destino' => '100801',
            'precio' => 12.00,
            'clase' => 'normal',
        ]);

        // Tariffs VIP for Huánuco - Puños
        RouteTariff::create([
            'route_id' => $huanucoPunosId,
            'origen_tramo' => 'Huánuco',
            'destino_tramo' => 'Puños',
            'ubigeo_origen' => '100101',
            'ubigeo_destino' => '100801',
            'precio' => 35.00,
            'clase' => 'vip',
        ]);

        RouteTariff::create([
            'route_id' => $huanucoPunosId,
            'origen_tramo' => 'Puños',
            'destino_tramo' => 'Huánuco',
            'ubigeo_origen' => '100801',
            'ubigeo_destino' => '100101',
            'precio' => 35.00,
            'clase' => 'vip',
        ]);

        RouteTariff::create([
            'route_id' => $huanucoPunosId,
            'origen_tramo' => 'Huánuco',
            'destino_tramo' => 'Llata',
            'ubigeo_origen' => '100101',
            'ubigeo_destino' => '100301',
            'precio' => 20.00,
            'clase' => 'vip',
        ]);

        RouteTariff::create([
            'route_id' => $huanucoPunosId,
            'origen_tramo' => 'Llata',
            'destino_tramo' => 'Puños',
            'ubigeo_origen' => '100301',
            'ubigeo_destino' => '100801',
            'precio' => 18.00,
            'clase' => 'vip',
        ]);

        // Tariffs for Huánuco - Llata
        RouteTariff::create([
            'route_id' => $huanucoLlataId,
            'origen_tramo' => 'Huánuco',
            'destino_tramo' => 'Llata',
            'ubigeo_origen' => '100101',
            'ubigeo_destino' => '100301',
            'precio' => 15.00,
            'clase' => 'normal',
        ]);

        RouteTariff::create([
            'route_id' => $huanucoLlataId,
            'origen_tramo' => 'Llata',
            'destino_tramo' => 'Huánuco',
            'ubigeo_origen' => '100301',
            'ubigeo_destino' => '100101',
            'precio' => 15.00,
            'clase' => 'normal',
        ]);

        // Tariffs VIP for Huánuco - Llata
        RouteTariff::create([
            'route_id' => $huanucoLlataId,
            'origen_tramo' => 'Huánuco',
            'destino_tramo' => 'Llata',
            'ubigeo_origen' => '100101',
            'ubigeo_destino' => '100301',
            'precio' => 20.00,
            'clase' => 'vip',
        ]);

        RouteTariff::create([
            'route_id' => $huanucoLlataId,
            'origen_tramo' => 'Llata',
            'destino_tramo' => 'Huánuco',
            'ubigeo_origen' => '100301',
            'ubigeo_destino' => '100101',
            'precio' => 20.00,
            'clase' => 'vip',
        ]);

        // Tariffs for Llata - Puños
        RouteTariff::create([
            'route_id' => $llataPunosId,
            'origen_tramo' => 'Llata',
            'destino_tramo' => 'Puños',
            'ubigeo_origen' => '100301',
            'ubigeo_destino' => '100801',
            'precio' => 12.00,
            'clase' => 'normal',
        ]);

        RouteTariff::create([
            'route_id' => $llataPunosId,
            'origen_tramo' => 'Puños',
            'destino_tramo' => 'Llata',
            'ubigeo_origen' => '100801',
            'ubigeo_destino' => '100301',
            'precio' => 12.00,
            'clase' => 'normal',
        ]);

        // Tariffs VIP for Llata - Puños
        RouteTariff::create([
            'route_id' => $llataPunosId,
            'origen_tramo' => 'Llata',
            'destino_tramo' => 'Puños',
            'ubigeo_origen' => '100301',
            'ubigeo_destino' => '100801',
            'precio' => 18.00,
            'clase' => 'vip',
        ]);

        RouteTariff::create([
            'route_id' => $llataPunosId,
            'origen_tramo' => 'Puños',
            'destino_tramo' => 'Llata',
            'ubigeo_origen' => '100801',
            'ubigeo_destino' => '100301',
            'precio' => 18.00,
            'clase' => 'vip',
        ]);
    }
}
