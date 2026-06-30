import { test } from '@playwright/test';
import { loginAs } from '../helpers/auth';
import { E2E_USERS, pagesForRole } from '../helpers/module-registry';
import { assertDeniedPage, smokeModulePages } from '../helpers/page-smoke';
import { skipUnlessLocalE2e } from './authenticated-setup';

test.describe('admin modules', () => {
    test.describe.configure({ timeout: 180_000 });

    test.beforeEach(() => {
        skipUnlessLocalE2e();
    });

    test('can open permitted module index pages', async ({ page }) => {
        await loginAs(page, E2E_USERS.admin);
        await smokeModulePages(page, pagesForRole('admin'));
    });

    test('cannot open super-admin-only system backup', async ({ page }) => {
        await loginAs(page, E2E_USERS.admin);
        await assertDeniedPage(page, '/system/backup', '#create-backup-card');
    });
});
