<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Client::updateOrCreate(
            ['documento' => '00000000'],
            [
                'nombre' => 'CLIENTES VARIOS',
                'telefono' => '000000000',
            ]
        );


        Client::updateOrCreate(
            ['documento' => '20573020970'],
            [
                'nombre' => 'RED DE SALUD DE HUAMALIES',
                'telefono' => '616000',
            ]
        );


        Client::updateOrCreate(
            ['documento' => '45255166'],
            [
                'nombre' => 'WILMER CAQUI PADILLA',
                'telefono' => '910566893',
            ]
        );


        Client::updateOrCreate(
            ['documento' => '45532962'],
            [
                'nombre' => 'CRISTIAN FIGUEROA FERRER',
                'telefono' => '933552905',
            ]
        );
    }
}
