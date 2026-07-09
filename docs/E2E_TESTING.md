# End-to-end testing (Dusk + Playwright)

This project uses **three layers** of automated testing. Each layer targets different environments.

## Environment matrix

| Layer | Tool | Target | What it tests | When to run |
|-------|------|--------|---------------|-------------|
| HTTP / API | PHPUnit / Pest (`php artisan test`) | **Local CI** (SQLite in-memory) | Controllers, permissions, routes, DB, ~1759 tests | Every commit |
| Browser (deep) | **Laravel Dusk** (`php artisan dusk`) | **Local only** | Real Chrome: login + OTP, sidebar modules, modals | Before merging UI changes |
| Browser (smoke) | **Playwright** (`npm run test:e2e:*`) | **Local / Staging / Prod** | Public pages, assets, optional staging auth | Local dev; staging on deploy; prod on schedule |

```text
                    ┌─────────────────────────────────┐
                    │  Production (Playwright prod)      │
                    │  Read-only: /login, assets, 302  │
                    └─────────────────────────────────┘
                                    ▲
                    ┌─────────────────────────────────┐
                    │  Staging (Playwright staging)    │
                    │  Public smoke + optional OTP auth │
                    └─────────────────────────────────┘
                                    ▲
        ┌───────────────────────────┴───────────────────────────┐
        │  Local                                                   │
        │  • php artisan test        (Pest — fast)                 │
        │  • php artisan dusk        (Dusk — full browser, PHP)   │
        │  • npm run test:e2e:local  (Playwright — quick smoke)   │
        └─────────────────────────────────────────────────────────┘
```

---

## 1. PHPUnit / Pest (all environments in CI)

```bash
php artisan test
```

Uses `phpunit.xml` with SQLite `:memory:`. Does **not** open a browser.

---

## 2. Laravel Dusk — **local only**

Dusk drives Chrome against a **running local app**. It uses `.env.dusk.local` (copied over `.env` while tests run).

### Setup (once)

```bash
# 1. Copy and edit Dusk env (use a FILE sqlite DB, not :memory:)
copy .env.dusk.local.example .env.dusk.local

# 2. Generate key if needed
php artisan key:generate --env=dusk.local

# 3. Create sqlite file
type nul > database\dusk.sqlite

# 4. Migrate Dusk database
php artisan migrate --env=dusk.local
```

### Run

```bash
# Terminal 1 — app must be running at APP_URL from .env.dusk.local
php artisan serve --host=127.0.0.1 --port=8000

# Terminal 2
php artisan dusk

# Watch the browser (debugging)
php artisan dusk --browse
```

### What Dusk covers here

| File | Purpose |
|------|---------|
| `tests/Browser/LoginFlowTest.php` | Full email → OTP → dashboard in Chrome |
| `tests/Browser/SuperAdminModuleTest.php` | All module index pages (super admin) |
| `tests/Browser/AdminModuleTest.php` | Permission-filtered modules + backup 403 |
| `tests/Browser/StaffModuleTest.php` | Staff sales permissions + denied dealer page |
| `tests/Browser/BrokerSalesModuleTest.php` | Broker email/OTP login + sales pages |
| `tests/Browser/DealerSalesModuleTest.php` | Dealer phone login + sales pages |
| `tests/Browser/ModuleSmokeTest.php` | Legacy admin permissions/roles smoke |

Run a subset: `php artisan dusk --filter=SuperAdminModuleTest`

**Do not point Dusk at staging or production.** It can create users, run migrations, and uses Dusk login routes.

---

## 3. Playwright — **local / staging / prod**

Install (once):

```bash
npm install
npm run test:e2e:install
```

### Local

Playwright resolves the local base URL in this order:

1. `E2E_LOCAL_URL` (shell env or `.env`)
2. `APP_URL` from `.env` (recommended for XAMPP — keep both in sync)
3. `http://127.0.0.1:8000` (fallback for `php artisan serve`)

**XAMPP** (matches `APP_URL` in `.env`):

```bash
npm run test:e2e:local
```

Example: `APP_URL=http://127.0.0.1:8000`

**artisan serve**:

```bash
php artisan serve --host=127.0.0.1 --port=8000
set E2E_LOCAL_URL=http://127.0.0.1:8000
npm run test:e2e:local
```

If tests hit Apache “Not Found” instead of the login page, the base URL is wrong — open `{baseURL}login` in a browser first.

Playwright note: local base URLs include a trailing slash and tests use relative paths (`login`, not `/login`) so XAMPP subdirectory installs resolve correctly.

#### Full local suite (modules, roles, CRUD smoke, browser health)

One-time setup:

```bash
# 1. Add to .env (APP_ENV must be local)
E2E_DEV_SECRET=change-me-local-e2e-secret

# 2. Seed fixed E2E users (password: password)
php artisan db:seed --class=E2eTestUserSeeder

# 3. Run all local Playwright tests
npm run test:e2e:local
```

| Spec file | Coverage |
|-----------|----------|
| `public-smoke.spec.ts` | Guest login page, assets, redirect |
| `auth.spec.ts` | Guest redirects, email OTP login, dealer phone login, forgot password |
| `super-admin.spec.ts` | All module index pages |
| `admin.spec.ts` | Permitted modules + backup 403 |
| `staff.spec.ts` | Sales modules + permissions denied |
| `broker.spec.ts` | Email OTP + sales modules |
| `dealer.spec.ts` | Phone login + sales modules |
| `deeper-flows.spec.ts` | Brand create modal, city modal, dashboard data, profile, settings, export UI |
| `non-functional.spec.ts` | Assets, console errors, mobile viewport, page health |

