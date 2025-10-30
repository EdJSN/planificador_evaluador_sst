<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

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
        $users = User::with('roles:id,name')->latest()->paginate(10);

        return view('settings.dashboard', compact('roles', 'users'));
    }

    /**
    
     */
    public function storeUser(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'                  => 'required|string|max:255',
                'email'                 => ['required', 'email', 'max:255', Rule::unique('users', 'email')->whereNull('deleted_at'),],
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

    public function usersIndex()
    {
        // Trae usuarios con roles para evitar N+1
        $users = User::with('roles:id,name')->orderByDesc('id')->paginate(10);
        $roles = Role::orderBy('name')->pluck('name', 'name');

        return view('settings.users', compact('users', 'roles'));
    }

    // Editar usuario 
    public function updateUser(Request $request, User $user)
    {
        try {
            $validated = $request->validate([
                'name'     => ['required', 'string', 'max:255'],
                'email'    => [
                    'required',
                    'email',
                    'max:255',
                    Rule::unique('users', 'email')->ignore($user->id)->whereNull('deleted_at'),
                ],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
                'role'     => ['nullable', 'string', Rule::exists('roles', 'name')],
            ]);

            // Evitar dejar el sistema sin administradores
            if (
                $user->hasRole('admin')
                && (($validated['role'] ?? null) !== 'admin')
                && User::role('admin')->count() === 1
            ) {
                return response()->json([
                    'message' => 'No puedes quitar el último administrador del sistema.'
                ], 403);
            }

            // Normaliza email si quieres tratarlo case-insensitive
            $validated['email'] = mb_strtolower($validated['email']);

            DB::transaction(function () use ($user, $validated) {
                $user->name  = $validated['name'];
                $user->email = $validated['email'];

                // Solo actualizar password si viene algo
                if (!empty($validated['password'])) {
                    $user->password = $validated['password']; // asume mutator/hasheado en el modelo
                }

                $user->save();

                // Sincroniza roles SOLO si la clave 'role' vino en la request
                if (array_key_exists('role', $validated)) {
                    $user->syncRoles($validated['role'] ? [$validated['role']] : []);
                }
            });

            return response()->json([
                'message' => 'Usuario actualizado correctamente.',
                'user'    => $user,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación.',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            // Opcional: log($e->getMessage());
            return response()->json([
                'message' => 'Ocurrió un error al actualizar el usuario.',
            ], 500);
        }
    }

    // Eliminar usuario
    public function destroyUser(Request $request, User $user)
    {
        try {
            // No dejar que un usuario se borre a sí mismo
            if (auth()->id() === $user->id) {
                return response()->json(['message' => 'No puedes eliminar tu propio usuario.'], 403);
            }

            // Confirmación por contraseña del admin actual
            $password = $request->input('password');
            if (!$password || !Hash::check($password, auth()->user()->password)) {
                return response()->json(['message' => 'La contraseña es incorrecta.'], 422);
            }

            // No dejar sin administradores
            if ($user->hasRole('admin') && User::role('admin')->count() === 1) {
                return response()->json(['message' => 'No puedes eliminar al último administrador del sistema.'], 403);
            }

            // Soft delete atómico (si algo falla, revierte)
            DB::transaction(function () use ($user) {
                $user->delete();
            });

            return response()->json(['message' => 'Usuario eliminado correctamente.'], 200);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Ocurrió un error al eliminar el usuario.'], 500);
        }
    }
}
