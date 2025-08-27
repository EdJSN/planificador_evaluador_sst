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
        ]);
    }
}
