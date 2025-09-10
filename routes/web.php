<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{CheckController, EmployeeController, HomeController, PlannerController};
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Rutas protegidas con middleware 'auth'
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {
    // Vista principal autenticada
    Route::get('/', function () {
        return view('home');
    });

    // Sección Planificador
    Route::get('/planner', [PlannerController::class, 'dashboard'])->name('planner.dashboard');
    Route::post('/planner', [PlannerController::class, 'store'])->name('planner.store');
    Route::put('/planner/{activity}', [PlannerController::class, 'update'])->name('planner.update');
    Route::patch('/planner/{activity}', [PlannerController::class, 'update']);
    Route::get('/planner/{activity}', [PlannerController::class, 'show'])->name('planner.show');
    Route::get('/planner/{activity}/edit', [PlannerController::class, 'edit'])->name('planner.edit');
    Route::delete('/planner/{activity}', [PlannerController::class, 'destroy'])->name('planner.destroy');
    Route::post('/planner/export', [PlannerController::class, 'export'])->name('planner.export');

    // Sección Empleados 
    Route::resource('employees', EmployeeController::class);
    Route::post('/employees/signature/show', [EmployeeController::class, 'showSignature'])->name('employees.signature.show');

    // Sección Controles
    Route::post('/check/prepare', [CheckController::class, 'prepare'])->name('check.prepare');
    Route::post('/check/attendance/update', [CheckController::class, 'updateAttendance'])->name('check.attendance.update');
    Route::post('/check/attendance/bulk-update', [CheckController::class, 'bulkUpdateAttendance'])->name('check.attendance.bulkUpdate');
    Route::post('/check/attendance/finalize', [CheckController::class, 'finalize'])->name('attendance.finalize');
    Route::post('/check/search', [CheckController::class, 'searchActivities'])->name('activities.search');
    Route::post('/check/print-attendees', [CheckController::class, 'printAttendees'])->name('check.print.attendees');
    Route::resource('check', CheckController::class);
    
    // Ruta /home 
    Route::get('/home', [HomeController::class, 'index'])->name('home');
});

/*
|--------------------------------------------------------------------------
| Rutas públicas: autenticación y restablecimiento
|--------------------------------------------------------------------------
*/

Auth::routes();
