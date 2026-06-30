import { test } from '@playwright/test';
import { loginEmailWithOtp } from '../helpers/auth';
import { E2E_USERS, pagesForRole } from '../helpers/module-registry';
import { smokeModulePages } from '../helpers/page-smoke';
import { skipUnlessLocalE2e } from './authenticated-setup';

test.describe('broker sales modules', () => {
    test.beforeEach(() => {
        skipUnlessLocalE2e();
    });

    test('email OTP login reaches sales module pages', async ({ page }) => {
        await loginEmailWithOtp(page, E2E_USERS.broker);
        await smokeModulePages(page, pagesForRole('broker'));
    });
});
