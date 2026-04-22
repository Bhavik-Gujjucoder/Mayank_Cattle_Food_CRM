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
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RawMaterialController;
use App\Http\Controllers\RawMaterialPurchaseController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StateManagementController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

/* Permissions & Roles — super admin / admin only */
Route::middleware(['auth', 'role:super admin|admin'])->group(function () {
    Route::resource('permissions', PermissionController::class);
    Route::resource('roles', RoleController::class);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [HomeController::class, 'dashboard'])->name('dashboard');

    /* My Profile */
    Route::get('my-profile/{id}', [UserController::class, 'my_profile'])->name('my_profile');
    Route::put('my-profile/{id}', [UserController::class, 'my_profile_update'])->name('my_profile.update');


    Route::get('users/{type}', [UserController::class, 'index'])->name('users.index');
    Route::get('users/{type}/create', [UserController::class, 'create'])->name('users.create');
    Route::get('users/{type}/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::get('users/{id}/show', [UserController::class, 'show'])->name('users.show');
    Route::post('users/{type}', [UserController::class, 'store'])->name('users.store');
    Route::put('users/{type}/{id}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{type}/{id}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/user/bulk-delete/{type}', [UserController::class, 'bulkDelete'])->name('user.bulkDelete');


    /* ------------------------------------------------------------------ */
    /*  Dealer Management  (type: dealer)                                   */
    /* ------------------------------------------------------------------ */
    Route::resource('dealer', DealerManagementController::class)->except(['store', 'update', 'destroy']);
    Route::post('dealer', [DealerManagementController::class, 'store'])
        ->name('dealer.store')->middleware('permission:add-dealer');
    Route::match(['put', 'patch'], 'dealer/{dealer}', [DealerManagementController::class, 'update'])
        ->name('dealer.update')->middleware('permission:edit-dealer');
    Route::delete('dealer/{dealer}', [DealerManagementController::class, 'destroy'])
        ->name('dealer.destroy')->middleware('permission:delete-dealer');
    Route::get('/dealer/export', [DealerManagementController::class, 'export'])
        ->name('dealer.export')->middleware('permission:export-dealer');
    Route::post('/get-cities', [DealerManagementController::class, 'getCitiesByState'])->name('get.cities');


    /* ------------------------------------------------------------------ */
    /*  Order / Soda-Order Management  (type: soda-order)                  */
    /* ------------------------------------------------------------------ */
    Route::resource('order', OrderManagementController::class)->except(['store', 'update', 'destroy']);
    Route::post('order', [OrderManagementController::class, 'store'])
        ->name('order.store')->middleware('permission:add-order');
    Route::match(['put', 'patch'], 'order/{order}', [OrderManagementController::class, 'update'])
        ->name('order.update')->middleware('permission:edit-order');
    Route::delete('order/{order}', [OrderManagementController::class, 'destroy'])
        ->name('order.destroy')->middleware('permission:delete-order');


    /* ------------------------------------------------------------------ */
    /*  Dispatch Management  (type: dispatch)                               */
    /* ------------------------------------------------------------------ */
    Route::resource('dispatch', DispatchManagementController::class)->except(['store', 'update', 'destroy']);
    Route::post('dispatch', [DispatchManagementController::class, 'store'])
        ->name('dispatch.store')->middleware('permission:add-dispatch');
    Route::match(['put', 'patch'], 'dispatch/{dispatch}', [DispatchManagementController::class, 'update'])
        ->name('dispatch.update')->middleware('permission:edit-dispatch');
    Route::delete('dispatch/{dispatch}', [DispatchManagementController::class, 'destroy'])
        ->name('dispatch.destroy')->middleware('permission:delete-dispatch');

    /* ------------------------------------------------------------------ */
    /*  Oil Management                                                   */
    /* ------------------------------------------------------------------ */
    Route::resource('oil', OilManagementController::class);


    /* ------------------------------------------------------------------ */
    /*  Machine Inventory                                                */
    /* ------------------------------------------------------------------ */
    Route::resource('machine', MachineInventoryController::class);


    /* ------------------------------------------------------------------ */
    /*  State Management  (type: state)                                     */
    /* ------------------------------------------------------------------ */
    Route::resource('state', StateManagementController::class)->except(['store', 'update', 'destroy']);
    Route::post('state', [StateManagementController::class, 'store'])
        ->name('state.store')->middleware('permission:add-state');
    Route::match(['put', 'patch'], 'state/{state}', [StateManagementController::class, 'update'])
        ->name('state.update')->middleware('permission:edit-state');
    Route::delete('state/{state}', [StateManagementController::class, 'destroy'])
        ->name('state.destroy')->middleware('permission:delete-state');
    Route::post('/state/bulk-delete', [StateManagementController::class, 'bulkDelete'])
        ->name('state.bulkDelete')->middleware('permission:delete-state');


    /* ------------------------------------------------------------------ */
    /*  City Management  (type: city)                                       */
    /* ------------------------------------------------------------------ */
    Route::resource('city', CityManagementController::class)->except(['store', 'update', 'destroy']);
    Route::post('city', [CityManagementController::class, 'store'])
        ->name('city.store')->middleware('permission:add-city');
    Route::match(['put', 'patch'], 'city/{city}', [CityManagementController::class, 'update'])
        ->name('city.update')->middleware('permission:edit-city');
    Route::delete('city/{city}', [CityManagementController::class, 'destroy'])
        ->name('city.destroy')->middleware('permission:delete-city');
    Route::post('/city/bulk-delete', [CityManagementController::class, 'bulkDelete'])
        ->name('city.bulkDelete')->middleware('permission:delete-city');


    /* ------------------------------------------------------------------ */
    /*  Supplier Management  (type: supplier)                               */
    /* ------------------------------------------------------------------ */
    Route::resource('supplier', SupplierController::class)->except(['store', 'update', 'destroy']);
    Route::post('supplier', [SupplierController::class, 'store'])
        ->name('supplier.store')->middleware('permission:add-supplier');
    Route::match(['put', 'patch'], 'supplier/{supplier}', [SupplierController::class, 'update'])
        ->name('supplier.update')->middleware('permission:edit-supplier');
    Route::delete('supplier/{supplier}', [SupplierController::class, 'destroy'])
        ->name('supplier.destroy')->middleware('permission:delete-supplier');
    Route::post('/supplier/bulk-delete', [SupplierController::class, 'bulkDelete'])
        ->name('supplier.bulkDelete')->middleware('permission:delete-supplier');


    /* ------------------------------------------------------------------ */
    /*  Raw Material Inventory  (type: raw-material-inventory)             */
    /* ------------------------------------------------------------------ */
    Route::resource('raw-material', RawMaterialController::class)->except(['store', 'update', 'destroy']);
    Route::post('raw-material', [RawMaterialController::class, 'store'])
        ->name('raw-material.store')->middleware('permission:add-raw-material-inventory');
    Route::match(['put', 'patch'], 'raw-material/{raw_material}', [RawMaterialController::class, 'update'])
        ->name('raw-material.update')->middleware('permission:edit-raw-material-inventory');
    Route::delete('raw-material/{raw_material}', [RawMaterialController::class, 'destroy'])
        ->name('raw-material.destroy')->middleware('permission:delete-raw-material-inventory');
    Route::post('/raw-material/bulk-delete', [RawMaterialController::class, 'bulkDelete'])
        ->name('raw-material.bulkDelete')->middleware('permission:delete-raw-material-inventory');


    /* ------------------------------------------------------------------ */
    /*  Product Management  (type: product)                                 */
    /* ------------------------------------------------------------------ */
    Route::resource('product', ProductController::class)->except(['store', 'update', 'destroy']);
    Route::post('product', [ProductController::class, 'store'])
        ->name('product.store')->middleware('permission:add-product');
    Route::match(['put', 'patch'], 'product/{product}', [ProductController::class, 'update'])
        ->name('product.update')->middleware('permission:edit-product');
    Route::delete('product/{product}', [ProductController::class, 'destroy'])
        ->name('product.destroy')->middleware('permission:delete-product');
    Route::post('/product/bulk-delete', [ProductController::class, 'bulkDelete'])
        ->name('product.bulkDelete')->middleware('permission:delete-product');


    /* ------------------------------------------------------------------ */
    /*  Raw Material Purchase Order  (type: raw-material-purchas-order)    */
    /* ------------------------------------------------------------------ */
    Route::resource('raw-material-order', RawMaterialPurchaseController::class)->except(['store', 'update', 'destroy']);
    Route::post('raw-material-order', [RawMaterialPurchaseController::class, 'store'])
        ->name('raw-material-order.store')->middleware('permission:add-raw-material-purchas-order');
    Route::match(['put', 'patch'], 'raw-material-order/{raw_material_order}', [RawMaterialPurchaseController::class, 'update'])
        ->name('raw-material-order.update')->middleware('permission:edit-raw-material-purchas-order');
    Route::delete('raw-material-order/{raw_material_order}', [RawMaterialPurchaseController::class, 'destroy'])
        ->name('raw-material-order.destroy')->middleware('permission:delete-raw-material-purchas-order');


    /* ------------------------------------------------------------------ */
    /*  General Settings                                                  */
    /* ------------------------------------------------------------------ */
    Route::prefix('general-setting')->name('generalsetting')->group(function () {
        Route::get('/create', [GeneralSettingController::class, 'create'])->name('.create');
        Route::post('/store', [GeneralSettingController::class, 'store'])->name('.store');
    });
});

require __DIR__ . '/auth.php';
