<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Limpia caché
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $perms = [
            // Activities (Planner)
            'view_activity',
            'create_activity',
            'edit_activity',
            'delete_activity',
            'export_activity',

            // Employees
            'view_employee',
            'create_employee',
            'edit_employee',
            'delete_employee',

            // Check / Controls / Attendance
            'view_control',
            'manage_attendance',
            'unlink_activity_from_control',
            'print_attendees',

            // Settings
            'view_settings',
            'view_user',
            'create_user',
            'edit_user',
            'delete_user',
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate([
                'name' => $p,
                'guard_name' => 'web',
            ]);
        }

        $admin   = Role::firstOrCreate(['name' => 'admin',   'guard_name' => 'web']);
        $manager = Role::firstOrCreate(['name' => 'manager', 'guard_name' => 'web']);

        // Admin: TODO
        $admin->syncPermissions(Permission::all());

        // Manager: solo ver y crear (y gestionar asistencia), nunca eliminar, sin acceso a Settings
        $managerPerms = [
            // Activities
            'view_activity',
            'create_activity',
            'export_activity',

            // Employees
            'view_employee',
            'create_employee',

            // Check / Controls / Attendance
            'view_control',
            'manage_attendance',
            'print_attendees',
        ];
        $manager->syncPermissions($managerPerms);

        // Usuario admin por email (opcional)
        if ($user = User::where('email', 'edhuardguerrero@gmail.com')->first()) {
            $user->assignRole('admin');
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
