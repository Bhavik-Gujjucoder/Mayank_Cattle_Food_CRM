import { test } from '@playwright/test';
import { loginAs } from '../helpers/auth';
import { E2E_USERS, pagesForRole } from '../helpers/module-registry';
import { assertDeniedPage, smokeModulePages } from '../helpers/page-smoke';
import { skipUnlessLocalE2e } from './authenticated-setup';

test.describe('staff modules', () => {
    test.beforeEach(() => {
        skipUnlessLocalE2e();
    });

    test('with sales permissions can open sales module pages', async ({ page }) => {
        await loginAs(page, E2E_USERS.staff);
        await smokeModulePages(page, pagesForRole('staff'));
    });

    test('cannot open permissions management', async ({ page }) => {
        await loginAs(page, E2E_USERS.staff);
        await assertDeniedPage(page, '/permissions', '#permission');
    });
});
