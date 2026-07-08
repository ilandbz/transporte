<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Branch;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branchHuanuco = Branch::firstOrCreate(
            ['name' => 'Sucursal Principal Huánuco'],
            ['address' => 'Terminal Terrestre Huánuco', 'is_active' => true]
        );

        $branchLlata = Branch::firstOrCreate(
            ['name' => 'Sucursal Llata'],
            ['address' => 'Plaza de Armas Llata', 'is_active' => true]
        );

        User::updateOrCreate(
            ['email' => 'admin@shinhua.pe'],
            [
                'name' => 'Administrador Shinhua',
                'role' => 'admin',
                'password' => bcrypt('Admin2026*'),
                'branch_id' => $branchHuanuco->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'pedro.conductor@shinhua.pe'],
            [
                'name' => 'Pedro Ramos Quispe',
                'role' => 'conductor',
                'password' => bcrypt('Conductor2026*'),
                'branch_id' => $branchHuanuco->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'maria.counter@shinhua.pe'],
            [
                'name' => 'María López Torres',
                'role' => 'counter',
                'password' => bcrypt('Counter2026*'),
                'branch_id' => $branchLlata->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'carlos.conductor@shinhua.pe'],
            [
                'name' => 'Carlos Mendoza Vega',
                'role' => 'conductor',
                'password' => bcrypt('Conductor2026*'),
                'branch_id' => $branchLlata->id,
            ]
        );
    }
}
