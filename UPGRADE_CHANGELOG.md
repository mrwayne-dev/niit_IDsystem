# NIIT Digital ID System — Full Upgrade Changelog

**Date:** April 13, 2026  
**Scope:** Production-readiness upgrade — security hardening, authentication, feature enhancements, frontend polish  
**Total files changed:** 12 modified · 20 created

---

## Table of Contents

1. [Project Overview (Before)](#1-project-overview-before)
2. [Audit Findings](#2-audit-findings)
3. [Pre-Phase: Foundations](#3-pre-phase-foundations)
4. [Phase 1: Security Hardening](#4-phase-1-security-hardening)
5. [Phase 2: Admin Authentication](#5-phase-2-admin-authentication)
6. [Phase 3: Database & API Hardening](#6-phase-3-database--api-hardening)
7. [Phase 4: Feature Enhancements](#7-phase-4-feature-enhancements)
8. [Phase 5: Frontend Polish](#8-phase-5-frontend-polish)
9. [Complete File Manifest](#9-complete-file-manifest)
10. [Remaining Manual Steps](#10-remaining-manual-steps)
11. [Verification Checklist](#11-verification-checklist)

---

## 1. Project Overview (Before)

The NIIT Digital ID System was a functional PHP/vanilla-JS web app with the following architecture:

| Layer | Technology |
|---|---|
| Language | PHP (no framework) |
| Database | MySQL via PDO |
| Frontend | Vanilla JavaScript (fetch API) |
| Styling | Bootstrap 5.0.2 (local) + custom CSS |
| PDF Generation | FPDF 1.86 (embedded) |
| Image Processing | PHP GD library |
| Web Server | Apache with `.htaccess` routing |

**Pages:**
- `index.php` — Admin creates student ID cards with live card preview
- `verify.php` — Students verify their ID by entering name + student ID

**API endpoints:**
- `backend/api/create_id.php` — Saves student record and uploads photo/signature
- `backend/api/verify_id.php` — Looks up student by name + ID
- `backend/api/download.php` — Generates front+back PNG images and assembles into PDF

---

## 2. Audit Findings

### Critical Issues (Before This Upgrade)

| # | Severity | Issue | File(s) |
|---|---|---|---|
| 1 | **CRITICAL** | Database credentials hardcoded in plain text (`root` / empty password) | `backend/config/database.php` |
| 2 | **CRITICAL** | No authentication — any user could create/modify student IDs | All pages |
| 3 | **CRITICAL** | No CSRF protection on any form or API endpoint | All forms + APIs |
| 4 | **HIGH** | File uploads only checked extension, not MIME type (polyglot attack possible) | `create_id.php` |
| 5 | **HIGH** | Uploaded files stored inside webroot with predictable `prefix_timestamp` names | `constants.php` |
| 6 | **HIGH** | Database error messages exposed directly to the client (leaks schema info) | All API files |
| 7 | **HIGH** | No HTTP security headers (CSP, HSTS, X-Frame-Options, etc.) | Everywhere |
| 8 | **HIGH** | No session management anywhere in the codebase | Everywhere |
| 9 | **MEDIUM** | `sql/` and `backend/config/` directories accessible via browser | `.htaccess` |
| 10 | **MEDIUM** | `htmlspecialchars()` applied at INSERT time, not output time (double-encoding bug) | `create_id.php` |
| 11 | **MEDIUM** | `@` error suppression used on all GD image functions | `download.php` |
| 12 | **MEDIUM** | No server-side input validation beyond empty checks | `create_id.php` |
| 13 | **MEDIUM** | Expired ID cards verified as valid (no expiry check) | `verify_id.php` |
| 14 | **MEDIUM** | `temp_pdfs/` never cleaned up (unbounded disk growth) | `download.php` |
| 15 | **MEDIUM** | No rate limiting on any API endpoint | All APIs |
| 16 | **LOW** | Meta tags referenced `healthruncare.com` (copy-paste artifact) | `index.php`, `verify.php` |
| 17 | **LOW** | `other_names` column exists in DB but had no form field | `index.php` |
| 18 | **LOW** | No `updated_at` timestamp in students table | `sql/db.sql` |
| 19 | **LOW** | No dependency management (no Composer, libraries bundled manually) | Whole project |
| 20 | **LOW** | Directory listing enabled on `uploads/` and `temp_pdfs/` | No `.htaccess` in subdirs |

---

## 3. Pre-Phase: Foundations

### `composer.json` — NEW FILE

Added Composer dependency management. Installs:
- `vlucas/phpdotenv ^5.6` — secure `.env` file loading
- `endroid/qr-code ^5.0` — QR code generation for ID card backs

```json
{
  "require": {
    "php": ">=8.0",
    "vlucas/phpdotenv": "^5.6",
    "endroid/qr-code": "^5.0"
  }
}
```

### `.env` — NEW FILE

Moves all sensitive configuration out of source code:

```
DB_HOST=localhost
DB_NAME=niit_digitalID
DB_USER=root
DB_PASS=
APP_ENV=production
SESSION_SECRET=<64-byte random hex>
```

### `.env.example` — NEW FILE

Committed template showing what variables are required, without actual values.

### `.gitignore` — NEW FILE

Prevents secrets and generated files from being committed:

```
.env
vendor/
temp_pdfs/*.pdf
assets/uploads/*
!assets/uploads/.gitkeep
private_uploads/
```

---

## 4. Phase 1: Security Hardening

### `backend/config/database.php` — MODIFIED

**Before:** 18 lines of hardcoded credentials. Exposed PDO error message to the browser on connection failure.

**After:**
- Loads credentials from `.env` via `vlucas/phpdotenv`
- `notEmpty()` validation on required variables (DB_HOST, DB_NAME, DB_USER)
- DB_PASS allowed to be empty (passwordless dev DBs are valid)
- Connection failure: logs to `error_log`, returns generic JSON `503` — never exposes error to client
- Added `PDO::ATTR_EMULATE_PREPARES => false` for true prepared statements

---

### `backend/config/security.php` — NEW FILE

Single include that emits all HTTP security headers. Added to the top of every API file and both HTML pages:

| Header | Value / Purpose |
|---|---|
| `X-Content-Type-Options` | `nosniff` — prevents MIME sniffing |
| `X-Frame-Options` | `DENY` — prevents clickjacking |
| `X-XSS-Protection` | `1; mode=block` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Permissions-Policy` | Disables camera, mic, geolocation |
| `Content-Security-Policy` | Restricts scripts to self + Iconify CDN |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` (HTTPS enforced) |

---

### `backend/config/csrf.php` — NEW FILE

Full CSRF protection system:

- Starts session with `httponly`, `samesite=Strict`, `secure=true` cookie flags
- `generate_csrf_token()` — creates a 32-byte cryptographically random token per session
- `verify_csrf_token()` — timing-safe `hash_equals()` comparison, also accepts `X-CSRF-Token` header for JS requests

Used in:
- `index.php` and `verify.php` inject `<input type="hidden" name="csrf_token">` into all forms
- All three API files verify the token at the top of every POST handler
- `create.js` and `verify.js` append the token to any manually-built `FormData` objects

---

### `backend/config/rate_limit.php` — NEW FILE

Session-based rate limiting without requiring Redis:

```
verify_id.php  → max 5 requests per 60 seconds per IP
create_id.php  → max 20 requests per 60 seconds per IP
admin login    → max 5 attempts per 300 seconds per IP
```

Returns HTTP `429 Too Many Requests` with a `Retry-After` header when exceeded.

---

### `backend/config/constants.php` — MODIFIED

**Before:** `UPLOAD_DIR` pointed to `assets/uploads/` (inside webroot). Directories created with `0777` permissions.

**After:**
- `UPLOAD_DIR` now points to `../private_uploads/` — **one level above the webroot**, making uploaded files completely inaccessible via browser URL
- Directory creation permissions changed from `0777` → `0750`

---

### `backend/api/create_id.php` — MODIFIED (major rewrite)

**Security fixes:**
1. Added `security.php`, `csrf.php`, `rate_limit.php` includes
2. CSRF token verified before any processing
3. Rate limit checked
4. **File upload — MIME type validation added:**
   ```php
   $finfo = new finfo(FILEINFO_MIME_TYPE);
   $mime  = $finfo->file($file['tmp_name']);
   if (!in_array($mime, ['image/jpeg', 'image/png'])) {
       throw new Exception("File content does not match an allowed image type.");
   }
   ```
5. **Filenames changed from predictable to cryptographically random:**
   - Before: `photo_NIIT123_1702394821.jpg` (student ID + Unix timestamp = guessable)
   - After: `a3f7c8d2e1b4...` (32 hex chars from `bin2hex(random_bytes(16))`)
6. DB now stores only the **filename** (not the full path), since path is derived server-side
7. **Removed all `htmlspecialchars()` calls from INSERT data** — PDO prepared statements prevent SQL injection; HTML-escaping at DB time caused double-encoding and corrupted names like `O'Brien` → `O&#039;Brien`
8. PDO errors no longer exposed to client — logged privately via `error_log()`

**Validation fixes:**
- Replaced bare `empty()` loop with a full rules-based validator:
  - Per-field max length enforcement (100 chars for names, 50 for codes, etc.)
  - Regex pattern validation for names (`/^[A-Za-z\s\-']+$/`) and student ID (`/^[A-Z0-9\-]+$/i`)
  - Proper date format validation using `DateTime::createFromFormat()`
- Added `other_names` field (was in DB schema but missing from the form and API)

---

### `backend/api/verify_id.php` — MODIFIED (major rewrite)

**Security fixes:**
1. Added security headers, CSRF verification, rate limiting
2. PDO errors suppressed from client response

**Feature changes:**
- SELECT query now returns full student row (name, course, semester, batch, expiry)
- **Expiry date check added:**
  ```php
  $isExpired = new DateTimeImmutable($student['expiry_date']) < new DateTimeImmutable('today');
  ```
- Response now includes `is_expired` flag and a full `student` object:
  ```json
  {
    "success": true,
    "is_expired": false,
    "message": "Student Verified Successfully.",
    "student_id": "NIIT12345",
    "student": {
      "student_id": "NIIT12345",
      "full_name": "John Doe",
      "course": "Software Engineering",
      "semester_code": "SEM-2025A",
      "batch_code": "BCH-21",
      "expiry_date": "2027-12-31",
      "is_expired": false
    }
  }
  ```
- All values in the `student` object are HTML-escaped at output time using `htmlspecialchars(ENT_QUOTES, 'UTF-8')`

---

### `backend/api/download.php` — MODIFIED (major rewrite)

**Security fixes:**
1. Added security headers and CSRF verification
2. PDO errors suppressed from client
3. **File path derivation fixed** — now uses `UPLOAD_DIR . DIRECTORY_SEPARATOR . basename($student['photo'])` instead of reconstructing from the old webroot path

**Code quality fixes:**
- **Removed all `@` error suppression** from GD image functions. Replaced with a `safeImageCreate()` helper that uses `try/catch` and `error_log()`:
  ```php
  function safeImageCreate(string $path): GdImage|false {
      try {
          return match(strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
              'png'         => imagecreatefrompng($path),
              'jpg', 'jpeg' => imagecreatefromjpeg($path),
              default       => false,
          };
      } catch (Throwable $e) {
          error_log("GD image load error [{$path}]: " . $e->getMessage());
          return false;
      }
  }
  ```
- Added **probabilistic temp PDF cleanup** (10% of requests trigger a scan/delete of PDFs older than 1 hour)
- Added **QR code generation** on the back of the ID card (see Phase 4)

---

### `backend/api/serve_image.php` — NEW FILE

Authenticated image proxy for uploaded files (now stored outside webroot):
- Requires admin session via `require_admin_auth()`
- Validates filename against strict regex: `/^[a-f0-9]{32}\.(jpg|jpeg|png)$/i`
- Re-validates MIME type before serving
- Sets `Cache-Control: private, max-age=3600`
- Returns correct `Content-Type` and `Content-Length` headers

---

### `assets/uploads/.htaccess` — NEW FILE

```apache
php_flag engine off
<FilesMatch "\.php$">
    Require all denied
</FilesMatch>
Options -Indexes
```

Prevents PHP execution and directory listing in the uploads folder (defence-in-depth, even though uploads are now outside webroot).

### `temp_pdfs/.htaccess` — NEW FILE

```apache
Options -Indexes
```

Disables directory listing on the temp PDF storage folder.

---

### `.htaccess` (root) — MODIFIED

**Added at the top (before all other rules):**

```apache
# Block direct access to sensitive paths
RewriteRule ^sql(/|$)               - [F,L]
RewriteRule ^backend/config(/|$)    - [F,L]
RewriteRule ^\.env$                 - [F,L]
RewriteRule ^\.env\.example$        - [F,L]
RewriteRule ^composer\.(json|lock)$ - [F,L]
RewriteRule ^vendor(/|$)            - [F,L]

# Admin routes
RewriteRule ^admin/login/?$      admin/login.php      [L]
RewriteRule ^admin/dashboard/?$  admin/dashboard.php  [L]
RewriteRule ^admin/logout/?$     admin/logout.php     [L]
```

**Removed** the blanket `sql` pass-through from the old allow-list rule.

---

## 5. Phase 2: Admin Authentication

### `backend/config/auth.php` — NEW FILE

Core authentication helpers:

| Function | Behaviour |
|---|---|
| `require_admin_auth()` | Redirects to `/admin/login` if no session. Regenerates session ID every 5 minutes to prevent fixation. |
| `admin_login($pdo, $username, $password)` | Fetches admin row, verifies Argon2ID hash via `password_verify()`, regenerates session ID on success, updates `last_login` |
| `admin_logout()` | Clears `$_SESSION`, expires the cookie, destroys the session, redirects to login |

---

### `sql/002_admin_auth.sql` — NEW FILE

Creates the `admins` table:

```sql
CREATE TABLE IF NOT EXISTS admins (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(80)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login    TIMESTAMP NULL
);
```

Inserts a default admin: `username=admin`, `password=niit@admin2025` (Argon2ID hash).  
**Change this password immediately after first login.**

---

### `admin/login.php` — NEW FILE

Bootstrap-styled login page:
- Redirects already-authenticated admins to dashboard
- Rate-limited: 5 attempts per 5 minutes
- CSRF-protected form
- Generic error message (`"Invalid credentials."`) — never reveals whether username or password was wrong
- `usleep(random_int(100000, 300000))` delay on failure to blunt brute-force timing attacks

---

### `admin/dashboard.php` — NEW FILE

Protected admin dashboard:
- Requires active admin session
- Shows 3 stat cards: Total Students, Active IDs, Expired IDs
- Paginated student table (20 per page): Student ID, Name, Course, Expiry Date, Status badge, Created date
- Live search by name or student ID (GET parameter `?q=`)
- Status badges: green `Active` / red `Expired` based on `expiry_date < CURDATE()`
- Navigation: Create ID, Verify Page (opens in new tab), Logout

---

### `admin/logout.php` — NEW FILE

One-liner that calls `admin_logout()` — clears session and redirects to `/admin/login`.

---

### `index.php` — MODIFIED (PHP auth gate added)

**Before:** Pure HTML, no server-side code.

**After:** PHP block at the top:
```php
require_once __DIR__ . '/backend/config/security.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/backend/config/database.php';
require_once __DIR__ . '/backend/config/auth.php';
require_admin_auth();
$csrfToken = generate_csrf_token();
```

Unauthenticated requests are redirected to `/admin/login`. The CSRF token is injected as a hidden field in the form.

---

### `verify.php` — MODIFIED (PHP session + CSRF added)

**Before:** Pure HTML, no server-side code.

**After:** PHP block at the top loads `security.php` and `csrf.php`, generates and injects the CSRF token. Verify page remains **publicly accessible** (students don't need to log in to verify their card).

---

## 6. Phase 3: Database & API Hardening

### `sql/003_add_updated_at.sql` — NEW FILE

Two schema improvements:

```sql
ALTER TABLE students
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP;

ALTER TABLE students
    MODIFY COLUMN other_names VARCHAR(150) NULL DEFAULT NULL;
```

MySQL's `ON UPDATE CURRENT_TIMESTAMP` maintains `updated_at` automatically — no PHP changes required.

---

### Input Validation (in `create_id.php`)

Replaced the bare `empty()` loop with a rules engine:

| Field | Required | Max Length | Pattern |
|---|---|---|---|
| `first_name` | Yes | 100 | `/^[A-Za-z\s\-']+$/` |
| `last_name` | Yes | 100 | `/^[A-Za-z\s\-']+$/` |
| `other_names` | No | 150 | — |
| `student_id` | Yes | 50 | `/^[A-Z0-9\-]+$/i` |
| `semester_code` | Yes | 50 | — |
| `batch_code` | Yes | 50 | — |
| `course` | Yes | 150 | — |
| `duration` | Yes | 100 | — |
| `expiry_date` | Yes | — | Valid `Y-m-d` date |

---

### `backend/cron/cleanup_pdfs.php` — NEW FILE

Deletes any file in `temp_pdfs/` older than 1 hour. Outputs a log line on each run:

```
2026-04-13 14:00:01 | Cleanup: deleted=3 errors=0
```

Register with cron:
```
0 * * * * php /path/to/backend/cron/cleanup_pdfs.php >> /var/log/niit_cleanup.log 2>&1
```

Also wired into `download.php` probabilistically (10% of PDF requests trigger inline cleanup).

---

### `migrate.php` — NEW FILE (delete after use)

A one-shot PHP script that runs all DB migrations via PDO (no MySQL CLI access required):
- Creates `admins` table
- Inserts default admin user
- Adds `updated_at` column
- Adds `other_names` column if missing
- Safe to run multiple times (checks before altering)

**Run:** `php migrate.php`  
**Then delete:** `rm migrate.php`

---

## 7. Phase 4: Feature Enhancements

### QR Code on Back of ID Card (`download.php`)

Uses `endroid/qr-code` (installed via Composer) to generate a scannable QR code on the back of every printed ID card.

- QR content links to the verify page with the student ID pre-filled: `https://yourdomain.com/verify?id=NIIT12345`
- Rendered as a 200×200px PNG, composited onto the back card image via GD
- Temp QR PNG file is deleted immediately after compositing
- Falls back gracefully if QR generation fails (error logged, card still generates)
- QR placeholder icon (Iconify `mdi:qrcode`) added to the back-card preview in `index.php`

---

### Rich Verification Modal (`verify.php` + `verify.js` + `style.css`)

**Before:** Modal showed only "Verification Successful!" text + download button.

**After:** Modal is fully populated with data from the API response:

**New HTML elements added to modal:**
- `#modal-expiry-badge` — coloured pill badge showing expiry status
- `#modal-student-details` — 5-row grid showing Name, Student ID, Course, Semester, Batch

**Expiry badge states:**
- Green: `Valid until: 31 December 2027`
- Red: `Expired: 30 June 2024`

**Expired card behaviour:**
- Modal title changes to `"ID Card Expired"` in red
- Modal icon switches to `mdi:alert-circle`
- Subtext changes to `"This ID card has expired. Please contact NIIT to renew."`
- Download button is **disabled** for expired cards

**`verify.js` additions:**
- `populateModal(result)` function fills all modal elements from the API `student` object
- `formatDate(dateStr)` helper formats `2027-12-31` → `31 December 2027`
- Backdrop click closes the modal
- Escape key closes the modal

---

## 8. Phase 5: Frontend Polish

### Dark Mode (`style.css` + `ui.js` + both HTML pages)

A persistent dark mode toggle fixed to the top-right corner of every page.

**CSS variables overridden in dark mode:**

| Variable | Light | Dark |
|---|---|---|
| `--Primary` | `#0B73CF` | `#4da6ff` |
| `--Primary-Dark` | `#084B95` | `#2979d4` |
| `--White` | `#ffffff` | `#1e1e2e` |
| `--GrayDark` | `#4A4F55` | `#b0bec5` |
| `--Border` | `#d1d5db` | `#2e2e3e` |
| `background` | white | `#121212` |

**How it works:** `initDarkMode()` in `ui.js` reads `localStorage.getItem('niit-theme')` on page load and sets `data-theme="dark"` on `<html>`. The toggle switch saves the preference back to localStorage on change — persists across page reloads and sessions.

---

### PWA Support (`manifest.json` + `service-worker.js`)

**`manifest.json`:**
- App name: `NIIT ID System` / short: `NIIT ID`
- Start URL: `/verify` (the public-facing page)
- Display: `standalone` (no browser chrome when installed)
- Theme colour: `#0B73CF`

**`service-worker.js`:**
- On install: pre-caches static assets (CSS, JS, placeholder image)
- On fetch:
  - **API calls** (`/backend/api/`) → Network-first, returns JSON error if offline
  - **Static assets** → Cache-first, falls back to network
- On activate: removes stale cache versions

**HTML additions to `index.php` and `verify.php`:**
```html
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#0B73CF">
<meta name="apple-mobile-web-app-capable" content="yes">
```

---

### UX Improvements

**`index.php`:**
- Added `other_names` optional field (was in DB, missing from form — students with middle names could not have them stored)
- File inputs now specify `accept="image/jpeg,image/png"` (browser-level filter, server still validates)
- Added Dashboard and Logout nav links above the submit button

**`assets/js/create.js`:**
- **Form reset after successful creation** — after the PDF downloads, the form clears and the live preview resets to defaults, ready for the next student. Previously required a manual page reload.
- Removed `console.error()` calls from catch blocks

**`verify.php` — Accessibility improvements:**
- Modal `<div>` now has `role="dialog"` `aria-modal="true"` `aria-labelledby="modal-title"`
- `aria-hidden="true/false"` toggled correctly when modal opens/closes
- Focus management: download button receives focus when modal opens; verify button receives focus when modal closes

**Both pages — Meta tags fixed:**
- Removed stale `healthruncare.com` meta description, author, and canonical URL
- Replaced with correct NIIT Port Harcourt content

---

## 9. Complete File Manifest

### New Files Created (20)

| File | Purpose |
|---|---|
| `composer.json` | Dependency management entry point |
| `composer.lock` | Locked dependency versions |
| `vendor/` | Composer packages (phpdotenv, endroid/qr-code + deps) |
| `.env` | Live environment variables (not committed) |
| `.env.example` | Committed template for `.env` |
| `.gitignore` | Excludes secrets and generated files from git |
| `backend/config/security.php` | HTTP security header middleware |
| `backend/config/csrf.php` | CSRF token generation and verification |
| `backend/config/auth.php` | Admin session management (login/logout/guard) |
| `backend/config/rate_limit.php` | Session-based per-action rate limiting |
| `backend/api/serve_image.php` | Auth-gated image proxy for private uploads |
| `backend/cron/cleanup_pdfs.php` | Hourly temp PDF cleanup script |
| `admin/login.php` | Admin login page (Bootstrap UI) |
| `admin/dashboard.php` | Admin student dashboard with search + pagination |
| `admin/logout.php` | Session destruction + redirect |
| `sql/002_admin_auth.sql` | Migration: `admins` table |
| `sql/003_add_updated_at.sql` | Migration: `updated_at` + `other_names` columns |
| `migrate.php` | One-time PHP migration runner (delete after use) |
| `assets/uploads/.htaccess` | Blocks PHP execution + directory listing in uploads |
| `temp_pdfs/.htaccess` | Blocks directory listing in temp PDF folder |
| `manifest.json` | PWA web app manifest |
| `service-worker.js` | PWA service worker (offline caching) |

### Modified Files (12)

| File | Summary of Changes |
|---|---|
| `.htaccess` | Blocks `sql/`, `backend/config/`, `.env`, `vendor/`; adds admin routes; removes old sql pass-through |
| `backend/config/database.php` | Loads credentials from `.env`; suppresses PDO errors from client |
| `backend/config/constants.php` | `UPLOAD_DIR` moved outside webroot; permissions `0777` → `0750` |
| `backend/api/create_id.php` | CSRF, rate limit, MIME validation, random filenames, rules-based validation, fixed htmlspecialchars, suppressed PDO errors |
| `backend/api/verify_id.php` | CSRF, rate limit, full student data + expiry in response, suppressed PDO errors |
| `backend/api/download.php` | CSRF, private upload paths, `safeImageCreate()`, QR code integration, probabilistic PDF cleanup, suppressed PDO errors |
| `index.php` | PHP auth gate, CSRF token, security headers, PWA meta, dark mode toggle, other_names field, fixed meta tags |
| `verify.php` | PHP CSRF setup, security headers, rich modal HTML (expiry badge + student details grid), PWA meta, dark mode toggle, accessibility attributes, fixed meta tags |
| `assets/js/create.js` | Appends CSRF token to download FormData; form + preview reset after success; removed console.error |
| `assets/js/verify.js` | `populateModal()`, `formatDate()`, expiry badge, student details grid, accessibility focus management, backdrop/Escape close, removed console.error |
| `assets/js/ui.js` | Added `initDarkMode()`, `registerServiceWorker()`, both called on `DOMContentLoaded` |
| `assets/css/style.css` | Added: expiry badge styles, student detail row styles, QR placeholder section, dark mode toggle switch, full dark mode CSS variable overrides |

---

## 10. Remaining Manual Steps

These four tasks require human action and cannot be automated:

### Step 1 — Run DB Migrations (Required before first use)

```bash
cd /path/to/niit_IDsystem
php migrate.php
```

Expected output:
```
✓ Connected to database: niit_digitalID
✓ Migration 002: Admin user created (username: admin, password: niit@admin2025)
✓ Migration 003: updated_at column added
✓ All migrations completed successfully!
```

**Then delete the file:** `rm migrate.php`

---

### Step 2 — Change Default Admin Password

1. Navigate to `/admin/login`
2. Log in with `admin` / `niit@admin2025`
3. Connect to MySQL and update:

```sql
UPDATE admins
SET password_hash = '<new_argon2id_hash>'
WHERE username = 'admin';
```

Generate a new hash: `php -r "echo password_hash('YourNewPassword', PASSWORD_ARGON2ID);"`

---

### Step 3 — Update QR Code Base URL

In `backend/api/download.php`, find and replace the placeholder domain:

```php
// Find this line (~line 100):
$qrContent = 'https://niit-ph.com/verify?id=' . urlencode($student['student_id']);

// Replace with your actual domain:
$qrContent = 'https://youractualdomain.com/verify?id=' . urlencode($student['student_id']);
```

---

### Step 4 — Register the PDF Cleanup Cron

```bash
crontab -e
```

Add:
```
0 * * * * php /full/path/to/niit_IDsystem/backend/cron/cleanup_pdfs.php >> /var/log/niit_cleanup.log 2>&1
```

---

## 11. Verification Checklist

Use this checklist to confirm the upgrade is working correctly end-to-end:

| # | Test | Expected Result |
|---|---|---|
| 1 | Visit `https://domain/sql/db.sql` | HTTP 403 Forbidden |
| 2 | Visit `https://domain/.env` | HTTP 403 Forbidden |
| 3 | Visit `https://domain/backend/config/database.php` | HTTP 403 Forbidden |
| 4 | POST to `create_id.php` without CSRF token | HTTP 403 + `"Invalid or expired request token."` |
| 5 | Upload a renamed `.php` file as a photo | Rejected with MIME type error |
| 6 | Visit `/` (create page) without being logged in | Redirect to `/admin/login` |
| 7 | Visit `/verify` without being logged in | Page loads normally (public) |
| 8 | Attempt admin login with wrong password 6× in 5 minutes | HTTP 429 `"Too many requests"` |
| 9 | Submit correct name + ID on verify page | Modal shows student name, course, semester, batch + green "Valid until" badge |
| 10 | Submit correct name + ID for an **expired** card | Modal shows red "Expired" badge, download button disabled |
| 11 | Full create flow: fill form, upload photo, click Generate | PDF downloads automatically; form resets for next student |
| 12 | Toggle dark mode switch | Page switches theme; refresh retains the choice |
| 13 | Visit `/verify` on mobile browser | "Add to Home Screen" / install prompt appears |
| 14 | Go offline and reload `/verify` | Page still loads from service worker cache |
| 15 | Scan QR code on printed ID card | Opens verify page in browser |

---

*Generated by Claude Code — Anthropic*  
*NIIT Port Harcourt Digital ID System — Production Upgrade*
