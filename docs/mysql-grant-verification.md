# MySQL grant verification: Steps 8 and 9

This document records the least-privilege checks for the MDRRMO reporting
application. The setup procedure and grant statements are in
[`runbooks/bootstrap-mysql-user.md`](runbooks/bootstrap-mysql-user.md).

## Intended grant matrix

| Account | Runtime tables | `audit_logs` | Schema changes |
| --- | --- | --- | --- |
| `mdrrmo_app` | SELECT, INSERT, UPDATE, DELETE, granted per table | SELECT, INSERT only | Denied |
| `mdrrmo_audit` | No access | SELECT, INSERT only | Denied |
| `mdrrmo_migrate` | Required migration access | Required migration access | Allowed |

MySQL and MariaDB privileges are cumulative. A table-level `REVOKE` cannot
cancel UPDATE, DELETE, or DDL inherited from a schema-wide grant. The app and
audit accounts must have no schema-wide grants in the protected schemas;
runtime permissions are granted table by table. Repeat the app grants for new
runtime tables after migrations, excluding `audit_logs`. The admin moderation
page requires `SELECT, INSERT, UPDATE, DELETE` on `banned_ips` (including
progressive ban metadata), `SELECT, INSERT, DELETE` on `banned_phones`, and
`SELECT, INSERT` on `rate_limit_violations`. Keep violation history append-only;
the audit account must not receive access to these tables. The app also needs
`SELECT, INSERT, UPDATE, DELETE` on `incident_reports` for queue triage,
soft-delete/restore, and reject/reopen actions. IP bans use Laravel's
RateLimiter and expire after 24 hours; phone bans remain active until an admin
unbans them.

## Step 8: verify the matrix

Run the checks as the indicated account against both the application and test
schemas:

- App: `SELECT` and `INSERT` succeed on the probe; `UPDATE` and `DELETE` fail
  on the protected probe; `ALTER TABLE` fails. App `SELECT`, `INSERT`, and
  `DELETE` on `banned_ips` and `banned_phones` succeed.
- Audit: `SELECT` and `INSERT` succeed on the protected probe; `UPDATE` and
  `DELETE` fail; access to unrelated tables fails.
- Migration: `ALTER TABLE` succeeds on the probe.

After verification, drop the probe tables and revoke their table grants. Some
MariaDB versions can retain table grant entries after a table is dropped.

## Step 9: enforce audit-log permissions

Once `audit_logs` exists:

1. Revoke any app and audit schema-wide grants.
2. Restore app DML grants individually on runtime tables other than
   `audit_logs`.
3. Grant app and audit accounts only `SELECT, INSERT` on `audit_logs`.
4. Flush privileges and verify the effective grants and denied operations.

The app can append an audit row but cannot rewrite or delete existing audit
history. The audit account is limited to the audit table. The migration account
is the only account allowed to change schema structure.

## Verification record

Checked on 2026-10-16 against `emergency_sys` and `emergency_sys_test`:

- All Step 8 app, audit, and migration permission checks passed.
- Step 9 checks passed on both real `audit_logs` tables. App and audit inserts
  were performed in transactions and rolled back; UPDATE and DELETE were
  denied.
- Neither app nor audit has schema-wide grants in either schema. The audit
  account has no table grants outside `audit_logs`.
- Probe tables and probe grants were removed.
- `php artisan migrate:status --database=mysql` succeeded using the app
  connection.
- PHPUnit passed: 30 tests, 114 assertions.

## Environment status

The local server reported MariaDB 10.4.32. The project bootstrap requirements
specify MySQL 8.0+, and the server's current SQL mode does not include the
documented strict defaults. The grant matrix is verified, but the local server
does not satisfy the documented production database requirements. Resolve
that version and configuration gap before production deployment.
