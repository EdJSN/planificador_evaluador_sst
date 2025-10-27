<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    /**
     * Dashboard de Ajustes (Usuarios).
     * Incluye la vista settings.create
     */
    public function dashboard()
    {
        // Roles para el select 
        $roles = Role::orderBy('name')->pluck('name', 'name');

        return view('settings.dashboard', compact('roles'));
    }

    /**
    
     */
    public function storeUser(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'                  => 'required|string|max:255',
                'email'                 => 'required|email|max:255|unique:users,email',
                'password'              => 'required|string|min:8|confirmed',
                'role'                  => 'nullable|string|exists:roles,name',
            ]);

            $user = User::create([
                'name'     => $validated['name'],
                'email'    => $validated['email'],
                'password' => $validated['password'],
            ]);

            if (!empty($validated['role'])) {
                $user->assignRole($validated['role']);
            }

            return response()->json([
                'message' => 'Usuario creado exitosamente.',
                'user'    => $user,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al crear el usuario.'
            ], 500);
        }
    }
}
