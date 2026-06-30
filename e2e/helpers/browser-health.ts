import { expect, type Page } from '@playwright/test';
import { expectLaravelLoginPage } from './smoke';

export type BrowserHealthReport = {
    consoleErrors: string[];
    failedAssets: string[];
    loadedAssets: number;
};

export function trackBrowserHealth(page: Page): BrowserHealthReport {
    const report: BrowserHealthReport = {
        consoleErrors: [],
        failedAssets: [],
        loadedAssets: 0,
    };

    page.on('pageerror', (error) => {
        report.consoleErrors.push(error.message);
    });

    page.on('console', (message) => {
        if (message.type() === 'error') {
            report.consoleErrors.push(message.text());
        }
    });

    page.on('response', (response) => {
        const url = response.url();

        if (!url.includes('/assets/')) {
            return;
        }

        if (response.status() >= 400) {
            report.failedAssets.push(`${response.status()} ${url}`);
        } else {
            report.loadedAssets++;
        }
    });

    return report;
}

export function assertBrowserHealth(report: BrowserHealthReport, context: string): void {
    expect(report.loadedAssets, `${context}: should load /assets/`).toBeGreaterThan(0);
    expect(report.failedAssets, `${context}: broken assets:\n${report.failedAssets.join('\n')}`).toEqual([]);
    expect(report.consoleErrors, `${context}: JS errors:\n${report.consoleErrors.join('\n')}`).toEqual([]);
}

export async function assertAuthenticatedPageHealth(
    page: Page,
    relativePath: string,
    context: string,
): Promise<void> {
    const report = trackBrowserHealth(page);
    const response = await page.goto(relativePath);

    await page.waitForLoadState('networkidle');
    expect(response?.status(), `${context} should return 200`).toBe(200);
    await expect(page.getByRole('heading', { name: /^not found$/i })).not.toBeVisible();
    assertBrowserHealth(report, context);
}

export async function assertLoginPageHealth(page: Page): Promise<void> {
    const report = trackBrowserHealth(page);
    const response = await page.goto('login');

    await page.waitForLoadState('networkidle');
    await expectLaravelLoginPage(page, response);
    assertBrowserHealth(report, 'login page');
}

export async function assertMobileViewport(page: Page, relativePath: string): Promise<void> {
    await page.setViewportSize({ width: 390, height: 844 });
    const response = await page.goto(relativePath);

    expect(response?.status()).toBeLessThan(500);
    await expect(page.locator('input[name="email"]')).toBeVisible();
}
