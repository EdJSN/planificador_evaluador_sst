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
                'email'    => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
                'role'     => ['nullable', 'string', Rule::exists('roles', 'name')],
            ]);

            // Evitar eliminar último Admin
            if (
                $user->hasRole('admin')
                && (($validated['role'] ?? null) !== 'admin')
                && User::role('admin')->count() === 1
            ) {
                return back()->withErrors(['role' => 'No puedes quitar el último administrador del sistema.'])->withInput();
            }

            DB::transaction(function () use ($user, $validated) {
                $user->name  = $validated['name'];
                $user->email = $validated['email'];

                if (!empty($validated['password'])) {
                    $user->password = $validated['password'];
                }
                $user->save();

                // si envían rol, lo sincroniza; si no, no cambia
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
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al actualizar el usuario.',
            ], 500);
        }
    }

    // Eliminar usuario
    public function destroyUser(User $user)
    {
        try {
            // Evitar autodestruirse
            if (auth()->id() === $user->id) {
                return back()->with('status', 'No puedes eliminar tu propio usuario.');
            }

            // Validar contraseña
            $password = request('password');
            if (!$password || !Hash::check($password, auth()->user()->password)) {
                return back()->withErrors(['password' => 'La contraseña es incorrecta.'])->withInput();
            }

            // Bloquear eliminación del último admin
            if ($user->hasRole('admin') && User::role('admin')->count() === 1) {
                return back()->withErrors(['user' => 'No puedes eliminar al último administrador del sistema.']);
            }

            $user->delete();

            return response()->json([
                'message' => 'Usuario eliminado correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Ocurrió un error al eliminar el usuario.',
            ], 500);
        }
    }
}
