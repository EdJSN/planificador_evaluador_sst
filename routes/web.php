<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{CheckController, EmployeeController, HomeController, PlannerController, SettingsController};
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

    /*
    |--------------------------------------------------------------------------
    | Sección Planificador
    |--------------------------------------------------------------------------
    */
    Route::get('/planner', [PlannerController::class, 'dashboard'])
        ->middleware('permission:view_activity')
        ->name('planner.dashboard');

    Route::post('/planner', [PlannerController::class, 'store'])
        ->middleware('permission:create_activity')
        ->name('planner.store');

    Route::put('/planner/{activity}', [PlannerController::class, 'update'])
        ->middleware('permission:edit_activity')
        ->name('planner.update');

    Route::patch('/planner/{activity}', [PlannerController::class, 'update'])
        ->middleware('permission:edit_activity');

    Route::get('/planner/{activity}', [PlannerController::class, 'show'])
        ->middleware('permission:view_activity')
        ->name('planner.show');

    Route::get('/planner/{activity}/edit', [PlannerController::class, 'edit'])
        ->middleware('permission:edit_activity')
        ->name('planner.edit');

    Route::delete('/planner/{activity}', [PlannerController::class, 'destroy'])
        ->middleware('permission:delete_activity')
        ->name('planner.destroy');

    Route::post('/planner/export', [PlannerController::class, 'export'])
        ->middleware('permission:export_activity')
        ->name('planner.export');

    /*
    |--------------------------------------------------------------------------
    | Sección Empleados 
    |--------------------------------------------------------------------------
    */
    Route::resource('employees', EmployeeController::class);

    Route::post('/employees/signature/show', [EmployeeController::class, 'showSignature'])
        ->middleware('permission:view_employee')
        ->name('employees.signature.show');

    Route::post('/employees/count-by-audiences', [EmployeeController::class, 'countByAudiences'])
        ->middleware('permission:view_employee')
        ->name('employees.countByAudiences');

    /*
    |--------------------------------------------------------------------------
    | Sección Controles (Check)
    |--------------------------------------------------------------------------
    */
    Route::post('/check/print-attendees', [CheckController::class, 'printAttendees'])
        ->middleware('permission:print_attendees')
        ->name('check.print.attendees');

    Route::post('/check/prepare', [CheckController::class, 'prepare'])
        ->middleware('permission:manage_attendance')
        ->name('check.prepare');

    Route::post('/check/attendance/update', [CheckController::class, 'updateAttendance'])
        ->middleware('permission:manage_attendance')
        ->name('check.attendance.update');

    Route::post('/check/attendance/bulk-update', [CheckController::class, 'bulkUpdateAttendance'])
        ->middleware('permission:manage_attendance')
        ->name('check.attendance.bulkUpdate');

    Route::post('/check/attendance/finalize', [CheckController::class, 'finalize'])
        ->middleware('permission:manage_attendance')
        ->name('attendance.finalize');

    Route::post('/check/search', [CheckController::class, 'searchActivities'])
        ->middleware('permission:view_control')
        ->name('activities.search');

    Route::resource('check', CheckController::class);

    Route::post('/check/activities/{activity}/unlink', [CheckController::class, 'unlinkFromControl'])
        ->middleware('permission:unlink_activity_from_control')
        ->name('check.activities.unlink');

    Route::get('/check/facilitator-signature', [CheckController::class, 'facilitatorSignature'])
        ->middleware('permission:view_control')
        ->name('check.facilitator.signature');

    /*
    |--------------------------------------------------------------------------
    | Sección Settings (SOLO ADMIN)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/settings', [SettingsController::class, 'dashboard'])
            ->middleware('permission:view_settings')
            ->name('settings.dashboard');

        Route::post('/settings/users', [SettingsController::class, 'storeUser'])
            ->middleware('permission:create_user')
            ->name('settings.users.store');

        Route::get('/settings/users', [SettingsController::class, 'usersIndex'])
            ->middleware('permission:view_settings') 
            ->name('settings.users.index');

        Route::put('/settings/users/{user}', [SettingsController::class, 'updateUser'])
            ->middleware('permission:edit_user')
            ->name('settings.users.update');

        Route::delete('/settings/users/{user}', [SettingsController::class, 'destroyUser'])
            ->middleware('permission:delete_user')
            ->name('settings.users.destroy');
    });

    // Ruta /home 
    Route::get('/home', [HomeController::class, 'index'])->name('home');
});

/*
|--------------------------------------------------------------------------
| Rutas públicas: autenticación y restablecimiento
|--------------------------------------------------------------------------
*/

Auth::routes();
