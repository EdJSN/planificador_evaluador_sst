<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PlannerController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\CheckController;
use App\Http\Controllers\HomeController;
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

    // Sección Empleados 
    Route::resource('employees', EmployeeController::class);
    Route::post('/employees/export', [EmployeeController::class, 'export'])->name('employees.export');
    Route::post('/employees/signature/show', [EmployeeController::class, 'showSignature'])->name('employees.signature.show');

    // Sección Controles
    Route::resource('check', CheckController::class);
    Route::post('/check/export', [CheckController::class, 'export'])->name('check.export');


    // Ruta /home 
    Route::get('/home', [HomeController::class, 'index'])->name('home');
});

/*
|--------------------------------------------------------------------------
| Rutas públicas: autenticación y restablecimiento
|--------------------------------------------------------------------------
*/

Auth::routes();
