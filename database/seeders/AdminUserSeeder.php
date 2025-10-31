<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $user = User::updateOrCreate(
            ['email' => 'sst@azloplay.com'],
            [
                'name' => 'Profesional SST',
                'password' => Hash::make('SSTazlo2025'),
            ]
        );
        $user->assignRole($role);
    }
}
