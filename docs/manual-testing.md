# Local manual testing

Use the isolated `emergency_sys_test` database. The regular application database in `.env` does not grant the app account schema-change privileges.

## Prepare the test database

```powershell
php artisan migrate --env=testing --database=mysql_migrate --force
php artisan db:seed --env=testing --force
php artisan db:seed --class=ManualTestingSeeder --env=testing --force
```

`RoleUserSeeder` creates or updates administrator and staff users during the standard seed run. In `local` and `testing`, it defaults to the demo accounts below; set `SEED_ADMIN_EMAIL` / `SEED_ADMIN_PASSWORD` and `SEED_STAFF_EMAIL` / `SEED_STAFF_PASSWORD` to override them. Outside those environments, set private credentials explicitly; passwords must be at least 12 characters. The separate `ManualTestingSeeder` also creates a synthetic report and starter message and is restricted to local/testing.

To create or update only the role accounts, run `php artisan db:seed --class=RoleUserSeeder` after setting the relevant `SEED_*` values in `.env`.

`ManualTestingSeeder` refuses to run outside `local` or `testing`. It creates sample staff/admin users, one clearly labeled synthetic report, a tracking link, and a starter message. It is safe to rerun.

## Start the local test server

```powershell
php -S 127.0.0.1:8765 -t public scripts/manual-test-router.php
```

The router loads `.env.testing`, uses file sessions so browser sign-in persists between requests, and refuses to serve if the configured database is not `emergency_sys_test`.

Open [http://127.0.0.1:8765](http://127.0.0.1:8765).

## Test accounts and sample data

Both accounts use password `ManualTest2026!`:

- Staff: `manual.staff@example.test`
- Admin: `manual.admin@example.test`
- Sample report: `MDR-2026-TEST01`
- Sample tracking link: [http://127.0.0.1:8765/track?reference=MDR-2026-TEST01&token=manual-testing-token-2026](http://127.0.0.1:8765/track?reference=MDR-2026-TEST01&token=manual-testing-token-2026)

## Available flows

- Public report submission, category loading, and the returned tracking link at `/report`.
- Three-step in-page report flow, optional browser location pin, camera-only photo capture, retry behavior, and copy/share confirmation actions.
- Tracking status, reporter messages, and staff replies through the sample tracking link.
- Tracking status timeline refresh, expiry countdown, and private photo access on the sample tracking link.
- Unified staff/admin login at `/login`; staff queue, polling, triage, and messaging at `/queue`.
- Shared dashboard and live map at `/dashboard` and `/live-map`; administrator queue and configuration remain under `/admin`.

This is a local test setup. The sample incident is fictional; do not submit a real emergency through it.
