<?php

use App\Http\Controllers\CityManagementController;
use App\Http\Controllers\DealerManagementController;
use App\Http\Controllers\DispatchManagementController;
use App\Http\Controllers\GeneralSettingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MachineInventoryController;
use App\Http\Controllers\OilManagementController;
use App\Http\Controllers\OrderManagementController;
use App\Http\Controllers\PermissionController;
// use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RawMaterialController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StateManagementController;
use App\Http\Controllers\SupplierController;
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


// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

/* Permissions */
Route::middleware(['auth', 'role:super admin|admin'])->group(function () {
    Route::resource('permissions', PermissionController::class);
    Route::resource('roles', RoleController::class);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [HomeController::class, 'dashboard'])->name('dashboard');

    /* My Profile */
    Route::get('my-profile/{id}', [UserController::class, 'my_profile'])->name('my_profile');
    Route::PUT('my-profile/{id}', [UserController::class, 'my_profile_update'])->name('my_profile.update');

    /* Users Management */
    Route::get('users/{type}', [UserController::class, 'index'])->name('users.index');
    Route::get('users/{type}/create', [UserController::class, 'create'])->name('users.create');
    Route::delete('users/{type}/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('users/{type}', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{type}/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::get('users/{id}/show', [UserController::class, 'show'])->name('users.show');
    Route::put('users/{type}/{id}', [UserController::class, 'update'])->name('users.update');
    Route::post('/user/bulk-delete/{type}', [UserController::class, 'bulkDelete'])->name('user.bulkDelete');

    /* Dealer Management */
    Route::resource('dealer', DealerManagementController::class);
    Route::get('/dealer/export', [DealerManagementController::class, 'export'])->name('dealer.export');
    Route::post('/get-cities', [DealerManagementController::class, 'getCitiesByState'])->name('get.cities');

    /* Order Management */
    Route::resource('order', OrderManagementController::class);

     /* Dispatch Management */
    Route::resource('dispatch', DispatchManagementController::class);

     /* Oil Management */
    Route::resource('oil', OilManagementController::class);

    /* Machine Inventory */
    Route::resource('machine', MachineInventoryController::class);

    /* States Management */
    Route::resource('state', StateManagementController::class);
    Route::post('/state/bulk-delete', [StateManagementController::class, 'bulkDelete'])->name('state.bulkDelete');

    /* City Management */
    Route::resource('city', CityManagementController::class);
    Route::post('/city/bulk-delete', [CityManagementController::class, 'bulkDelete'])->name('city.bulkDelete');

    /* Raw Material Management */
    Route::resource('raw-material', RawMaterialController::class);
    Route::post('/raw-material/bulk-delete', [RawMaterialController::class, 'bulkDelete'])->name('raw-material.bulkDelete');

    /* Supplier Management */
    Route::resource('supplier', SupplierController::class);
    Route::post('/supplier/bulk-delete', [SupplierController::class, 'bulkDelete'])->name('supplier.bulkDelete');

    /* General Settings */
    Route::prefix('general-setting')->name('generalsetting')->group(function () {
        Route::get('/create', [GeneralSettingController::class, 'create'])->name('.create');
        Route::post('/store', [GeneralSettingController::class, 'store'])->name('.store');
    });
});

require __DIR__ . '/auth.php';
