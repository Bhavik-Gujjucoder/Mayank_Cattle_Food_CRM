import { defineConfig, devices } from '@playwright/test';
import { localBaseUrl, prodBaseUrl, stagingBaseUrl } from './helpers/env';

/**
 * Playwright projects map to deployment targets:
 *   local   — developer machine (XAMPP via APP_URL, or artisan serve)
 *   staging — pre-production mirror (full read-only + optional auth flows)
 *   prod    — live site (read-only smoke only)
 */
export default defineConfig({
    testDir: './',
    fullyParallel: true,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    workers: process.env.CI ? 1 : undefined,
    reporter: [['list'], ['html', { open: 'never', outputFolder: 'playwright-report' }]],
    use: {
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    projects: [
        {
            name: 'local',
            testDir: './local',
            timeout: 90_000,
            workers: 1,
            use: {
                ...devices['Desktop Chrome'],
                baseURL: localBaseUrl(),
            },
        },
        {
            name: 'staging',
            testDir: './staging',
            use: {
                ...devices['Desktop Chrome'],
                baseURL: stagingBaseUrl(),
            },
        },
        {
            name: 'prod',
            testDir: './prod',
            use: {
                ...devices['Desktop Chrome'],
                baseURL: prodBaseUrl(),
            },
        },
    ],
});
