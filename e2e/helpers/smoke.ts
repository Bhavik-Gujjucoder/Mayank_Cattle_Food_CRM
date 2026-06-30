import { expect, type Page, type Response } from '@playwright/test';

export async function expectLaravelLoginPage(
    page: Page,
    response?: Response | null,
): Promise<void> {
    if (response) {
        expect(response.status(), 'Login page should not 5xx').toBeLessThan(500);
        expect(response.status(), 'Login page should return 200 — check E2E_LOCAL_URL / APP_URL').toBe(200);
    }

    await expect(page.getByRole('heading', { name: /^not found$/i })).not.toBeVisible();
    await expect(page.getByRole('heading', { name: /sign in/i })).toBeVisible();
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
}

export async function expectAuthAssetsHealthy(page: Page): Promise<void> {
    const failed: string[] = [];
    let loadedAssets = 0;

    page.on('response', (response) => {
        const url = response.url();

        if (!url.includes('/assets/')) {
            return;
        }

        if (response.status() >= 400) {
            failed.push(`${response.status()} ${url}`);
        } else {
            loadedAssets++;
        }
    });

    const response = await page.goto('login');
    await page.waitForLoadState('networkidle');

    await expectLaravelLoginPage(page, response);
    expect(loadedAssets, 'Login page should load CSS/JS from /assets/').toBeGreaterThan(0);
    expect(failed, `Broken assets:\n${failed.join('\n')}`).toEqual([]);
}
