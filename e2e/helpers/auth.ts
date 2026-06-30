import { expect, type APIRequestContext, type Page } from '@playwright/test';
import { envValue } from './env';
import { E2E_USERS } from './module-registry';

export function e2eDevSecret(): string | undefined {
    return envValue('E2E_DEV_SECRET');
}

export function hasLocalE2eSupport(): boolean {
    return Boolean(e2eDevSecret());
}

function e2eHeaders(): Record<string, string> {
    const secret = e2eDevSecret();

    if (!secret) {
        throw new Error('Set E2E_DEV_SECRET in .env for authenticated local Playwright tests.');
    }

    return { 'X-E2E-Secret': secret };
}

export async function assertDevE2eAvailable(request: APIRequestContext): Promise<void> {
    const secret = e2eDevSecret();

    if (!secret) {
        throw new Error('E2E_DEV_SECRET is missing — run authenticated tests after configuring .env');
    }

    const probe = await request.post('dev/e2e/login', {
        headers: { 'X-E2E-Secret': 'invalid-probe' },
        form: { email: E2E_USERS.superAdmin },
    });

    expect(probe.status(), 'dev/e2e routes should exist in APP_ENV=local').not.toBe(404);
}

/** Local-only session login (mirrors Dusk loginAs). */
export async function loginAs(page: Page, email: string): Promise<void> {
    await page.goto('login');

    const response = await page.request.post('dev/e2e/login', {
        headers: e2eHeaders(),
        form: { email },
    });

    expect(response.ok(), `dev login failed for ${email}: ${response.status()}`).toBeTruthy();
}

export async function loginDealerByPhone(page: Page, phone = E2E_USERS.dealerPhone): Promise<void> {
    await page.goto('login');
    await page.fill('input[name="email"]', phone);
    await page.fill('input[name="password"]', E2E_USERS.password);
    await page.getByRole('button', { name: /sign in/i }).click();
    await expect(page.locator('#sidebar-menu')).toBeVisible({ timeout: 20000 });
}

export async function loginEmailWithOtp(page: Page, email: string, password = E2E_USERS.password): Promise<void> {
    await page.goto('login');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', password);
    await page.getByRole('button', { name: /sign in/i }).click();
    await expect(page.locator('#otpForm')).toBeVisible({ timeout: 20000 });

    const otpResponse = await page.request.get(`dev/e2e/latest-otp?email=${encodeURIComponent(email)}`, {
        headers: e2eHeaders(),
    });

    expect(otpResponse.ok()).toBeTruthy();

    const { otp } = (await otpResponse.json()) as { otp: string | number | null };

    expect(otp, 'OTP should be generated after email login').toBeTruthy();

    await fillOtp(page, String(otp));
    await page.locator('#otpForm').evaluate((form: HTMLFormElement) => form.requestSubmit());
    await expect(page.locator('#sidebar-menu')).toBeVisible({ timeout: 25000 });
}

async function fillOtp(page: Page, otp: string): Promise<void> {
    const digits = otp.padStart(6, '0').slice(0, 6).split('');

    for (let index = 0; index < digits.length; index++) {
        await page.locator('.otp').nth(index).fill(digits[index]!);
    }
}
