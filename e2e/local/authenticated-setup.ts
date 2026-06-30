import { test } from '@playwright/test';
import { hasLocalE2eSupport } from '../helpers/auth';

/** Skip authenticated local suites when E2E_DEV_SECRET or seeded users are not configured. */
export function skipUnlessLocalE2e(): void {
    test.skip(!hasLocalE2eSupport(), 'Set E2E_DEV_SECRET in .env and run: php artisan db:seed --class=E2eTestUserSeeder');
}
