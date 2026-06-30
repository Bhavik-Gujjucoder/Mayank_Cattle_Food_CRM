import { expect, test } from '@playwright/test';
import { hasProdUrl } from '../helpers/env';
import { expectAuthAssetsHealthy, expectLaravelLoginPage } from '../helpers/smoke';

/**
 * Production Playwright smoke — READ ONLY. No login, no writes, no OTP emails.
 *
 * Required env:
 *   E2E_PROD_URL=https://app.mayankcattlefood.com
 *
 * Run (from CI or your machine, external to the prod server):
 *   npm run test:e2e:prod
 */

test.beforeEach(() => {
    test.skip(!hasProdUrl(), 'Set E2E_PROD_URL to run production Playwright tests.');
});

test.describe('production public smoke', () => {
    test('login page responds without server error', async ({ page }) => {
        const response = await page.goto('login');

        await expectLaravelLoginPage(page, response);
    });

    test('auth assets are not 404', async ({ page }) => {
        await expectAuthAssetsHealthy(page);
    });

    test('guest cannot open protected module', async ({ page }) => {
        await page.goto('permissions');

        await expect(page).toHaveURL(/\/login/);
    });

    test('no console errors on login page', async ({ page }) => {
        const errors: string[] = [];

        page.on('pageerror', (error) => {
            errors.push(error.message);
        });

        await page.goto('login');
        await page.waitForLoadState('networkidle');

        await expectLaravelLoginPage(page);
        expect(errors, `JS errors:\n${errors.join('\n')}`).toEqual([]);
    });
});
