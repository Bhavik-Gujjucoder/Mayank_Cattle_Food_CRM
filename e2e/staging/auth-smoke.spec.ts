import { expect, test } from '@playwright/test';
import { hasStagingUrl, stagingCredentials, stagingOtp } from '../helpers/env';

/**
 * Staging Playwright tests — safe flows on a staging mirror of production.
 *
 * Required env:
 *   E2E_STAGING_URL=https://staging.example.com
 *
 * Optional (for auth flows):
 *   E2E_STAGING_EMAIL
 *   E2E_STAGING_PASSWORD
 *   E2E_STAGING_OTP   — six-digit OTP from staging mail catcher / manual step
 *
 * Run:
 *   npm run test:e2e:staging
 */

test.beforeEach(() => {
    test.skip(!hasStagingUrl(), 'Set E2E_STAGING_URL to run staging Playwright tests.');
});

test.describe('staging public smoke', () => {
    test('login page is healthy', async ({ page }) => {
        const response = await page.goto('login');

        expect(response?.status()).toBeLessThan(500);
        await expect(page.getByRole('button', { name: /sign in/i })).toBeVisible();
    });

    test('home redirects guests to login', async ({ page }) => {
        await page.goto('./');

        await expect(page).toHaveURL(/\/login/);
    });
});

test.describe('staging auth (optional)', () => {
    test('credentials reach OTP screen', async ({ page }) => {
        const credentials = stagingCredentials();

        test.skip(!credentials, 'Set E2E_STAGING_EMAIL and E2E_STAGING_PASSWORD.');

        await page.goto('login');
        await page.fill('input[name="email"]', credentials!.email);
        await page.fill('input[name="password"]', credentials!.password);
        await page.getByRole('button', { name: /sign in/i }).click();

        await expect(page.locator('#otpForm')).toBeVisible({ timeout: 15000 });
        await expect(page.getByText(/please enter the otp/i)).toBeVisible();
    });

    test('OTP completes login when E2E_STAGING_OTP is provided', async ({ page }) => {
        const credentials = stagingCredentials();
        const otp = stagingOtp();

        test.skip(
            !credentials || !otp,
            'Set E2E_STAGING_EMAIL, E2E_STAGING_PASSWORD, and E2E_STAGING_OTP.',
        );

        await page.goto('login');
        await page.fill('input[name="email"]', credentials!.email);
        await page.fill('input[name="password"]', credentials!.password);
        await page.getByRole('button', { name: /sign in/i }).click();
        await expect(page.locator('#otpForm')).toBeVisible({ timeout: 15000 });

        const inputs = page.locator('.otp');
        const digits = otp!.split('');

        for (let index = 0; index < digits.length; index++) {
            await inputs.nth(index).fill(digits[index]);
        }

        await page.getByRole('button', { name: /verify otp/i }).click();

        await expect(page).toHaveURL(/\/$|\/dashboard/, { timeout: 20000 });
    });
});
