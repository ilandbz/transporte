<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Administrador Shinhua',
            'email' => 'admin@shinhua.pe',
            'role' => 'admin',
            'password' => bcrypt('Admin2026*'),
        ]);

        User::create([
            'name' => 'Pedro Ramos Quispe',
            'email' => 'pedro.conductor@shinhua.pe',
            'role' => 'conductor',
            'password' => bcrypt('Conductor2026*'),
        ]);

        User::create([
            'name' => 'María López Torres',
            'email' => 'maria.counter@shinhua.pe',
            'role' => 'counter',
            'password' => bcrypt('Counter2026*'),
        ]);

        User::create([
            'name' => 'Carlos Mendoza Vega',
            'email' => 'carlos.conductor@shinhua.pe',
            'role' => 'conductor',
            'password' => bcrypt('Conductor2026*'),
        ]);
    }
}
