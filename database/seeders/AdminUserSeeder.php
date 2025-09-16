<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'edhuardguerrero@gmail.com'],
            [
                'name' => 'Eduard Guerrero',
                'password' => Hash::make('azlo2025'),
            ]
        );
    }
}
