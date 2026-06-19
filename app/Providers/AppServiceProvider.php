<?php

namespace App\Providers;

use App\Models\DispatchManagement;
use App\Models\RawMaterialOrderItem;
use App\Models\RawMaterialReceive;
use App\Observers\DispatchManagementObserver;
use App\Observers\RawMaterialOrderItemObserver;
use App\Observers\RawMaterialReceiveObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super admin')  ? true : null;
        });

        RawMaterialOrderItem::observe(RawMaterialOrderItemObserver::class);
        RawMaterialReceive::observe(RawMaterialReceiveObserver::class);
        DispatchManagement::observe(DispatchManagementObserver::class);
    }
}
