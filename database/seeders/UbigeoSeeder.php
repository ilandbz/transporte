<?php

namespace Database\Seeders;

use App\Models\Departamento;
use App\Models\Distrito;
use App\Models\Provincia;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UbigeoSeeder extends Seeder
{
    /**
     * Ejecuta el seed. Requiere el archivo CSV de ubigeo en
     * base_path('archivos/ubigeo.csv') — cópialo desde tu otro proyecto
     * (mismo formato: columna 0 = ubigeo de 6 dígitos, separador ';').
     */
    public function run(): void
    {
        // $path = base_path('archivos/ubigeo.csv');
        $path = storage_path('app/private/ubigeo.csv');

        if (!file_exists($path)) {
            $this->command->getOutput()->writeln("<error>No se encontró el archivo: {$path}</error>");
            $this->command->getOutput()->writeln('Copia el CSV de ubigeo desde tu otro proyecto a esa ruta y vuelve a correr el seeder.');
            return;
        }

        $csvFile = fopen($path, 'r');
        $nroRegistros = -1;
        while (fgetcsv($csvFile, 555, ';') !== false) {
            $nroRegistros += 1;
        }
        fclose($csvFile);

        $this->command->getOutput()->writeln('Iniciando importación de Ubigeo...');

        DB::unprepared('SET FOREIGN_KEY_CHECKS = 0;');
        Distrito::truncate();
        Provincia::truncate();
        Departamento::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        $this->command->getOutput()->writeln('Tablas limpiadas.');

        $csvFile2 = fopen($path, 'r');
        $progressBar = $this->command->getOutput()->createProgressBar($nroRegistros);
        $progressBar->start();

        $firstLine = true;
        $departamentosCache = [];
        $provinciasCache = [];

        while (($fila = fgetcsv($csvFile2, 2000, ';')) !== false) {
            if (!$firstLine) {
                $codDepartamento = substr($fila[0], 0, 2);
                $codProvincia = substr($fila[0], 0, 4);
                $codDistrito = $fila[0];

                if (!isset($departamentosCache[$codDepartamento])) {
                    $departamentosCache[$codDepartamento] = Departamento::firstOrCreate(
                        ['codigo' => $codDepartamento],
                        ['nombre' => $fila[3]]
                    );
                }
                $departamento = $departamentosCache[$codDepartamento];

                if (substr($codProvincia, -2) !== '00' && !isset($provinciasCache[$codProvincia])) {
                    $provinciasCache[$codProvincia] = Provincia::firstOrCreate(
                        ['codigo' => $codProvincia],
                        ['departamento_id' => $departamento->id, 'nombre' => $fila[2]]
                    );
                }

                if (substr($codDistrito, -2) !== '00' && isset($provinciasCache[$codProvincia])) {
                    Distrito::firstOrCreate(
                        ['ubigeo' => $codDistrito],
                        ['provincia_id' => $provinciasCache[$codProvincia]->id, 'nombre' => $fila[1]]
                    );
                }

                $progressBar->advance();
            }
            $firstLine = false;
        }

        fclose($csvFile2);
        $progressBar->finish();
        $this->command->getOutput()->writeln('');
        $this->command->getOutput()->writeln('Importación finalizada.');
    }
}
