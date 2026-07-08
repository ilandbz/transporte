<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\Client::updateOrCreate(
            ['documento' => '00000000'],
            [
                'nombre' => 'CLIENTES VARIOS',
                'telefono' => null,
            ]
        );
    }
}
