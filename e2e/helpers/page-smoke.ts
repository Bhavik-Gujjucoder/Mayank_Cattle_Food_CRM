import { expect, type Page } from '@playwright/test';
import { type ModulePage, resolvedPath } from './module-registry';

const SERVER_ERROR_PATTERNS = [
    /welcome to xampp for windows/i,
    /500\s*\|\s*server error/i,
    /server error/i,
    /whoops/i,
];

export async function smokeModulePage(page: Page, modulePage: ModulePage, timeout = 20000): Promise<void> {
    const response = await page.goto(resolvedPath(modulePage.path));

    expect(response?.status(), `${modulePage.path} should not 5xx`).toBeLessThan(500);

    for (const pattern of SERVER_ERROR_PATTERNS) {
        await expect(page.locator('body')).not.toContainText(pattern, { timeout: 5000 });
    }

    await expect(page.locator(modulePage.selector).first()).toBeVisible({ timeout });
    await expect(page.locator('.page-wrapper')).toContainText(modulePage.text, { timeout });
}

export async function smokeModulePages(page: Page, pages: ModulePage[]): Promise<void> {
    expect(pages.length, 'Expected at least one module page').toBeGreaterThan(0);

    for (const modulePage of pages) {
        await smokeModulePage(page, modulePage);
    }
}

export async function assertDeniedPage(page: Page, path: string, missingSelector: string): Promise<void> {
    await page.goto(resolvedPath(path));
    await expect(page.locator(missingSelector)).toHaveCount(0);
}
