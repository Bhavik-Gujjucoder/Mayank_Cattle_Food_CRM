import { expect, test } from '@playwright/test';
import { expectAuthAssetsHealthy, expectLaravelLoginPage } from '../helpers/smoke';

/**
 * Local Playwright smoke — public pages against a running local app.
 *
 * Base URL resolution (first match wins):
 *   E2E_LOCAL_URL  →  APP_URL from .env  →  http://127.0.0.1:8000
 *
 * XAMPP:
 *   Ensure APP_URL in .env matches the browser URL, then:
 *   npm run test:e2e:local
 *
 * artisan serve:
 *   php artisan serve --host=127.0.0.1 --port=8000
 *   set E2E_LOCAL_URL=http://127.0.0.1:8000
 *   npm run test:e2e:local
 */

test.describe('local public smoke', () => {
    test('login page renders', async ({ page }) => {
        const response = await page.goto('login');

        await expectLaravelLoginPage(page, response);
    });

    test('static auth assets load', async ({ page }) => {
        await expectAuthAssetsHealthy(page);
    });

    test('guest cannot open protected module', async ({ page }) => {
        await page.goto('permissions');

        await expect(page).toHaveURL(/\/login/);
    });
});
