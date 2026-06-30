import { appPath } from './paths';

export type ModulePage = {
    path: string;
    selector: string;
    text: string;
    permissions: string[];
    rolesOnly?: string[];
    rolesAny?: string[];
};

/** Mirrors Tests\Browser\Support\ModulePageRegistry */
export const MODULE_PAGES: ModulePage[] = [
    { path: '/', selector: '#sidebar-menu', text: 'Dashboard', permissions: [] },
    {
        path: '/raw-material/category',
        selector: '#raw_material_category_table',
        text: 'Category',
        permissions: [
            'view-raw-material-category', 'export-raw-material-category',
            'add-raw-material-category', 'edit-raw-material-category', 'delete-raw-material-category',
        ],
    },
    {
        path: '/raw-material',
        selector: '#raw_material_table',
        text: 'Material',
        permissions: [
            'view-raw-material-inventory', 'export-raw-material-inventory',
            'add-raw-material-inventory', 'edit-raw-material-inventory', 'delete-raw-material-inventory',
        ],
    },
    {
        path: '/raw-material/order',
        selector: '#raw_material_order_table',
        text: 'Raw Material — Orders',
        permissions: [
            'view-raw-material-purchas-order', 'export-raw-material-purchas-order',
            'add-raw-material-purchas-order', 'edit-raw-material-purchas-order', 'delete-raw-material-purchas-order',
        ],
    },
    {
        path: '/raw-material/receive',
        selector: '#raw_material_receive_table',
        text: 'Received',
        permissions: [
            'view-raw-material-receive', 'export-raw-material-receive',
            'add-raw-material-receive', 'edit-raw-material-receive', 'delete-raw-material-receive',
        ],
    },
    {
        path: '/product',
        selector: '#product_table',
        text: 'Product',
        permissions: ['add-product', 'edit-product', 'delete-product'],
    },
    {
        path: '/order',
        selector: '#order_table',
        text: 'Soda/Order Management',
        permissions: ['view-order', 'add-order', 'edit-order', 'delete-order'],
    },
    {
        path: '/dispatch',
        selector: '#dispatch_table',
        text: 'Dispatch Management',
        permissions: ['view-dispatch', 'add-dispatch', 'edit-dispatch', 'delete-dispatch'],
    },
    {
        path: '/delivery-pending-payments',
        selector: '#dppFilterForm',
        text: 'Dispatch Pending Payments',
        permissions: ['view-dispatch-pending-payments'],
    },
    {
        path: '/oil',
        selector: '.card-body',
        text: 'Comming Soon',
        permissions: ['add-oil', 'edit-oil', 'delete-oil'],
    },
    {
        path: '/machine',
        selector: '.card-body',
        text: 'Comming Soon',
        permissions: ['add-machine', 'edit-machine', 'delete-machine'],
    },
    {
        path: '/brand',
        selector: '#brand_table',
        text: 'Brand',
        permissions: ['view-brand', 'add-brand', 'edit-brand', 'delete-brand'],
    },
    {
        path: '/supplier-broker',
        selector: '#supplier_broker_table',
        text: 'Supplier Broker',
        permissions: ['view-supplier-broker', 'add-supplier-broker', 'edit-supplier-broker', 'delete-supplier-broker'],
    },
    {
        path: '/supplier',
        selector: '#supplier_table',
        text: 'Supplier',
        permissions: ['add-supplier', 'edit-supplier', 'delete-supplier'],
    },
    {
        path: '/users/broker',
        selector: '#users',
        text: 'Broker',
        permissions: ['add-broker', 'edit-broker', 'delete-broker'],
    },
    {
        path: '/dealer',
        selector: '#dealerTable',
        text: 'Dealer',
        permissions: ['add-dealer', 'edit-dealer', 'delete-dealer'],
    },
    {
        path: '/users/transporter',
        selector: '#users',
        text: 'Transporter',
        permissions: ['add-transporter', 'edit-transporter', 'delete-transporter'],
    },
    {
        path: '/truck',
        selector: '#truck_table',
        text: 'Truck',
        permissions: ['add-truck', 'edit-truck', 'delete-truck'],
    },
    {
        path: '/users/user',
        selector: '#users',
        text: 'Admin',
        permissions: ['add-user', 'edit-user', 'delete-user'],
    },
    {
        path: '/permissions',
        selector: '#permission',
        text: 'Permission',
        permissions: [],
        rolesAny: ['super admin', 'admin'],
    },
    {
        path: '/roles',
        selector: '#roles',
        text: 'Permissions',
        permissions: [],
        rolesAny: ['super admin', 'admin'],
    },
    {
        path: '/state',
        selector: '#state_table',
        text: 'State',
        permissions: ['add-state', 'edit-state', 'delete-state'],
    },
    {
        path: '/city',
        selector: '#city_table',
        text: 'City',
        permissions: ['add-city', 'edit-city', 'delete-city'],
    },
    {
        path: '/general-setting/create',
        selector: '#myTab',
        text: 'General Setting',
        permissions: [],
        rolesAny: ['super admin', 'admin'],
    },
    {
        path: '/system/backup',
        selector: '#create-backup-card',
        text: 'Create Backup',
        permissions: [],
        rolesOnly: ['super admin'],
    },
];

export type E2eRole = 'super admin' | 'admin' | 'staff' | 'broker' | 'dealer';

export const E2E_USERS = {
    superAdmin: 'e2e-superadmin@mayank.local',
    admin: 'e2e-admin@mayank.local',
    staff: 'e2e-staff@mayank.local',
    broker: 'e2e-broker@mayank.local',
    dealerPhone: '9876598765',
    password: 'password',
} as const;

export function salesPages(): ModulePage[] {
    return MODULE_PAGES.filter((page) =>
        ['/order', '/dispatch', '/delivery-pending-payments'].includes(page.path),
    );
}

export function pagesForRole(role: E2eRole): ModulePage[] {
    return MODULE_PAGES.filter((page) => roleCanAccessPage(role, page));
}

function roleCanAccessPage(role: E2eRole, page: ModulePage): boolean {
    if (role === 'super admin') {
        return true;
    }

    if (page.rolesOnly?.length) {
        return page.rolesOnly.includes(role);
    }

    if (page.rolesAny?.length && !page.permissions.length) {
        return page.rolesAny.includes(role);
    }

    if (role === 'admin') {
        if (page.rolesOnly?.includes('super admin') && !page.rolesAny?.includes('admin')) {
            return false;
        }

        if (page.rolesAny?.includes('admin')) {
            return true;
        }

        return page.permissions.length > 0 || page.path === '/';
    }

    if (role === 'staff') {
        const staffPaths = ['/', '/order', '/dispatch', '/delivery-pending-payments'];

        return staffPaths.includes(page.path);
    }

    if (role === 'broker' || role === 'dealer') {
        return salesPages()
            .filter((p) => p.path !== '/delivery-pending-payments')
            .some((p) => p.path === page.path);
    }

    return false;
}

export function resolvedPath(path: string): string {
    return path === '/' ? './' : appPath(path);
}
