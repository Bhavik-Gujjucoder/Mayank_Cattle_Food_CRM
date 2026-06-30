import { expect, test } from '@playwright/test';
import { loginDealerByPhone, loginEmailWithOtp } from '../helpers/auth';
import { E2E_USERS } from '../helpers/module-registry';
import { expectLaravelLoginPage } from '../helpers/smoke';
import { skipUnlessLocalE2e } from './authenticated-setup';

test.describe('auth flows', () => {
    test('guest is redirected from protected pages to login', async ({ page }) => {
        await page.goto('permissions');

        await expect(page).toHaveURL(/\/login/);
        await expectLaravelLoginPage(page);
    });

    test('guest home redirects to login', async ({ page }) => {
        await page.goto('./');

        await expect(page).toHaveURL(/\/login/);
    });

    test('email login with OTP reaches dashboard', async ({ page }) => {
        skipUnlessLocalE2e();

        await loginEmailWithOtp(page, E2E_USERS.broker);
        await expect(page.locator('#sidebar-menu')).toContainText('Dashboard');
    });

    test('dealer phone login reaches dashboard', async ({ page }) => {
        skipUnlessLocalE2e();

        await loginDealerByPhone(page);
        await expect(page.locator('#sidebar-menu')).toContainText('Dashboard');
    });

    test('forgot password page renders', async ({ page }) => {
        await page.goto('login');
        await page.getByRole('link', { name: /forgot password/i }).click();

        await expect(page).toHaveURL(/forgot-password/);
        await expect(page.locator('input[name="email"]')).toBeVisible();
    });
});