Authenticated tests are **skipped** when `E2E_DEV_SECRET` is unset. Dev routes (`/dev/e2e/*`) exist only when `APP_ENV=local`.

E2E users (from seeder):

| Role | Email / phone | Password |
|------|----------------|----------|
| Super admin | `e2e-superadmin@mayank.local` | `password` |
| Admin | `e2e-admin@mayank.local` | `password` |
| Staff | `e2e-staff@mayank.local` | `password` |
| Broker | `e2e-broker@mayank.local` | `password` |
| Dealer | phone `9876598765` | `password` |

### Staging

```bash
set E2E_STAGING_URL=https://staging.your-domain.com
set E2E_STAGING_EMAIL=monitor@staging.example.com
set E2E_STAGING_PASSWORD=secret
rem Optional — from mail catcher or manual OTP read:
set E2E_STAGING_OTP=123456

npm run test:e2e:staging
```

Staging tests:

- Always: login page health, guest redirects  
- Optional: credentials → OTP screen  
- Optional: full login when `E2E_STAGING_OTP` is set  

Use a **dedicated monitoring user** on staging, not a real staff account.

### Production

```bash
set E2E_PROD_URL=https://app.mayankcattlefood.com
npm run test:e2e:prod
```

Production tests are **read-only**:

- `/login` returns 200  
- CSS/JS under `/assets/` are not 404  
- Guests cannot open `/permissions` (redirect to login)  
- No login, no OTP (avoids real emails via `LoginOtpDelivery`)  

Run from CI or your PC — **never install Playwright on the production server**.

---

## GitHub Actions

Two workflows live in `.github/workflows/`:

| Workflow | File | When it runs |
|----------|------|--------------|
| **Tests** | `tests.yml` | Every push / PR to `main`, `master`, `develop` — runs `php artisan test` |
| **Playwright Smoke** | `playwright-smoke.yml` | Every **30 minutes** (prod + staging) + manual dispatch |

### Required GitHub secrets

Add these under **Settings → Secrets and variables → Actions**:

| Secret | Required for | Example |
|--------|--------------|---------|
| `E2E_PROD_URL` | Production smoke | `https://app.mayankcattlefood.com` |
| `E2E_STAGING_URL` | Staging smoke | `https://app.mayankcattlefood.com` |
| `E2E_STAGING_EMAIL` | Optional staging auth tests | `abhayl.gc@gmail.com` |
| `E2E_STAGING_PASSWORD` | Optional staging auth tests | dedicated monitor password |
| `E2E_STAGING_OTP` | Optional full staging login in CI | only when using manual dispatch with auth enabled |

URLs must be the **public base URL** (no trailing slash). Playwright runs **outside** your servers.

### Manual Playwright run

1. Open **Actions → Playwright Smoke → Run workflow**
2. Choose `prod`, `staging`, or `both`
3. Enable **Run optional staging OTP login tests** only if `E2E_STAGING_OTP` is set and you accept OTP emails on staging

Scheduled runs **never** pass `E2E_STAGING_OTP` — only public staging smoke + credentials-to-OTP-screen (if email/password secrets exist).

### Failed run artifacts

On failure, HTML reports and screenshots upload as `playwright-report-prod` or `playwright-report-staging` artifacts (kept 7 days).

---

## Environment variables summary

| Variable | Used by | Required for |
|----------|---------|--------------|
| `.env.dusk.local` | Dusk | Local browser tests |
| `E2E_LOCAL_URL` | Playwright `local` | Optional override |
| `E2E_DEV_SECRET` | Playwright local auth + `/dev/e2e/*` | Full local suite |
| `E2E_STAGING_URL` | Playwright `staging` | Staging project |
| `E2E_STAGING_EMAIL` | Playwright `staging` | Optional auth tests |
| `E2E_STAGING_PASSWORD` | Playwright `staging` | Optional auth tests |
| `E2E_STAGING_OTP` | Playwright `staging` | Full staging login |
| `E2E_PROD_URL` | Playwright `prod` | Production smoke |

Add staging/prod URLs to your CI secrets — not to `.env` committed to git.

---

## Recommended CI schedule

| Job | Trigger |
|-----|---------|
| `php artisan test` | Every push / PR (`tests.yml`) |
| `php artisan dusk` | Nightly or pre-release (self-hosted runner with Chrome) |
| `npm run test:e2e:staging` | Scheduled + after staging deploy (`playwright-smoke.yml`) |
| `npm run test:e2e:prod` | Every 30 minutes (`playwright-smoke.yml`) |

---

## XAMPP note

Set `APP_URL` in `.env` to the exact URL you use in the browser (including `/public` if applicable). Playwright local tests reuse that value automatically. Override only when needed:

```bash
set E2E_LOCAL_URL=http://localhost/Mayank_Cattle_Food_CRM/public
```

For Dusk, set the same URL in `.env.dusk.local`. Mismatched URLs cause 404 pages, broken assets, and failed guest redirects.
