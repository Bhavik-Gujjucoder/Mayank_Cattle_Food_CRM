import { expect, test } from '@playwright/test';
import { loginAs } from '../helpers/auth';
import {
    assertAuthenticatedPageHealth,
    assertLoginPageHealth,
    assertMobileViewport,
    trackBrowserHealth,
    assertBrowserHealth,
} from '../helpers/browser-health';
import { E2E_USERS } from '../helpers/module-registry';
import { skipUnlessLocalE2e } from './authenticated-setup';

test.describe('non-functional browser checks', () => {
    test('login page passes asset and console checks', async ({ page }) => {
        await assertLoginPageHealth(page);
    });

    test('login page renders on mobile viewport', async ({ page }) => {
        await assertMobileViewport(page, 'login');
    });

    test('authenticated dashboard passes health checks', async ({ page }) => {
        skipUnlessLocalE2e();

        await loginAs(page, E2E_USERS.superAdmin);
        await assertAuthenticatedPageHealth(page, './', 'dashboard');
    });

    test('key modules have no console errors or broken assets', async ({ page }) => {
        skipUnlessLocalE2e();

        await loginAs(page, E2E_USERS.superAdmin);

        for (const path of ['brand', 'order', 'dispatch', 'permissions']) {
            const report = trackBrowserHealth(page);
            const response = await page.goto(path);

            await page.waitForLoadState('networkidle');
            expect(response?.status(), `${path} status`).toBeLessThan(500);
            assertBrowserHealth(report, path);
        }
    });

    test('guest protected route responds quickly', async ({ page }) => {
        const started = Date.now();
        await page.goto('permissions');
        const elapsed = Date.now() - started;

        await expect(page).toHaveURL(/\/login/);
        expect(elapsed, 'redirect should complete within 15s').toBeLessThan(15_000);
    });
});
