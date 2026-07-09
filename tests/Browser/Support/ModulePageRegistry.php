<?php

namespace Tests\Browser\Support;

use App\Models\User;

/**
 * Index-page smoke targets aligned with sidebar @canany / @role gates.
 */
class ModulePageRegistry
{
    /**
     * @return list<array{path: string, selector: string, text: string, permissions: list<string>, roles_only?: list<string>, roles_any?: list<string>}>
     */
    public static function definitions(): array
    {
        return [
            [
                'path'        => '/',
                'selector'    => '#sidebar-menu',
                'text'        => 'Dashboard',
                'permissions' => [],
            ],
            [
                'path'        => '/raw-material/category',
                'selector'    => '#raw_material_category_table',
                'text'        => 'Category',
                'permissions' => [
                    'view-raw-material-category', 'export-raw-material-category',
                    'add-raw-material-category', 'edit-raw-material-category', 'delete-raw-material-category',
                ],
            ],
            [
                'path'        => '/raw-material',
                'selector'    => '#raw_material_table',
                'text'        => 'Material',
                'permissions' => [
                    'view-raw-material-inventory', 'export-raw-material-inventory',
                    'add-raw-material-inventory', 'edit-raw-material-inventory', 'delete-raw-material-inventory',
                ],
            ],
            [
                'path'        => '/raw-material/order',
                'selector'    => '#raw_material_order_table',
                'text'        => 'Orders',
                'permissions' => [
                    'view-raw-material-purchas-order', 'export-raw-material-purchas-order',
                    'add-raw-material-purchas-order', 'edit-raw-material-purchas-order', 'delete-raw-material-purchas-order',
                ],
            ],
            [
                'path'        => '/raw-material/receive',
                'selector'    => '#raw_material_receive_table',
                'text'        => 'Received',
                'permissions' => [
                    'view-raw-material-receive', 'export-raw-material-receive',
                    'add-raw-material-receive', 'edit-raw-material-receive', 'delete-raw-material-receive',
                ],
            ],
            [
                'path'        => '/product',
                'selector'    => '#product_table',
                'text'        => 'Product',
                'permissions' => ['add-product', 'edit-product', 'delete-product'],
            ],
            [
                'path'        => '/order',
                'selector'    => '#order_table',
                'text'        => 'Soda',
                'permissions' => ['view-order', 'add-order', 'edit-order', 'delete-order'],
            ],
            [
                'path'        => '/dispatch',
                'selector'    => '#dispatch_table',
                'text'        => 'Dispatch',
                'permissions' => ['view-dispatch', 'add-dispatch', 'edit-dispatch', 'delete-dispatch'],
            ],
            [
                'path'        => '/delivery-pending-payments',
                'selector'    => '#dppFilterForm',
                'text'        => 'Dispatch Pending Payments',
                'permissions' => ['view-dispatch-pending-payments'],
            ],
            [
                'path'        => '/weekly-report',
                'selector'    => '#weekly_report_table',
                'text'        => 'Weekly Report',
                'permissions' => ['view-weekly-report', 'add-weekly-report', 'edit-weekly-report', 'delete-weekly-report'],
            ],
            [
                'path'        => '/oil',
                'selector'    => '.card-body',
                'text'        => 'Comming Soon',
                'permissions' => ['add-oil', 'edit-oil', 'delete-oil'],
            ],
            [
                'path'        => '/machine',
                'selector'    => '.card-body',
                'text'        => 'Comming Soon',
                'permissions' => ['add-machine', 'edit-machine', 'delete-machine'],
            ],
            [
                'path'        => '/brand',
                'selector'    => '#brand_table',
                'text'        => 'Brand',
                'permissions' => ['view-brand', 'add-brand', 'edit-brand', 'delete-brand'],
            ],
            [
                'path'        => '/supplier-broker',
                'selector'    => '#supplier_broker_table',
                'text'        => 'Supplier Broker',
                'permissions' => ['view-supplier-broker', 'add-supplier-broker', 'edit-supplier-broker', 'delete-supplier-broker'],
            ],
            [
                'path'        => '/supplier',
                'selector'    => '#supplier_table',
                'text'        => 'Supplier',
                'permissions' => ['add-supplier', 'edit-supplier', 'delete-supplier'],
            ],
            [
                'path'        => '/users/broker',
                'selector'    => '#users',
                'text'        => 'Broker',
                'permissions' => ['add-broker', 'edit-broker', 'delete-broker'],
            ],
            [
                'path'        => '/dealer',
                'selector'    => '#dealerTable',
                'text'        => 'Dealer',
                'permissions' => ['add-dealer', 'edit-dealer', 'delete-dealer'],
            ],
            [
                'path'        => '/users/transporter',
                'selector'    => '#users',
                'text'        => 'Transporter',
                'permissions' => ['add-transporter', 'edit-transporter', 'delete-transporter'],
            ],
            [
                'path'        => '/truck',
                'selector'    => '#truck_table',
                'text'        => 'Truck',
                'permissions' => ['add-truck', 'edit-truck', 'delete-truck'],
            ],
            [
                'path'        => '/users/user',
                'selector'    => '#users',
                'text'        => 'Admin',
                'permissions' => ['add-user', 'edit-user', 'delete-user'],
            ],
            [
                'path'          => '/permissions',
                'selector'      => '#permission',
                'text'          => 'Permission',
                'permissions'   => [],
                'roles_any'     => ['super admin', 'admin'],
            ],
            [
                'path'          => '/roles',
                'selector'      => '#roles',
                'text'          => 'Permissions',
                'permissions'   => [],
                'roles_any'     => ['super admin', 'admin'],
            ],
            [
                'path'        => '/state',
                'selector'    => '#state_table',
                'text'        => 'State',
                'permissions' => ['add-state', 'edit-state', 'delete-state'],
            ],
            [
                'path'        => '/city',
                'selector'    => '#city_table',
                'text'        => 'City',
                'permissions' => ['add-city', 'edit-city', 'delete-city'],
            ],
            [
                'path'          => '/general-setting/create',
                'selector'      => '#myTab',
                'text'          => 'General Setting',
                'permissions'   => [],
                'roles_any'     => ['super admin', 'admin'],
            ],
            [
                'path'          => '/system/backup',
                'selector'      => '#create-backup-card',
                'text'          => 'Create Backup',
                'permissions'   => [],
                'roles_only'    => ['super admin'],
            ],
        ];
    }

