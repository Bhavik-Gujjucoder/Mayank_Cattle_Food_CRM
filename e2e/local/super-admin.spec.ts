import { test } from '@playwright/test';
import { loginAs } from '../helpers/auth';
import { E2E_USERS, pagesForRole } from '../helpers/module-registry';
import { smokeModulePages } from '../helpers/page-smoke';
import { skipUnlessLocalE2e } from './authenticated-setup';

test.describe('super admin modules', () => {
    test.describe.configure({ timeout: 180_000 });

    test.beforeEach(() => {
        skipUnlessLocalE2e();
    });

    test('can open every module index without server errors', async ({ page }) => {
        await loginAs(page, E2E_USERS.superAdmin);
        await smokeModulePages(page, pagesForRole('super admin'));
    });
});
