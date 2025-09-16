<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Audience;

class AudienceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $audiences = [
            'Administrativo',
            'Operativo',
            'CCL',
            'COPASST',
            'Comité Seguridad Vial',
            'Brigada De Emergencias',
            'Comité Investigador De ATEL',
            'Gestión Humana',
            'Admin Y Financiero',
            'Instalaciones',
            'Diseño Técnico',
            'Diseño Creativo',
            'Metalmecánica',
            'Ensamble Y Empaque',
            'Comercial',
            'Gerencial',
            'General',
        ];

        foreach ($audiences as $name) {
            Audience::firstOrCreate(['name' => $name]);
        }
    }
}
