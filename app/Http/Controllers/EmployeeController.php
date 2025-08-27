<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Activity;
use App\Models\Attendance;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Vista principal (dashboard).
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $employees = Employee::with('position')->orderBy('names', 'asc')->get();
        $positions = Position::orderBy('position')->get();
        $activities = Activity::whereIn('states', ['P', 'A', 'R'])->get();

        return view('employees.dashboard', compact('employees', 'positions', 'activities'));
    }

    /*
    |--------------------------------------------------------------------------
    | Formulario de creación.
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        return view('employees.create');
    }

    /*
    |--------------------------------------------------------------------------
    | Almacenar nuevo empleado.
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'names' => 'required|string|max:100',
                'lastname1' => 'required|string|max:100',
                'lastname2' => 'nullable|string|max:100',
                'document' => 'required|string|max:50|unique:employees,document',
                'position_id' => 'required|exists:positions,id',
                'signature'   => 'required|string',
            ]);

            $filePath = $this->storeSignatureBase64($validatedData['signature'], $validatedData['document']);

            $employee = Employee::create([
                'names'       => $validatedData['names'],
                'lastname1'   => $validatedData['lastname1'],
                'lastname2'   => $validatedData['lastname2'] ?? null,
                'document'    => $validatedData['document'],
                'position_id' => $validatedData['position_id'],
                'file_path'   => $filePath,
            ]);

            return response()->json([
                'message' => 'Empleado registrado exitosamente.',
                'employee' => $employee
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Ocurrió un error al registrar el empleado.'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Actualizar nuevo empleado.
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, Employee $employee)
    {
        try {
            $validatedData = $request->validate([
                'names' => 'required|string|max:100',
                'lastname1' => 'required|string|max:100',
                'lastname2' => 'nullable|string|max:100',
                'document' => 'required|string|max:50|unique:employees,document,' . $employee->id,
                'position_id' => 'required|exists:positions,id',
                'signature'   => 'nullable|string',
            ]);

            $employee->fill([
                'names'       => $validatedData['names'],
                'lastname1'   => $validatedData['lastname1'],
                'lastname2'   => $validatedData['lastname2'] ?? null,
                'document'    => $validatedData['document'],
                'position_id' => $validatedData['position_id'],
            ]);

            if ($request->filled('signature')) {
                $oldPath  = $employee->file_path;
                $newPath  = $this->storeSignatureBase64($request->input('signature'), $employee->document);
                $employee->file_path = $newPath;
                $employee->save();

                // Borrar el archivo anterior SOLO después de guardar con éxito
                if ($oldPath && Storage::disk('signatures')->exists($oldPath)) {
                    Storage::disk('signatures')->delete($oldPath);
                }
            } else {
                // No llegó firma => se preserva la anterior
                $employee->save();
            }

            return response()->json([
                'message'  => 'Empleado actualizado exitosamente.',
                'employee' => $employee
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors'  => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al actualizar empleado: ' . $e->getMessage());
            return response()->json(['message' => 'Error interno al actualizar el empleado.'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Eliminar empleado (soft delete).
    |--------------------------------------------------------------------------
    */
    public function destroy(Request $request, Employee $employee)
    {
        $request->validate([
            'password' => 'required',
        ]);

        if (!Hash::check($request->password, Auth::user()->password)) {
            return response()->json(['message' => 'Contraseña incorrecta. Por favor, inténtalo de nuevo.'], 403);
        }

        try {
            if ($employee->file_path && Storage::disk('signatures')->exists($employee->file_path)) {
                Storage::disk('signatures')->delete($employee->file_path);
            }

            $employee->delete();
            return response()->json(['message' => 'Empleado eliminado correctamente.']);
        } catch (\Exception $e) {
            Log::error('Error al eliminar empleado: ' . $e->getMessage());
            return response()->json(['message' => 'Error interno al eliminar empleado.'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Función para exportar lista de asistencia.
    |--------------------------------------------------------------------------
    */
    public function export(Request $request)
    {
        $request->validate(['activity_id' => 'required|exists:activities,id']);
        $employees = Employee::all();

        foreach ($employees as $employee) {
            Attendance::create([
                'activity_id' => $request->activity_id,
                'employee_id' => $employee->id,
                'attend' => false,
            ]);
        }

        return response()->json(['message' => 'Exportación realizada correctamente']);
    }

    /*
    |--------------------------------------------------------------------------
    | Endpoint para devolver la firma como dataURL
    |--------------------------------------------------------------------------
    */
    public function showSignature(Request $request)
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        $employee = Employee::findOrFail($data['employee_id']);

        if (!$employee->file_path) {
            return response()->json(['data_url' => null], 200);
        }

        if (!Storage::disk('signatures')->exists($employee->file_path)) {
            return response()->json(['data_url' => null], 200);
        }

        $contents = Storage::disk('signatures')->get($employee->file_path);
        $base64   = base64_encode($contents);
        $mime     = 'image/png';
        $dataUrl  = "data:{$mime};base64,{$base64}";

        return response()->json(['data_url' => $dataUrl], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Guarda la imagen base64 en el disco privado "signatures" y devuelve la ruta relativa.
    |--------------------------------------------------------------------------
    */
    private function storeSignatureBase64(string $dataUrl, string $document): string
    {
        // Acepta solo PNG/JPEG, normaliza a .png si te aseguras que viene PNG
        if (!preg_match('#^data:image/(png|jpeg);base64,#i', $dataUrl)) {
            throw new \RuntimeException('Formato de firma inválido.');
        }

        $dataUrl = preg_replace('#^data:image/\w+;base64,#i', '', $dataUrl);
        $binary  = base64_decode(str_replace(' ', '+', $dataUrl), true);

        if ($binary === false) {
            throw new \RuntimeException('No se pudo decodificar la firma.');
        }

        $fileName = 'firma_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $document) . '_' . time() . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.png';

        Storage::disk('signatures')->put($fileName, $binary);

        return $fileName; // relativo a storage/app/private/signatures
    }

    /*
    |--------------------------------------------------------------------------
    | Mostrar empleado específico.
    |--------------------------------------------------------------------------
    */
    public function show(string $id)
    {
        //
    }

    /*
    |--------------------------------------------------------------------------
    | Formulario de edición.
    |--------------------------------------------------------------------------
    */
    public function edit(string $id)
    {
        //
    }
}
