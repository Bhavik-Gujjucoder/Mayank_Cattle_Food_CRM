import { expect, test } from '@playwright/test';
import { loginAs } from '../helpers/auth';
import { E2E_USERS } from '../helpers/module-registry';
import { skipUnlessLocalE2e } from './authenticated-setup';

test.describe('deeper UI flows', () => {
    test.beforeEach(async ({ page }) => {
        skipUnlessLocalE2e();
        await loginAs(page, E2E_USERS.superAdmin);
    });

    test('brand modal create reloads datatable', async ({ page }) => {
        const brandName = `E2E Brand ${Date.now()}`;

        await page.goto('brand');
        await expect(page.locator('#brand_table')).toBeVisible({ timeout: 20000 });

        await page.locator('#openModal').click();
        await expect(page.locator('#brandModal')).toBeVisible();
        await page.fill('#brandForm input[name="name"]', brandName);
        await page.locator('#brandForm').evaluate((form: HTMLFormElement) => form.requestSubmit());

        await expect(page.locator('#brandModal')).toBeHidden({ timeout: 15000 });
        await page.fill('#customSearch', brandName);
        await expect(page.locator('#brand_table')).toContainText(brandName, { timeout: 15000 });
    });

    test('city add modal opens with form fields', async ({ page }) => {
        await page.goto('city');
        await expect(page.locator('#city_table')).toBeVisible({ timeout: 20000 });
        await page.locator('#openModal').click();
        await expect(page.locator('.modal.show')).toBeVisible();
        await expect(page.locator('.modal.show input[name="city_name"]')).toBeVisible();
    });

    test('dashboard loads sidebar and data endpoints', async ({ page }) => {
        const dataResponses: string[] = [];

        page.on('response', (response) => {
            const url = response.url();

            if (url.includes('dashboard/data') && response.status() >= 400) {
                dataResponses.push(`${response.status()} ${url}`);
            }
        });

        await page.goto('./');
        await expect(page.locator('#sidebar-menu')).toBeVisible();
        await page.waitForLoadState('networkidle');

        expect(dataResponses, `Dashboard data errors:\n${dataResponses.join('\n')}`).toEqual([]);
    });

    test('my profile page opens from header menu', async ({ page }) => {
        await page.goto('./');
        await page.locator('.main-drop .userset').click();
        await page.getByRole('link', { name: /my profile/i }).click();

        await expect(page).toHaveURL(/my-profile/);
        await expect(page.locator('form[action*="my-profile"]')).toBeVisible();
    });

    test('general settings tabs render', async ({ page }) => {
        await page.goto('general-setting/create');
        await expect(page.locator('#myTab')).toBeVisible();
        await expect(page.getByText('General Setting')).toBeVisible();
    });

    test('raw material export menu is available', async ({ page }) => {
        await page.goto('raw-material');
        await expect(page.locator('#raw_material_table')).toBeVisible({ timeout: 20000 });
        await expect(page.locator('#exportMaterialsBtn')).toBeVisible();
        await page.locator('#exportMaterialsBtn').click();
        await expect(page.getByRole('link', { name: /export excel/i })).toBeVisible();
    });
});
