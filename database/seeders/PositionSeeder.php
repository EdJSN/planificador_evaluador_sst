<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('positions')->insert([
            ['position' => 'Gerente Comercial', 'created_at' => now(), 'updated_at' => now()],
            ['position' => 'Coordinador GH', 'created_at' => now(), 'updated_at' => now()],
            ['position' => 'Profesional SST', 'created_at' => now(), 'updated_at' => now()],
            ['position' => 'Coordinador Comercial', 'created_at' => now(), 'updated_at' => now()],
            ['position' => 'Aprendiz SENA', 'created_at' => now(), 'updated_at' => now()],
            ['position' => 'Jefe de Metalmecánica', 'created_at' => now(), 'updated_at' => now()],
            ['position' => 'Soldador', 'created_at' => now(), 'updated_at' => now()],
            ['position' => 'Líder de Instalación', 'created_at' => now(), 'updated_at' => now()],
            ['position' => 'Jefe de Ensamble', 'created_at' => now(), 'updated_at' => now()],
            ['position' => 'Lider de inventario y mantenimiento ', 'created_at' => now(), 'updated_at' => now()],
            ['position' => 'Auxiliar de ensamble y empaque ', 'created_at' => now(), 'updated_at' => now()],
            ['position' => 'Auxiliar de Instalación ', 'created_at' => now(), 'updated_at' => now()],
            ['position' => 'Operario corte CNC', 'created_at' => now(), 'updated_at' => now()],
            ['position' => 'Auxiliar de metalmecánica', 'created_at' => now(), 'updated_at' => now()],
            ['position' => 'Soldador Junior', 'created_at' => now(), 'updated_at' => now()],
            ['position' => 'Auxiliar de ensamble y pintura', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
