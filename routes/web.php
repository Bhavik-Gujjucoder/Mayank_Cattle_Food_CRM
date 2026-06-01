<?php

use App\Http\Controllers\CityManagementController;
use App\Http\Controllers\DealerManagementController;
use App\Http\Controllers\DispatchManagementController;
use App\Http\Controllers\GeneralSettingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MachineInventoryController;
use App\Http\Controllers\OilManagementController;
use App\Http\Controllers\OrderManagementController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RawMaterialController;
use App\Http\Controllers\RawMaterialOrderController;
use App\Http\Controllers\RawMaterialReceiveController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StateManagementController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TruckManagementController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});


/* Verify OTP */
Route::get('/verify-otp', fn() => view('auth.verify_otp'))->name('verify.otp.form');
Route::post('/verify-otp', [OtpController::class, 'verify'])->name('verify.otp');
Route::post('/resend-otp', [OtpController::class, 'resendOtp'])->name('resend.otp');


/* Permissions & Roles — super admin / admin only */
Route::middleware(['auth', 'role:super admin|admin'])->group(function () {
    Route::resource('permissions', PermissionController::class);
    Route::resource('roles', RoleController::class);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [HomeController::class, 'dashboard'])->name('dashboard');

    /* ------------------------------------------------------------------ */
    /*  My Profile                                                        */
    /* ------------------------------------------------------------------ */
    Route::get('my-profile/{id}', [UserController::class, 'my_profile'])->name('my_profile');
    Route::put('my-profile/{id}', [UserController::class, 'my_profile_update'])->name('my_profile.update');


    /* ------------------------------------------------------------------ */
    /*  User Management  (type: broker, transporter, admin & staff)       */
    /* ------------------------------------------------------------------ */
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
    Route::get('/get-dealers', [DealerManagementController::class, 'getDealersByBrokerBrand'])->name('get.dealers');


    /* ------------------------------------------------------------------ */
    /*  Order / Soda-Order Management  (type: soda-order)                  */
    /* ------------------------------------------------------------------ */
    Route::get('order-last-price', [OrderManagementController::class, 'lastItemPrice'])->name('order.lastItemPrice');
    /* Sequential-dispatch eligibility check (AJAX) — must precede resource to avoid
       Laravel treating 'dispatch-check' as an {order} segment for the show() route. */
    Route::get('order/{order}/dispatch-check', [OrderManagementController::class, 'checkDispatchEligibility'])
        ->name('order.dispatchCheck');
    /* Delete eligibility check — returns dispatch details if order cannot be deleted */
    Route::get('order/{order}/delete-check', [OrderManagementController::class, 'deleteCheck'])
        ->name('order.deleteCheck');
    Route::resource('order', OrderManagementController::class)->except(['store', 'update', 'destroy']);
    Route::post('order', [OrderManagementController::class, 'store'])
        ->name('order.store')->middleware('permission:add-order');
    Route::match(['put', 'patch'], 'order/{order}', [OrderManagementController::class, 'update'])
        ->name('order.update')->middleware('permission:edit-order');
    Route::delete('order/{order}', [OrderManagementController::class, 'destroy'])
        ->name('order.destroy')->middleware('permission:delete-order');
    Route::post('order-bulk-delete', [OrderManagementController::class, 'bulkDelete'])
        ->name('order.bulkDelete')->middleware('permission:delete-order');


    /* ------------------------------------------------------------------ */
    /*  Dispatch Management  (type: dispatch)                             */
    /* ------------------------------------------------------------------ */
    /* Dispatch history for a specific order — must be BEFORE resource route */
    Route::get('dispatch/order/{order}', [DispatchManagementController::class, 'orderHistory'])
        ->name('dispatch.orderHistory');

    /* AJAX: trucks that belong to a given transporter (for dynamic truck dropdown) */
    Route::get('dispatch/transporter-trucks/{transporter}', [DispatchManagementController::class, 'getTrucksByTransporter'])
        ->name('dispatch.transporterTrucks');

    Route::resource('dispatch', DispatchManagementController::class)->except(['store', 'update', 'destroy']);
    Route::post('dispatch', [DispatchManagementController::class, 'store'])
        ->name('dispatch.store')->middleware('permission:add-dispatch');
    Route::match(['put', 'patch'], 'dispatch/{dispatch}', [DispatchManagementController::class, 'update'])
        ->name('dispatch.update')->middleware('permission:edit-dispatch');
    Route::delete('dispatch/{dispatch}', [DispatchManagementController::class, 'destroy'])
        ->name('dispatch.destroy')->middleware('permission:delete-dispatch');


    /* ------------------------------------------------------------------ */
    /*  Oil Management                                                    */
    /* ------------------------------------------------------------------ */
    Route::resource('oil', OilManagementController::class);


    /* ------------------------------------------------------------------ */
    /*  Machine Inventory                                                 */
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
    /*  City Management  (type: city)                                     */
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
    /*  Raw Material Module                                                 */
    /* ------------------------------------------------------------------ */
    Route::prefix('raw-material')->name('raw-material.')->group(function () {
        /* Material (inventory) */
        Route::get('export', [RawMaterialController::class, 'export'])->name('export');
        Route::patch('{raw_material}/toggle-status', [RawMaterialController::class, 'toggleStatus'])
            ->name('toggleStatus')->middleware('permission:edit-raw-material-inventory');

        /* Orders — register before {raw_material} wildcard routes */
        Route::prefix('order')->name('order.')->group(function () {
            Route::get('export', [RawMaterialOrderController::class, 'export'])->name('export');
            Route::get('export-pdf-list', [RawMaterialOrderController::class, 'exportListPdf'])->name('export-list-pdf');
            Route::get('export-full', [RawMaterialOrderController::class, 'exportFull'])->name('export-full');
            Route::get('export-full-pdf', [RawMaterialOrderController::class, 'exportFullPdf'])->name('export-full-pdf');
            Route::get('{raw_material_order}/export-excel', [RawMaterialOrderController::class, 'exportOrderExcel'])
                ->name('export-order-excel');
            Route::get('{raw_material_order}/export-order-pdf', [RawMaterialOrderController::class, 'exportOrderPdf'])
                ->name('export-order-pdf');
            Route::get('{raw_material_order}/export-pdf', [RawMaterialOrderController::class, 'exportPdf'])
                ->name('exportPdf');
            Route::get('{raw_material_order}/items', [RawMaterialOrderController::class, 'orderItems'])
                ->name('items');
            Route::patch('{raw_material_order}/cancel', [RawMaterialOrderController::class, 'cancel'])
                ->name('cancel')->middleware('permission:edit-raw-material-purchas-order');
            Route::resource('/', RawMaterialOrderController::class)
                ->except(['store', 'update', 'destroy'])
                ->parameters(['' => 'raw_material_order']);
            Route::post('/', [RawMaterialOrderController::class, 'store'])
                ->name('store')->middleware('permission:add-raw-material-purchas-order');
            Route::match(['put', 'patch'], '{raw_material_order}', [RawMaterialOrderController::class, 'update'])
                ->name('update')->middleware('permission:edit-raw-material-purchas-order');
            Route::delete('{raw_material_order}', [RawMaterialOrderController::class, 'destroy'])
                ->name('destroy')->middleware('permission:delete-raw-material-purchas-order');
        });

        /* Received */
        Route::prefix('receive')->name('receive.')->group(function () {
            Route::get('export', [RawMaterialReceiveController::class, 'export'])->name('export');
            Route::patch('{raw_material_receive}/mark-received', [RawMaterialReceiveController::class, 'markReceived'])
                ->name('markReceived')->middleware('permission:edit-raw-material-purchas-order');
            Route::patch('{raw_material_receive}/cancel', [RawMaterialReceiveController::class, 'cancel'])
                ->name('cancel')->middleware('permission:edit-raw-material-purchas-order');
            Route::resource('/', RawMaterialReceiveController::class)
                ->except(['store', 'update', 'destroy'])
                ->parameters(['' => 'raw_material_receive']);
            Route::post('/', [RawMaterialReceiveController::class, 'store'])
                ->name('store')->middleware('permission:add-raw-material-purchas-order');
            Route::match(['put', 'patch'], '{raw_material_receive}', [RawMaterialReceiveController::class, 'update'])
                ->name('update')->middleware('permission:edit-raw-material-purchas-order');
            Route::delete('{raw_material_receive}', [RawMaterialReceiveController::class, 'destroy'])
                ->name('destroy')->middleware('permission:delete-raw-material-purchas-order');
        });

        /* Material CRUD — wildcard routes last */
        Route::get('create', [RawMaterialController::class, 'create'])->name('create');
        Route::get('/', [RawMaterialController::class, 'index'])->name('index');
        Route::post('/', [RawMaterialController::class, 'store'])
            ->name('store')->middleware('permission:add-raw-material-inventory');
        Route::get('{raw_material}/edit', [RawMaterialController::class, 'edit'])->name('edit');
        Route::get('{raw_material}', [RawMaterialController::class, 'show'])->name('show');
        Route::match(['put', 'patch'], '{raw_material}', [RawMaterialController::class, 'update'])
            ->name('update')->middleware('permission:edit-raw-material-inventory');
        Route::delete('{raw_material}', [RawMaterialController::class, 'destroy'])
            ->name('destroy')->middleware('permission:delete-raw-material-inventory');
    });


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
    /*  Truck Management                                                    */
    /* ------------------------------------------------------------------ */
    Route::resource('truck', TruckManagementController::class)->except(['store', 'update', 'destroy']);
    Route::post('truck', [TruckManagementController::class, 'store'])
        ->name('truck.store')->middleware('permission:add-truck');
    Route::match(['put', 'patch'], 'truck/{truck}', [TruckManagementController::class, 'update'])
        ->name('truck.update')->middleware('permission:edit-truck');
    Route::delete('truck/{truck}', [TruckManagementController::class, 'destroy'])
        ->name('truck.destroy')->middleware('permission:delete-truck');
    Route::post('/truck/bulk-delete', [TruckManagementController::class, 'bulkDelete'])
        ->name('truck.bulkDelete')->middleware('permission:delete-truck');



    /* ------------------------------------------------------------------ */
    /*  General Settings                                                  */
    /* ------------------------------------------------------------------ */
    Route::prefix('general-setting')->name('generalsetting')->group(function () {
        Route::get('/create', [GeneralSettingController::class, 'create'])->name('.create');
        Route::post('/store', [GeneralSettingController::class, 'store'])->name('.store');
    });


    /* ------------------------------------------------------------------ */
    /*  Cache Clear                                                       */
    /* ------------------------------------------------------------------ */
    Route::get('/clear', function () {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('config:cache');
        Artisan::call('view:clear');
        Artisan::call('storage:link');
        return "All cache cleared successfully!";
    });
});

require __DIR__ . '/auth.php';
