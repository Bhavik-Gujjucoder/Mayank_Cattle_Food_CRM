import { test } from '@playwright/test';
import { loginDealerByPhone } from '../helpers/auth';
import { pagesForRole } from '../helpers/module-registry';
import { smokeModulePages } from '../helpers/page-smoke';
import { skipUnlessLocalE2e } from './authenticated-setup';

test.describe('dealer sales modules', () => {
    test.beforeEach(() => {
        skipUnlessLocalE2e();
    });

    test('phone login reaches sales module pages', async ({ page }) => {
        await loginDealerByPhone(page);
        await smokeModulePages(page, pagesForRole('dealer'));
    });
});
