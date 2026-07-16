<?php

namespace Database\Seeders;

use App\Models\Lugar;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LugarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lugares = [
            'ANTAMINA',
            'APARICIO POMARES',
            'ARANCAY',
            'BELLAS FLORES',
            'CAHUAC',
            'CANCHABAMBA',
            'CARHUAPATA',
            'CASCANGA',
            'CHACABAMBA',
            'CHAVIN DE PARIARCA',
            'CHAVINILLO',
            'CHORAS',
            'CHUQUIS',
            'COCHABAMBA',
            'EL PORVENIR',
            'HUACAYBAMBA',
            'HUANUCO',
            'HUANUCO PAMPA',
            'ILAURO',
            'IRMA CHICO',
            'IRMA GRANDE',
            'ISHANCA',
            'JACAS CHICO',
            'JACAS GRANDE',
            'JIRCAN',
            'LA UNION',
            'LIBERTAD',
            'LLATA',
            'MARIAS',
            'MATACANCHA',
            'MIRAFLORES',
            'MONZON',
            'MORCA',
            'NUEVAS FLORES',
            'OBAS',
            'PACHAS',
            'PAMPAMARCA',
            'PAMPAS CARMEN',
            'PAMPAS DE FLORES',
            'PINRA',
            'POQUE',
            'PROGRESO',
            'PUMACAHUA',
            'PUNCHAO',
            'PUÑOS',
            'QUIPRAN',
            'QUISQUI',
            'QUIVILLA',
            'RIPAN',
            'SAN JUAN DE QUEROSH',
            'SAN JUAN DE PAMPAN',
            'SAN JUAN DE VISCAS',
            'SAN MIGUEL',
            'SANTA ROSA DE PAMPAN',
            'SHUNQUI',
            'SILLAPATA',
            'SINGA',
            'SUSUPILLO',
            'TANTAMAYO',
            'TINGO CHICO',
            'YACUS',
            'YANAS',
        ];

        foreach ($lugares as $nombre) {
            Lugar::firstOrCreate(['nombre' => $nombre]);
        }

        $this->command->getOutput()->writeln(count($lugares) . ' lugares cargados.');
    }
}
