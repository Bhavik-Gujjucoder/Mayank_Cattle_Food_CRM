<?php

use App\Http\Controllers\GeneralSettingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Auth::routes();

Route::get('/', function () {
    return view('auth.login');
});

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'role:super admin|admin'])->group(function () {
    Route::resource('permissions', PermissionController::class);
    Route::resource('roles', RoleController::class);
});


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [HomeController::class, 'dashboard'])->name('dashboard');

    /* Route::resource('users', UserController::class); */
    Route::get('users/{type}', [UserController::class, 'index'])->name('users.index');
    Route::get('users/{type}/create', [UserController::class, 'create'])->name('users.create');
    Route::delete('users/{type}/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('users/{type}', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{type}/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::get('users/{id}/show', [UserController::class, 'show'])->name('users.show');
    Route::put('users/{type}/{id}', [UserController::class, 'update'])->name('users.update');
    Route::post('/user/bulk-delete/{type}', [UserController::class, 'bulkDelete'])->name('user.bulkDelete');


    Route::prefix('general-setting')->name('generalsetting')->group(function () {
        Route::get('/create', [GeneralSettingController::class, 'create'])->name('.create');
        Route::post('/store', [GeneralSettingController::class, 'store'])->name('.store');
    });


});



require __DIR__.'/auth.php';
