import { existsSync, readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const projectRoot = resolve(dirname(fileURLToPath(import.meta.url)), '../..');

function parseEnvFile(path: string): Record<string, string> {
    const vars: Record<string, string> = {};

    if (!existsSync(path)) {
        return vars;
    }

    for (const line of readFileSync(path, 'utf8').split(/\r?\n/)) {
        const trimmed = line.trim();

        if (!trimmed || trimmed.startsWith('#')) {
            continue;
        }

        const eq = trimmed.indexOf('=');

        if (eq === -1) {
            continue;
        }

        const key = trimmed.slice(0, eq).trim();
        let value = trimmed.slice(eq + 1).trim();

        if (
            (value.startsWith('"') && value.endsWith('"'))
            || (value.startsWith("'") && value.endsWith("'"))
        ) {
            value = value.slice(1, -1);
        }

        vars[key] = value;
    }

    return vars;
}

let cachedProjectEnv: Record<string, string> | null = null;

function projectEnv(): Record<string, string> {
    if (cachedProjectEnv === null) {
        cachedProjectEnv = parseEnvFile(resolve(projectRoot, '.env'));
    }

    return cachedProjectEnv;
}

export function envValue(key: string): string | undefined {
    return process.env[key] ?? projectEnv()[key];
}

function ensureTrailingSlash(url: string): string {
    return `${url.replace(/\/+$/, '')}/`;
}

function isBareOrigin(url: string): boolean {
    try {
        const parsed = new URL(url);

        return parsed.pathname === '' || parsed.pathname === '/';
    } catch {
        return false;
    }
}

export function localBaseUrl(): string {
    const appUrl = envValue('APP_URL');
    const e2eUrl = envValue('E2E_LOCAL_URL');

    // A bare host in E2E_LOCAL_URL (e.g. http://192.168.0.183) 404s subdirectory installs.
    if (appUrl && e2eUrl && isBareOrigin(e2eUrl) && !isBareOrigin(appUrl)) {
        return ensureTrailingSlash(appUrl);
    }

    const url = e2eUrl ?? appUrl ?? 'http://127.0.0.1:8000';

    return ensureTrailingSlash(url);
}

export function stagingBaseUrl(): string {
    const url = envValue('E2E_STAGING_URL');

    if (!url) {
        return 'https://app.mayankcattlefood.com/';
    }

    return ensureTrailingSlash(url);
}

export function prodBaseUrl(): string {
    const url = envValue('E2E_PROD_URL');

    if (!url) {
        return 'https://app.mayankcattlefood.com/';
    }

    return ensureTrailingSlash(url);
}

export function hasStagingUrl(): boolean {
    return Boolean(envValue('E2E_STAGING_URL'));
}

export function hasProdUrl(): boolean {
    return Boolean(envValue('E2E_PROD_URL'));
}

export function stagingCredentials(): { email: string; password: string } | null {
    const email = envValue('E2E_STAGING_EMAIL');
    const password = envValue('E2E_STAGING_PASSWORD');

    if (!email || !password) {
        return null;
    }

    return { email, password };
}

export function stagingOtp(): string | null {
    const otp = envValue('E2E_STAGING_OTP');

    return otp && /^\d{6}$/.test(otp) ? otp : null;
}
