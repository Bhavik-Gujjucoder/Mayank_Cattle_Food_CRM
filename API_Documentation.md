# Mayank Cattle Food CRM — Mobile API Documentation

> **Version:** v1  
> **Stack:** Laravel 12 · Laravel Sanctum · REST JSON API  
> **Audience:** Mobile developers (Android / iOS), QA, backend maintainers

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Base URLs](#base-urls)
3. [Route Prefix Structure](#route-prefix-structure)
4. [Authentication: Mobile vs Web](#authentication-mobile-vs-web)
5. [Mobile Login Flow (Sanctum)](#mobile-login-flow-sanctum)
6. [Web OTP Flow (Reference Only)](#web-otp-flow-reference-only)
7. [Laravel Sanctum Setup](#laravel-sanctum-setup)
8. [Standard Response Format](#standard-response-format)
9. [API Endpoints](#api-endpoints)
   - [System: Health Check](#api-system-health-check)
   - [Auth: Login](#api-auth-login)
   - [Auth: OTP Verify](#api-auth-otp-verify)
   - [Auth: OTP Resend](#api-auth-otp-resend)
   - [Auth: Forgot Password](#api-auth-forgot-password)
   - [Auth: Authentication Check (Dealer/Broker)](#api-auth-authentication-check-dealerbroker)
   - [Orders: Soda/Order Listing](#api-orders-sodaorder-listing)
   - [Dispatches: Dispatch Listing (API 9)](#api-dispatches-dispatch-listing-api-9)
10. [Bearer Token Usage](#bearer-token-usage)
11. [Postman Testing](#postman-testing)
12. [Android Integration](#android-integration)
13. [iOS Integration](#ios-integration)
14. [Security Considerations](#security-considerations)
15. [Project Structure & Code Flow](#project-structure--code-flow)
16. [Future Endpoints](#future-endpoints)
17. [Migrations & Support](#migrations--support)

---

## Project Overview

This document describes the **Mobile Application REST API** for Mayank Cattle Food CRM.

| Aspect | Detail |
|--------|--------|
| Purpose | Authenticate mobile users and expose CRM data via JSON |
| Auth mechanism | **Laravel Sanctum** personal access tokens (`Bearer`) |
| Web app impact | **None** — web session + OTP login is unchanged |
| API entry file | `routes/api.php` |
| Version prefix | `/api/v1` |

---

## Base URLs

Replace the host with your environment.

| Environment | Base URL (XAMPP) | Base URL (`artisan serve`) |
|-------------|------------------|----------------------------|
| Local | `http://localhost/Mayank_Cattle_Food_CRM/public/api` | `http://127.0.0.1:8000/api` |
| Production | `https://your-domain.com/api` | — |

**Full endpoint example (XAMPP login):**  
`http://localhost/Mayank_Cattle_Food_CRM/public/api/v1/auth/login`

---

## Route Prefix Structure

All routes are grouped by **functionality** under `/api/v1`:

| Prefix | Auth required | Purpose |
|--------|---------------|---------|
| `/api/v1/auth/*` | No (public) | Login, logout, forgot/reset password (future) |
| `/api/v1/system/*` | No (public) | Health checks, connectivity smoke tests |
| `/api/v1/profile/*` | Yes (`Bearer`) | User profile read/update (planned) |
| `/api/v1/notifications/*` | Yes (`Bearer`) | Notifications (planned) |
| `/api/v1/training/*` | Yes (`Bearer`) | Training module (planned) |

### Named routes (Laravel)

| Route name | Method | URI |
|------------|--------|-----|
| `api.v1.auth.login` | POST | `/api/v1/auth/login` |
| `api.v1.auth.otp.verify` | POST | `/api/v1/auth/otp/verify` |
| `api.v1.auth.otp.resend` | POST | `/api/v1/auth/otp/resend` |
| `api.v1.auth.forgot-password` | POST | `/api/v1/auth/forgot-password` |
| `api.v1.auth.me` | GET | `/api/v1/auth/me` |
| `api.v1.orders.index` | GET | `/api/v1/orders` |
| `api.v1.dispatches.index` | GET | `/api/v1/dispatches` |
| `api.v1.system.health-check` | GET | `/api/v1/system/health-check` |

---

## Authentication: Mobile vs Web

The CRM has **two independent authentication systems**. Do not mix them.

### Mobile API (this document)

**Email login (two-step OTP):**
```
POST /api/v1/auth/login  { email, password }
  → 202 { otp_token, expires_in_seconds }
  → OTP emailed to user
POST /api/v1/auth/otp/verify  { otp_token, otp }
  → 200 { access_token, token_type: "Bearer" }
```

**Phone login (single-step):**
```
POST /api/v1/auth/login  { phone, password }
  → 200 { access_token, token_type: "Bearer" }
```

- **No session cookies** — use `Authorization: Bearer {token}`.
- Email login always triggers OTP; phone login issues the token immediately.

### Web application (browser — not mobile API)

| Login type | Flow |
|------------|------|
| **Email** | Password check → **OTP emailed** → user enters OTP → **session** login |
| **Phone** | Password check → **session** login (dealers only) |

- OTP handled by: `App\Http\Controllers\OtpController`.
- Email OTP triggered by: `App\Http\Controllers\Auth\AuthenticatedSessionController`.

> **Important:** Mobile developers should call **`/api/v1/auth/login` only**.  
> Web OTP routes (`/verify-otp`, `/resend-otp`) are for the browser app, not the mobile app.

---

## Mobile Login Flow (Sanctum)

### Step-by-step

```
┌──────────────┐
│  Mobile App  │
└──────┬───────┘
       │  POST /api/v1/auth/login
       │  { login, password, device_name? }
       ▼
┌──────────────────────────────────────────────────────────┐
│  LoginRequest — validate login format & password rules   │
└──────┬───────────────────────────────────────────────────┘
       ▼
┌──────────────────────────────────────────────────────────┐
│  LoginService::authenticate()                            │
│    1. resolveUser() — email → users.email              │
│                       phone → users.phone_no            │
│    2. userCanAuthenticate() — status=1, not deleted     │
│    3. Hash::check() — verify password                   │
└──────┬───────────────────────────────────────────────────┘
       │  success                          │  failure
       ▼                                   ▼
┌─────────────────────┐            ┌─────────────────┐
│ createToken()       │            │ 401 JSON error  │
│ (Sanctum)           │            │ generic message │
└──────┬──────────────┘            └─────────────────┘
       ▼
┌──────────────────────────────────────────────────────────┐
│  200 JSON: user + access_token + token_type: Bearer      │
└──────────────────────────────────────────────────────────┘
```

### Sanctum token generation

1. `$user->createToken($deviceName)` creates a row in `personal_access_tokens`.
2. The **plain-text token** is returned **once** in the response (`access_token`).
3. Only a **hash** of the token is stored in the database (Sanctum default).
4. Mobile app must store `access_token` securely and send it on every protected request.
5. Optional `device_name` labels the token (e.g. `"iPhone 15"`, `"Android Pixel"`).

### Login identifier auto-detection

| `login` value | Resolved via |
|---------------|--------------|
| Valid email format | `users.email` |
| 10–15 digit number | `users.phone_no` |
| Anything else | `422` validation error |

---

## Web OTP Flow (Reference Only)

> Not used by the mobile API. Documented so developers understand the full CRM auth picture.

### OTP lifecycle (web email login)

| Step | Where | What happens |
|------|-------|--------------|
| 1. Password OK | `AuthenticatedSessionController` | 6-digit OTP generated (`rand(100000, 999999)`) |
| 2. Storage | `users` table | `otp_code`, `otp_expires_at` (= now + 5 minutes) |
| 3. Session | Server session | `otp_user_id`, `remember_me` stored |
| 4. Delivery | `LoginOtpDelivery` | OTP queued/sent to user's email |
| 5. Verification | `OtpController::verify` | Compare OTP + check expiry |
| 6. Success | `OtpController` | Clear OTP fields, `Auth::login()`, session regenerate |
| 7. Retry | `OtpController::resendOtp` | New OTP, reset 5-minute expiry, resend email |

### OTP rules

- **Length:** 6 digits  
- **Expiry:** 5 minutes (`otp_expires_at`)  
- **Single use:** Cleared from DB after successful verification  
- **Invalid/expired:** User sees form error, must re-enter or resend  

---

## Laravel Sanctum Setup

### Already configured in this project

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### Configuration reference

| Item | Location / notes |
|------|------------------|
| Sanctum config | `config/sanctum.php` |
| API routes | `routes/api.php` |
| User model | `HasApiTokens` trait on `App\Models\User` |
| Token table | `personal_access_tokens` |
| Rate limiter | `api-login` in `AppServiceProvider` (5/min) |

### Environment

```env
# Optional — for SPA cookie auth only; mobile uses Bearer tokens
SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

---

## Standard Response Format

Every mobile API response uses the same JSON envelope.

### Success (`success: true`)

```json
{
  "success": true,
  "message": "Human-readable success message.",
  "data": { }
}
```

| Field | Type | Description |
|-------|------|-------------|
| `success` | boolean | Always `true` |
| `message` | string | Short description for UI or logs |
| `data` | object \| array \| null | Payload (user, token, errors, etc.) |

### Error (`success: false`)

```json
{
  "success": false,
  "message": "Human-readable error message.",
  "data": null
}
```

### Validation error — HTTP 422

Returned by `ApiFormRequest` when validation fails.

```json
{
  "success": false,
  "message": "Validation failed.",
  "data": {
    "login": ["Email or phone number is required."],
    "password": ["Password must be at least 6 characters."]
  }
}
```

### HTTP status code summary

| Code | When |
|------|------|
| `200` | Success |
| `202` | Accepted — OTP required (email login path) |
| `401` | Invalid credentials, bad token, or ineligible account |
| `403` | Account inactive or suspended |
| `404` | OTP session not found |
| `410` | OTP expired |
| `422` | Validation failed or incorrect OTP |
| `429` | Rate limit exceeded or too many OTP attempts |
| `500` | Unexpected server error |

---

## API Endpoints

---

### API: System — Health Check

**Purpose:** Verify the API is reachable before testing login. No authentication.

| Property | Value |
|----------|-------|
| **URL** | `GET /api/v1/system/health-check` |
| **Route name** | `api.v1.system.health-check` |
| **Controller** | `App\Http\Controllers\Api\V1\System\HealthCheckController@index` |
| **Auth** | None |

#### Headers

| Header | Value |
|--------|-------|
| `Accept` | `application/json` |

#### Success response (200)

```json
{
  "success": true,
  "message": "Mobile API is running.",
  "data": {
    "service": "Mayank Cattle Food Mobile API",
    "version": "v1",
    "status": "ok"
  }
}
```

#### Postman (XAMPP)

```
GET http://localhost/Mayank_Cattle_Food_CRM/public/api/v1/system/health-check
```

---

### API: Auth — Login

**Purpose:** Authenticate with email or phone + password; receive a Sanctum Bearer token.

| Property | Value |
|----------|-------|
| **URL** | `POST /api/v1/auth/login` |
| **Route name** | `api.v1.auth.login` |
| **Controller** | `App\Http\Controllers\Api\V1\Auth\LoginController@store` |
| **Service** | `App\Services\Api\V1\Auth\LoginService` |
| **Form request** | `App\Http\Requests\Api\V1\Auth\LoginRequest` |
| **Auth** | None (public) |
| **Rate limit** | 5 requests/minute per `login` + IP |

#### Headers

| Header | Value | Required |
|--------|-------|----------|
| `Accept` | `application/json` | Yes |
| `Content-Type` | `application/json` | Yes |

#### Request body

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `login` | string | Yes | Email **or** 10–15 digit phone | Auto-detected identifier |
| `password` | string | Yes | min 6 characters | Account password |
| `device_name` | string | No | max 255 | Sanctum token label (default: `mobile-app`) |

#### Sample — email login

```json
{
  "login": "admin@example.com",
  "password": "password123",
  "device_name": "Android Pixel 8"
}
```

#### Sample — phone login

```json
{
  "login": "9876543210",
  "password": "password123"
}
```

#### Success response (200)

```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com",
      "phone_no": "9876543210",
      "profile_picture": "http://localhost/storage/profile_pictures/avatar.jpg",
      "status": 1,
      "roles": ["admin"]
    },
    "access_token": "1|plainTextTokenShownOnce",
    "token_type": "Bearer"
  }
}
```

#### Error — invalid credentials (401)

```json
{
  "success": false,
  "message": "The provided credentials are incorrect.",
  "data": null
}
```

> Generic message by design — does not reveal whether email/phone exists.

#### Error — validation (422)

```json
{
  "success": false,
  "message": "Validation failed.",
  "data": {
    "login": [
      "The login must be a valid email address or mobile number (10–15 digits)."
    ]
  }
}
```

#### Error — rate limit (429)

```json
{
  "message": "Too Many Attempts."
}
```

#### Account eligibility

Login is **rejected** (401) when:

- User not found or password incorrect
- `status` ≠ `1` (inactive / suspended)
- `deleted_at` is set (soft-deleted account)

#### Email login — 202 response (OTP required)

```json
{
  "success": true,
  "message": "OTP sent to your email address.",
  "data": {
    "otp_required": true,
    "otp_token": "eyJpdiI6Ii4uLi4iLCJ2YWx1ZSI6Ii4uLiJ9",
    "expires_in_seconds": 600
  }
}
```

> Store `otp_token` in memory and pass it to `/api/v1/auth/otp/verify` or `/api/v1/auth/otp/resend`.

#### Postman (XAMPP)

```
POST http://localhost/Mayank_Cattle_Food_CRM/public/api/v1/auth/login
```

---

### API: Auth — OTP Verify

**Purpose:** Submit the 6-digit OTP received by email to complete the login flow and receive a Sanctum Bearer token.

| Property | Value |
|----------|-------|
| **URL** | `POST /api/v1/auth/otp/verify` |
| **Route name** | `api.v1.auth.otp.verify` |
| **Controller** | `App\Http\Controllers\Api\V1\Auth\OtpController@verify` |
| **Service** | `App\Services\Api\V1\Auth\OtpService` |
| **Form request** | `App\Http\Requests\Api\V1\Auth\VerifyOtpRequest` |
| **Auth** | None (public) |
| **Rate limit** | 5 requests/minute per IP (`throttle:api-otp`) |

#### Headers

| Header | Value | Required |
|--------|-------|----------|
| `Accept` | `application/json` | Yes |
| `Content-Type` | `application/json` | Yes |

#### Request body

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `otp_token` | string | Yes | — | Token returned by `POST /auth/login` |
| `otp` | string | Yes | exactly 6 digits | The OTP received in the email |
| `device_name` | string | No | max 255 | Sanctum token label (default: `mobile-app`) |

#### Sample request

```json
{
  "otp_token": "eyJpdiI6Ii4uLi4iLCJ2YWx1ZSI6Ii4uLiJ9",
  "otp": "123456",
  "device_name": "Android Pixel 8"
}
```

#### Success response (200)

```json
{
  "success": true,
  "message": "OTP verified successfully.",
  "data": {
    "user": {
      "id": 1,
      "name": "Admin User",
      "email": "admin@example.com",
      "phone_no": "9876543210",
      "profile_picture": null,
      "status": 1,
      "roles": ["admin"]
    },
    "access_token": "1|plainTextTokenShownOnce",
    "token_type": "Bearer"
  }
}
```

#### Error responses

| Code | Scenario | `message` |
|------|----------|-----------|
| `401` | `otp_token` is tampered / invalid | `Invalid or expired OTP token.` |
| `403` | Account is inactive | `Your account is inactive. Please contact support.` |
| `404` | No pending OTP session for this user | `No pending OTP session found. Please log in again.` |
| `410` | OTP has expired (> 10 minutes) | `Your OTP has expired. Please log in again to receive a new one.` |
| `422` | OTP digits are wrong | `The OTP you entered is incorrect.` (+ `data.attempts_remaining`) |
| `429` | Max attempts reached | `Too many incorrect attempts. Please log in again.` |

#### Postman (XAMPP)

```
POST http://localhost/Mayank_Cattle_Food_CRM/public/api/v1/auth/otp/verify
```

---

### API: Auth — OTP Resend

**Purpose:** Invalidate the current OTP and send a fresh 6-digit code to the user's email address.

| Property | Value |
|----------|-------|
| **URL** | `POST /api/v1/auth/otp/resend` |
| **Route name** | `api.v1.auth.otp.resend` |
| **Controller** | `App\Http\Controllers\Api\V1\Auth\OtpController@resend` |
| **Service** | `App\Services\Api\V1\Auth\OtpService` |
| **Form request** | `App\Http\Requests\Api\V1\Auth\ResendOtpRequest` |
| **Auth** | None (public) |
| **Rate limit** | 3 requests per 10 minutes per IP (`throttle:api-otp-resend`) |

#### Headers

| Header | Value | Required |
|--------|-------|----------|
| `Accept` | `application/json` | Yes |
| `Content-Type` | `application/json` | Yes |

#### Request body

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `otp_token` | string | Yes | Token from the `/auth/login` or previous `/auth/otp/resend` response |

#### Sample request

```json
{
  "otp_token": "eyJpdiI6Ii4uLi4iLCJ2YWx1ZSI6Ii4uLiJ9"
}
```

#### Success response (200)

```json
{
  "success": true,
  "message": "OTP resent successfully. Please check your email.",
  "data": {
    "otp_token": "eyJpdiI6InVwZGF0ZWQiLCJ2YWx1ZSI6Ii4uLiJ9",
    "expires_in_seconds": 600,
    "resend_attempts_remaining": 2
  }
}
```

> Always use the **new** `otp_token` from the resend response on subsequent calls. The old OTP code is no longer valid after a resend.

#### Error responses

| Code | Scenario | `message` |
|------|----------|-----------|
| `401` | `otp_token` is tampered / invalid | `Invalid or expired OTP token.` |
| `403` | Account is inactive | `Your account is inactive. Please contact support.` |
| `422` | `otp_token` field missing | `Validation failed.` |
| `429` | Cooldown not elapsed (60 s default) | `Please wait {N} seconds before requesting a new OTP.` (+ `data.seconds_remaining`) |
| `429` | Max resends reached (3 by default) | `You have reached the maximum number of OTP resend attempts. Please log in again.` |

#### Postman (XAMPP)

```
POST http://localhost/Mayank_Cattle_Food_CRM/public/api/v1/auth/otp/resend
```

---

### API: Auth — Forgot Password

**Purpose:** Send a password reset link to the user's registered email address. The link points to the web reset page; after resetting, the user can log in through the mobile API.

| Property | Value |
|----------|-------|
| **URL** | `POST /api/v1/auth/forgot-password` |
| **Route name** | `api.v1.auth.forgot-password` |
| **Controller** | `App\Http\Controllers\Api\V1\Auth\ForgotPasswordController@store` |
| **Form request** | `App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest` |
| **Auth** | None (public) |
| **Rate limit** | 3 requests per 5 minutes per email + IP (`throttle:api-forgot-password`) |

#### Headers

| Header | Value | Required |
|--------|-------|----------|
| `Accept` | `application/json` | Yes |
| `Content-Type` | `application/json` | Yes |

#### Request body

| Field | Type | Required | Validation | Description |
|-------|------|----------|------------|-------------|
| `email` | string | Yes | valid email format, max 255 | The user's registered email address |

#### Sample request

```json
{
  "email": "user@example.com"
}
```

#### Success response (200)

Returned whether the email is registered **or not** (prevents user enumeration).

```json
{
  "success": true,
  "message": "If your email address is registered and your account is active, you will receive a password reset link shortly. Please check your inbox.",
  "data": null
}
```

#### Error responses

| Code | Scenario | `message` |
|------|----------|-----------|
| `403` | Account is inactive or soft-deleted | `Your account is inactive. Please contact support.` |
| `422` | Email field missing or invalid format | `Validation failed.` |
| `429` | Broker throttle: same email within 60 s | `Too many password reset requests. Please wait a moment before trying again.` |

#### Security note

The response body is **identical** for registered and unregistered email addresses (`200`). This prevents attackers from using this endpoint to enumerate valid accounts. Exception: inactive/deleted accounts return `403` — a meaningful signal only to someone who already knows the email exists.

#### Postman (XAMPP)

```
POST http://localhost/Mayank_Cattle_Food_CRM/public/api/v1/auth/forgot-password
```

---

### API: Auth — Authentication Check (Dealer/Broker)

**Purpose:** Verify that a Sanctum Bearer token is still valid, confirm the user holds the **Dealer** or **Broker** role, and return the full role and permission list for mobile UI gate-keeping. This is the first call the mobile app should make after obtaining a Bearer token to bootstrap the session.

| Property | Value |
|----------|-------|
| **URL** | `GET /api/v1/auth/me` |
| **Route name** | `api.v1.auth.me` |
| **Controller** | `App\Http\Controllers\Api\V1\Auth\AuthCheckController@show` |
| **Auth** | **Required** — Bearer token (`Authorization: Bearer {token}`) |
| **Rate limit** | None (protected by Sanctum middleware) |
| **Allowed roles** | `dealer`, `broker` only |

#### Headers

| Header | Value | Required |
|--------|-------|----------|
| `Accept` | `application/json` | Yes |
| `Authorization` | `Bearer {access_token}` | Yes |

#### Request body

None — this is a `GET` endpoint. Pass the token in the `Authorization` header only.

#### Role validation flow

```
GET /api/v1/auth/me
  Authorization: Bearer {token}
       │
       ▼
 Sanctum middleware
   token valid? ──No──→ 401 Unauthorized
       │ Yes
       ▼
 User has 'dealer' role? ──Yes──→ role = 'dealer'
       │ No                              │
 User has 'broker' role? ──Yes──→ role = 'broker'
       │ No                              │
       ▼                                 ▼
   403 Forbidden            Fetch getAllPermissions()
                                         │
                                         ▼
                              200 { user, role, permissions, token_status }
```

#### Success response (200)

```json
{
  "success": true,
  "message": "Authenticated successfully.",
  "data": {
    "user": {
      "id": 42,
      "name": "Ramesh Patel",
      "email": null,
      "phone_no": "9876543210",
      "profile_picture": null,
      "status": 1
    },
    "role": "dealer",
    "permissions": [
      "view-order",
      "view-dispatch"
    ],
    "token_status": "valid"
  }
}
```

> `permissions` is an array of permission name strings. It will be an empty array `[]` if no permissions are assigned to the role.

#### Error — no token / invalid token (401)

```json
{
  "message": "Unauthenticated."
}
```

#### Error — valid token, wrong role (403)

```json
{
  "success": false,
  "message": "Access denied. This endpoint is restricted to Dealer and Broker accounts.",
  "data": null
}
```

#### Account eligibility

The Sanctum middleware rejects:
- Requests with no `Authorization` header → **401**
- Requests with an invalid or malformed token → **401**
- Requests with a revoked token (deleted from `personal_access_tokens`) → **401**

The controller additionally rejects:
- Users with `admin`, `super admin`, or any other non-dealer/broker role → **403**

#### Postman (XAMPP)

```
GET http://localhost/Mayank_Cattle_Food_CRM/public/api/v1/auth/me
Authorization: Bearer {{access_token}}
Accept: application/json
```

#### Android integration

```kotlin
@GET("api/v1/auth/me")
suspend fun authCheck(
    @Header("Authorization") token: String
): AuthCheckResponse

// Usage — call immediately after login to verify role and cache permissions:
val response = api.authCheck("Bearer $savedToken")
if (response.data.role == "dealer") {
    // dealer UI
}
```

#### iOS integration

```swift
var request = URLRequest(url: URL(string: "\(baseURL)/api/v1/auth/me")!)
request.httpMethod = "GET"
request.setValue("application/json", forHTTPHeaderField: "Accept")
request.setValue("Bearer \(savedToken)", forHTTPHeaderField: "Authorization")
```

---

### API: Orders — Soda/Order Listing

**Purpose:** Return a paginated, filterable list of soda orders accessible to the authenticated Dealer or Broker. Implements the same role-based visibility rules as the web `OrderManagementController`, so each user sees exactly the same orders on mobile and web.

| Property | Value |
|----------|-------|
| **URL** | `GET /api/v1/orders` |
| **Route name** | `api.v1.orders.index` |
| **Controller** | `App\Http\Controllers\Api\V1\Orders\OrderController@index` |
| **Auth** | **Required** — Bearer token (`Authorization: Bearer {token}`) |
| **Rate limit** | None (protected by Sanctum middleware) |
| **Allowed roles** | `dealer`, `broker` only |

#### Headers

| Header | Value | Required |
|--------|-------|----------|
| `Accept` | `application/json` | Yes |
| `Authorization` | `Bearer {access_token}` | Yes |

#### Query parameters (all optional)

| Parameter | Type | Description | Role restriction |
|-----------|------|-------------|-----------------|
| `order_number` | string | Partial match on order reference (e.g. `0042`) | Both |
| `payment_status` | string | `unpaid` \| `paid` \| `partial` | Both |
| `date_from` | date (Y-m-d) | Order date lower bound (inclusive) | Both |
| `date_to` | date (Y-m-d) | Order date upper bound (inclusive) | Both |
| `brand_id` | integer | Filter by brand ID | Broker only — ignored for dealers |
| `dealer_id` | integer | Filter by dealer ID | Broker only — ignored for dealers |
| `per_page` | integer | Records per page (1–100, default 15) | Both |
| `page` | integer | Page number | Both |

#### Role-based data access

| Role | Sees |
|------|------|
| `dealer` | Only orders where `dealer_management.user_id = authenticated user` |
| `broker` | Only orders where `order_management.broker_id = authenticated user` |

Implemented via `SalesScope::scopeOrders()` — the same method used by the web DataTable.

#### Sample request

```
GET /api/v1/orders?payment_status=unpaid&per_page=10&page=1
Authorization: Bearer {access_token}
Accept: application/json
```

#### Success response (200)

```json
{
  "success": true,
  "message": "Orders retrieved successfully.",
  "data": {
    "orders": [
      {
        "id": 42,
        "order_number": "ORD/2024-25/0042",
        "order_date": "2025-03-15",
        "broker": { "id": 5, "name": "Broker Name" },
        "brand":  { "id": 2, "name": "Brand Name" },
        "dealer": {
          "id": 7,
          "firm_shop_name": "Test Firm",
          "user_name": "Dealer User"
        },
        "delivery_address": "123 Delivery Street, City",
        "payment_status": "unpaid",
        "partial_paid_amount": null,
        "total_order_amount": "5000.00",
        "grand_total": "5000.00",
        "status": 1,
        "dispatch_summary": {
          "ordered_qty": 50,
          "dispatched_qty": 20,
          "pending_qty": 30,
          "dispatch_percent": 40,
          "pending_line_items": 1,
          "is_fully_dispatched": false
        },
        "items": [
          {
            "id": 101,
            "product": { "id": 9, "name": "Cottonseed Cake", "unit": "Bag" },
            "qty": 50,
            "unit_price": "100.00",
            "total_price": "5000.00",
            "dispatched_qty": 20,
            "pending_qty": 30,
            "dispatch_status": "partial",
            "dispatches": [
              {
                "id": 201,
                "no_of_bags": 20,
                "dispatch_date": "2025-03-20",
                "payment_status": "unpaid"
              }
            ]
          }
        ],
        "created_at": "2025-03-15 10:30:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 10,
      "total": 42,
      "last_page": 5,
      "next_page_url": "http://example.com/api/v1/orders?page=2",
      "prev_page_url": null
    }
  }
}
```

#### `dispatch_status` values per item

| Value | Meaning |
|-------|---------|
| `not_dispatched` | No dispatch records for this item yet |
| `partial` | Some bags dispatched, some pending |
| `fully_dispatched` | All ordered bags have been dispatched |

#### Error responses

| Code | Scenario | `message` |
|------|----------|-----------|
| `401` | Token missing, invalid, or revoked | `Unauthenticated.` |
| `403` | Valid token but user is not dealer/broker | `Access denied. This endpoint is restricted to Dealer and Broker accounts.` |
| `422` | Invalid filter parameter (bad date, wrong enum) | `Validation failed.` + field errors |

#### Performance notes

- All related records (broker, brand, dealer, items, dispatches) are eager-loaded in a single set of queries to avoid N+1 issues.
- Column selection is minimized on eager-loaded relations to reduce memory footprint.
- Pagination default is 15 records; maximum is 100.

#### Postman (XAMPP)

```
GET http://localhost/Mayank_Cattle_Food_CRM/public/api/v1/orders
Authorization: Bearer {{access_token}}
Accept: application/json
```

#### Android integration

```kotlin
@GET("api/v1/orders")
suspend fun getOrders(
    @Header("Authorization") token: String,
    @Query("payment_status") paymentStatus: String? = null,
    @Query("date_from") dateFrom: String? = null,
    @Query("date_to") dateTo: String? = null,
    @Query("per_page") perPage: Int = 15,
    @Query("page") page: Int = 1
): OrderListResponse
```

#### iOS integration

```swift
var components = URLComponents(string: "\(baseURL)/api/v1/orders")!
components.queryItems = [
    URLQueryItem(name: "per_page", value: "15"),
    URLQueryItem(name: "page", value: "1"),
]
var request = URLRequest(url: components.url!)
request.setValue("Bearer \(savedToken)", forHTTPHeaderField: "Authorization")
request.setValue("application/json", forHTTPHeaderField: "Accept")
```

---

### API: Dispatches — Dispatch Listing (API 9)

**`GET /api/v1/dispatches`**

Returns a paginated, filterable list of dispatch records for the authenticated Dealer or Broker user.  
Role-based visibility mirrors `DispatchManagementController::index()` using `SalesScope::scopeDispatches()`.

Each dispatch record includes the parent order (with broker, brand, dealer), the dispatched product (with unit price),
the transporter details, and **order-item quantity context** (`ordered_qty`, `total_dispatched_qty`, `pending_qty`)
so the mobile app can display fulfilment progress without additional API calls.

#### Authentication

`Authorization: Bearer {sanctum_token}` (required)

#### Role access

| Role | Sees |
|------|------|
| `dealer` | Dispatches for orders where `dealer_management.user_id = auth user id` |
| `broker` | Dispatches for orders where `broker_id = auth user id` |
| Any other role | `403 Access denied` |

#### Query parameters (all optional)

| Parameter | Type | Description |
|-----------|------|-------------|
| `order_number` | string | Partial match on parent order's `unique_order_id` |
| `status` | string | Payment status: `unpaid` \| `paid` \| `partial` |
| `date_from` | date (Y-m-d) | Dispatch date lower bound (inclusive) |
| `date_to` | date (Y-m-d) | Dispatch date upper bound (inclusive) |
| `brand_id` | integer | Filter by brand (Broker only; silently ignored for Dealers) |
| `product_id` | integer | Filter by dispatched product |
| `dealer_id` | integer | Filter by dealer (Broker only; silently ignored for Dealers) |
| `per_page` | integer (1–100) | Records per page; default `15` |
| `page` | integer | Page number; default `1` |

#### Success response — 200

```json
{
  "success": true,
  "message": "Dispatches retrieved successfully.",
  "data": {
    "dispatches": [
      {
        "id": 12,
        "dispatch_number": "DISP-000012",
        "dispatch_date": "2025-03-20",
        "no_of_bags": 5,
        "ordered_qty": 10,
        "total_dispatched_qty": 7,
        "pending_qty": 3,
        "is_item_complete": false,
        "payment_status": "paid",
        "partial_paid_amount": null,
        "accrued_late_fee": "0.00",
        "late_fee_last_accrued_on": null,
        "truck_number": "GJ01AB1234",
        "driver_contact": "9999999999",
        "order": {
          "id": 3,
          "order_number": "ORD/2025/0010",
          "order_date": "2025-03-15",
          "broker": { "id": 4, "name": "Broker Name" },
          "brand": { "id": 1, "name": "Brand A" },
          "dealer": {
            "id": 2,
            "firm_shop_name": "Test Firm",
            "user_name": "John Doe"
          }
        },
        "product": {
          "id": 7,
          "name": "Product X",
          "unit": "Bag",
          "unit_price": "100.00"
        },
        "transporter": {
          "id": 5,
          "name": "Transport Co",
          "phone_no": "8888888888"
        },
        "created_at": "2025-03-20 10:00:00",
        "updated_at": "2025-03-21 14:30:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 1,
      "last_page": 1,
      "next_page_url": null,
      "prev_page_url": null
    }
  }
}
```

#### Response field reference

| Field | Type | Description |
|-------|------|-------------|
| `dispatch_number` | string | Human-readable dispatch reference (`DISP-XXXXXX` padded to 6 digits) |
| `no_of_bags` | integer | Bags dispatched in **this specific dispatch event** |
| `ordered_qty` | integer | Total bags ordered for the parent order item |
| `total_dispatched_qty` | integer | Sum of `no_of_bags` across **all dispatch events** for the same order item |
| `pending_qty` | integer | Bags still outstanding: `ordered_qty − total_dispatched_qty` |
| `is_item_complete` | boolean | `true` when `total_dispatched_qty >= ordered_qty` |
| `payment_status` | string | Payment state of this dispatch: `unpaid` / `paid` / `partial` |
| `accrued_late_fee` | string | Late fee accrued on this dispatch (decimal string) |
| `order.broker` | object\|null | Broker who placed/manages the parent order |
| `product.unit_price` | string | Unit price per bag at time of order (from order item) |

#### `payment_status` values

| Value | Integer stored in DB | Meaning |
|-------|----------------------|---------|
| `unpaid` | 0 | Dispatch payment not yet received |
| `paid` | 1 | Dispatch fully paid |
| `partial` | 2 | Partial payment received (`partial_paid_amount` is set) |

#### Error responses

| Code | Scenario | `message` |
|------|----------|-----------|
| `401` | Token missing, invalid, or revoked | `Unauthenticated.` |
| `403` | Valid token but user is not dealer/broker | `Access denied. This endpoint is restricted to Dealer and Broker accounts.` |
| `422` | Invalid filter parameter (bad date, wrong enum) | `Validation failed.` + field errors in `data` |

#### Performance notes

- All relations (`order`, `order.brand`, `order.dealer`, `order.broker`, `product`, `transporter`, `orderItem`) are eager-loaded in a single batch to avoid N+1 queries.
- `orderItem` is loaded with `withSum('dispatches','no_of_bags')` — a single subquery per item computes `total_dispatched_qty` without loading every dispatch record.
- `brand_id` and `dealer_id` filters are silently ignored for Dealer accounts (SalesScope already limits their view).
- Pagination default is 15 records; maximum is 100.

#### Postman (XAMPP)

```
GET http://localhost/Mayank_Cattle_Food_CRM/public/api/v1/dispatches
Authorization: Bearer {{access_token}}
Accept: application/json
```

#### Android integration

```kotlin
@GET("api/v1/dispatches")
suspend fun getDispatches(
    @Header("Authorization") token: String,
    @Query("status") status: String? = null,
    @Query("date_from") dateFrom: String? = null,
    @Query("date_to") dateTo: String? = null,
    @Query("product_id") productId: Int? = null,
    @Query("per_page") perPage: Int = 15,
    @Query("page") page: Int = 1
): DispatchListResponse
```

#### iOS integration

```swift
var components = URLComponents(string: "\(baseURL)/api/v1/dispatches")!
components.queryItems = [
    URLQueryItem(name: "per_page", value: "15"),
    URLQueryItem(name: "page", value: "1"),
]
var request = URLRequest(url: components.url!)
request.setValue("Bearer \(savedToken)", forHTTPHeaderField: "Authorization")
request.setValue("application/json", forHTTPHeaderField: "Accept")
```

---

## Bearer Token Usage

Protected endpoints require:

```http
Authorization: Bearer {access_token}
Accept: application/json
```

### cURL example

```bash
curl -X GET "http://127.0.0.1:8000/api/v1/profile" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer 1|your-token-here"
```

---

## Postman Testing

### 1. Collection variables

| Variable | Example value |
|----------|---------------|
| `base_url` | `http://localhost/Mayank_Cattle_Food_CRM/public` |
| `access_token` | *(empty — set after login)* |

### 2. Test health check first

| Setting | Value |
|---------|-------|
| Method | `GET` |
| URL | `{{base_url}}/api/v1/system/health-check` |

Expect `200` and `"status": "ok"`.

### 3. Login request

| Setting | Value |
|---------|-------|
| Method | `POST` |
| URL | `{{base_url}}/api/v1/auth/login` |
| Headers | `Accept: application/json`, `Content-Type: application/json` |
| Body | Raw → JSON (see samples above) |

### 4. Auto-save token (Tests tab)

```javascript
if (pm.response.code === 200) {
    const json = pm.response.json();
    pm.collectionVariables.set('access_token', json.data.access_token);
}
```

### 5. Authenticated requests (future)

```
Authorization: Bearer {{access_token}}
```

---

## Android Integration

```kotlin
// POST /api/v1/auth/login
data class LoginRequest(
    val login: String,
    val password: String,
    val device_name: String = "Android"
)

@POST("api/v1/auth/login")
suspend fun login(@Body body: LoginRequest): LoginResponse
```

- Store `data.access_token` in **EncryptedSharedPreferences** or **Keystore**.
- Add `Authorization: Bearer {token}` via an OkHttp interceptor.
- On **401**, clear token and show login screen.

---

## iOS Integration

```swift
let url = URL(string: "\(baseURL)/api/v1/auth/login")!
var request = URLRequest(url: url)
request.httpMethod = "POST"
request.setValue("application/json", forHTTPHeaderField: "Content-Type")
request.setValue("application/json", forHTTPHeaderField: "Accept")
```

- Store token in **Keychain**.
- Set `Authorization: Bearer {token}` on protected calls.
- On **401**, clear Keychain and present login UI.

---

## Security Considerations

| Topic | Implementation |
|-------|----------------|
| Passwords | Bcrypt (`Hash::check`) |
| API tokens | Sanctum — hashed in `personal_access_tokens` |
| Login rate limit | 5/min per login + IP (`throttle:api-login`) |
| Inactive users | Blocked in `LoginService::userCanAuthenticate()` |
| Deleted users | Blocked when `deleted_at` is set |
| Error messages | Generic 401 text (no user enumeration) |
| Role restriction | `/auth/me` blocks non-dealer/broker roles with 403 |
| Token revocation | Deleted tokens return 401 on next request |
| Web isolation | Mobile API does not touch sessions or OTP |
| Production | **HTTPS required** |

### Production recommendations

- Enforce HTTPS
- Configure token expiration in `config/sanctum.php` if needed
- Add `auth/logout` to revoke tokens on sign-out
- Monitor failed login attempts
- Consider certificate pinning on mobile clients

---

## Project Structure & Code Flow

```
app/
├── Exceptions/Api/
│   └── OtpException.php                       # Typed OTP error constants
├── Http/
│   ├── Controllers/
│   │   └── Api/V1/
│   │       ├── Auth/
│   │       │   ├── LoginController.php        # POST login → 202 (email) or 200 (phone)
│   │       │   ├── OtpController.php          # POST otp/verify, POST otp/resend
│   │       │   ├── ForgotPasswordController.php # POST forgot-password
│   │       │   └── AuthCheckController.php    # GET auth/me (Dealer/Broker only)
│   │       ├── Orders/
│   │       │   └── OrderController.php        # GET orders (paginated, filtered)
│   │       ├── Dispatches/
│   │       │   └── DispatchController.php     # GET dispatches (paginated, filtered)
│   │       └── System/
│   │           └── HealthCheckController.php  # GET health-check
│   ├── Requests/Api/
│   │   ├── ApiFormRequest.php                 # 422 JSON validation errors
│   │   ├── V1/Auth/
│   │       ├── LoginRequest.php               # login + password rules
│   │       ├── VerifyOtpRequest.php           # otp_token + otp rules
│   │       ├── ResendOtpRequest.php           # otp_token rule
│   │       └── ForgotPasswordRequest.php      # email rule
│   │   ├── V1/Orders/
│   │   │   └── OrderListRequest.php           # filter + pagination validation
│   │   └── V1/Dispatches/
│   │       └── DispatchListRequest.php        # filter + pagination validation
│   └── Resources/Api/V1/
│       ├── UserResource.php                   # Safe user JSON shape
│       ├── OrderResource.php                  # Order + dispatch summary
│       ├── OrderItemResource.php              # Line item + dispatch records
│       └── DispatchResource.php               # Dispatch record + order/product/transporter
├── Mail/Api/
│   └── MobileOtpMail.php                      # Queued OTP email (ShouldQueue)
├── Models/
│   └── MobileOtp.php                          # OTP session row
├── Services/Api/V1/Auth/
│   ├── LoginService.php                       # Resolve user, verify password
│   └── OtpService.php                         # Create/send/verify/resend OTP
└── Support/Api/
    └── ApiResponse.php                        # success() / error() envelope

config/
└── otp.php                                    # expiry_minutes, max_attempts, cooldowns

database/migrations/
└── 2026_07_01_000001_create_mobile_otps_table.php

resources/views/emails/api/
└── mobile_otp.blade.php                       # OTP email template

routes/
└── api.php                                    # v1 route groups by prefix

# Web-only (NOT mobile API):
app/Http/Controllers/
├── Auth/AuthenticatedSessionController.php    # Web login → OTP or session
└── OtpController.php                          # Web OTP verify & resend
```

### Request lifecycle (email login)

1. **`routes/api.php`** — routes to `LoginController@store` with `throttle:api-login`.
2. **`LoginRequest`** — validates format; fails → `ApiResponse::error` (422).
3. **`LoginService`** — DB lookup, status check, password verify.
4. **`LoginController`** — email path: `OtpService::createAndSend()` → 202; phone path: `createToken()` → 200.
5. Mobile stores `otp_token`, submits to `POST /auth/otp/verify`.
6. **`OtpService::verify()`** — decrypts token, checks OTP row, plain-text compare → sets `used_at`.
7. **`OtpController::verify()`** — issues Sanctum token → `UserResource` → 200.

---

## Future Endpoints

| Prefix | Endpoint | Method | Auth | Description |
|--------|----------|--------|------|-------------|
| `auth` | `/api/v1/auth/logout` | POST | Bearer | Revoke current token |
| `auth` | `/api/v1/auth/reset-password` | POST | Public | Reset password with token from email |
| `profile` | `/api/v1/profile` | GET | Bearer | Get profile |
| `profile` | `/api/v1/profile` | PUT | Bearer | Update profile |
| `profile` | `/api/v1/profile/password` | PUT | Bearer | Change password |
| `notifications` | `/api/v1/notifications` | GET | Bearer | List notifications |
| `training` | `/api/v1/training/*` | TBD | Bearer | Training module |

### Versioning policy

- Non-breaking changes stay in **v1**.
- Breaking changes → new prefix `/api/v2` with overlap period documented in release notes.

---

## Migrations & Support

### Run migrations

```bash
php artisan migrate
```

Creates `personal_access_tokens` (required for Sanctum).

### Reporting issues

Include:

- Full URL and HTTP method
- Request body (**omit password**)
- Response status and JSON body
- Platform (Android / iOS) and app version

---

*Last updated: Mobile API v1 — auth/login, auth/otp/verify, auth/otp/resend, auth/forgot-password, auth/me (dealer/broker check), orders (paginated listing), dispatches (dispatch listing with quantity context, API 9), system/health-check*
