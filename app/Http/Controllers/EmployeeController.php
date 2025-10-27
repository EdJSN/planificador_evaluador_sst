<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Activity, Audience, Attendance, Control, Employee, Position};
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_employee')->only(['index', 'show', 'dashboard']);
        $this->middleware('permission:create_employee')->only(['store', 'create']);
        $this->middleware('permission:edit_employee')->only(['edit', 'update']);
        $this->middleware('permission:delete_employee')->only(['destroy']);

        // Endpoints auxiliares de lectura:
        $this->middleware('permission:view_employee')->only(['showSignature', 'countByAudiences']);
    }

    /*
    |--------------------------------------------------------------------------
    | Vista principal (dashboard).
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $employees = Employee::with('position')->orderBy('names', 'asc')->get();
        $positions = Position::orderBy('position')->get();

        // Buscar actividades ya usadas en el control activo
        $usedActivities = Attendance::pluck('activity_id')->unique();

        // Mostrar solo actividades válidas y no usadas
        $activities = Activity::whereIn('states', ['P', 'A', 'R'])
            ->whereNotIn('id', $usedActivities)
            ->get();

        return view('employees.dashboard', compact('employees', 'positions', 'activities'));
    }

    /*
    |--------------------------------------------------------------------------
    | Formulario de creación.
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        $positions = Position::pluck('position', 'id');
        $audienceOptions = Audience::pluck('name', 'id'); // para el select multiple

        return view('employees.create', compact('positions', 'audienceOptions'));
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
                'audiences'   => 'required|array|min:1',
                'audiences.*' => 'exists:audiences,id',
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

            $employee->audiences()->sync($validatedData['audiences']);

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
                'audiences'   => 'required|array|min:1',
                'audiences.*' => 'exists:audiences,id',
            ]);

            $employee->fill([
                'names'       => $validatedData['names'],
                'lastname1'   => $validatedData['lastname1'],
                'lastname2'   => $validatedData['lastname2'] ?? null,
                'document'    => $validatedData['document'],
                'position_id' => $validatedData['position_id'],
            ]);

            $employee->audiences()->sync($validatedData['audiences']);

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
        // Deducir el mime por la extensión guardada
        $ext = strtolower(pathinfo($employee->file_path, PATHINFO_EXTENSION));
        $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png';
        $base64  = base64_encode($contents);
        $dataUrl = "data:{$mime};base64,{$base64}";

        return response()->json(['data_url' => $dataUrl], 200);
    }

    /*
    |--------------------------------------------------------------------------
    | Guarda la imagen base64 en el disco privado "signatures" y devuelve la ruta correspondiente.
    |--------------------------------------------------------------------------
    */
    private function storeSignatureBase64(string $dataUrl, string $document): string
    {
        // Detecta el mime real del Data URL
        if (!preg_match('#^data:image/(png|jpeg);base64,#i', $dataUrl, $m)) {
            throw new \RuntimeException('Formato de firma inválido.');
        }
        $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : 'png'; // normaliza jpeg → jpg

        $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $dataUrl);
        $binary = base64_decode(str_replace(' ', '+', $base64), true);
        if ($binary === false) {
            throw new \RuntimeException('No se pudo decodificar la firma.');
        }

        // Sanitiza el documento para usarlo en el nombre
        $safeDoc = preg_replace('/[^A-Za-z0-9_\-]/', '_', $document);
        $fileName = sprintf('firma_%s_%d_%s.%s', $safeDoc, time(), substr(bin2hex(random_bytes(4)), 0, 8), $ext);

        // Asegura que el directorio exista (deploys limpios)
        $fs = Storage::disk('signatures');
        if (method_exists($fs, 'path')) {
            @mkdir($fs->path(''), 0775, true);
        }

        $fs->put($fileName, $binary);

        return $fileName;
    }

    /*
    |--------------------------------------------------------------------------
    | NUEVO: Contar empleados por audiencias seleccionadas
    |--------------------------------------------------------------------------
    */
    public function countByAudiences(Request $request)
    {
        $data = $request->validate([
            'audience_ids'   => 'required|array|min:1',
            'audience_ids.*' => 'integer|exists:audiences,id',
            'mode'           => 'nullable|in:any,all',
        ]);

        $ids  = $data['audience_ids'];
        $mode = $data['mode'] ?? 'any';

        if ($mode === 'all') {
            // Debe tener TODAS las audiencias seleccionadas
            $query = Employee::query();
            foreach ($ids as $audId) {
                $query->whereHas('audiences', function ($q) use ($audId) {
                    $q->where('audiences.id', $audId);
                });
            }
            $count = $query->distinct('employees.id')->count('employees.id');
        } else {
            // 'any' = al menos una
            $count = Employee::whereHas('audiences', function ($q) use ($ids) {
                $q->whereIn('audiences.id', $ids);
            })->distinct('employees.id')->count('employees.id');
        }

        return response()->json([
            'success' => true,
            'count'   => $count,
            'mode'    => $mode,
        ]);
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