    /**
     * Sales module pages (order + dispatch [+ payment receivable]).
     *
     * @return list<array{path: string, selector: string, text: string, permissions: list<string>}>
     */
    public static function salesDefinitions(): array
    {
        return array_values(array_filter(
            static::definitions(),
            fn (array $page) => in_array($page['path'], ['/order', '/dispatch', '/delivery-pending-payments', '/weekly-report'], true)
        ));
    }

    /**
     * @return list<array{path: string, selector: string, text: string, permissions: list<string>, roles_only?: list<string>, roles_any?: list<string>}>
     */
    public static function forUser(User $user): array
    {
        return array_values(array_filter(
            static::definitions(),
            fn (array $page) => static::userCanAccessPage($user, $page)
        ));
    }

    /**
     * @param  array{path: string, selector: string, text: string, permissions: list<string>, roles_only?: list<string>, roles_any?: list<string>}  $page
     */
    public static function userCanAccessPage(User $user, array $page): bool
    {
        if ($user->hasRole('super admin')) {
            return true;
        }

        if (! empty($page['roles_only'])) {
            return $user->hasAnyRole($page['roles_only']);
        }

        if (! empty($page['roles_any']) && empty($page['permissions'])) {
            return $user->hasAnyRole($page['roles_any']);
        }

        if (empty($page['permissions'])) {
            return empty($page['roles_any']) || $user->hasAnyRole($page['roles_any']);
        }

        if (! empty($page['roles_any']) && $user->hasAnyRole($page['roles_any'])) {
            return true;
        }

        foreach ($page['permissions'] as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
