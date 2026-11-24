# MDRRMO Production Readiness Checklist

Use this checklist before the first production launch and repeat the relevant sections before major releases. Keep the checklist with the release record. Do not mark an item complete from local development alone: attach the staging or production evidence. Mark an item `N/A` only with a short reason and an approver where it affects scope or security.

## Project profile

This checklist is tailored to the MDRRMO Emergency Response Portal: Laravel 12 / PHP 8.2+, server-rendered web UI with Vite-built assets, MySQL 8+, private report evidence, seven-day private tracking links, staff/admin operations, email notifications, and database-backed queue/cache/session defaults. The service is operationally sensitive: public reporting must remain usable, private reporter data must stay private, and the site must never imply that a submitted report has dispatched responders.

Use these repo runbooks alongside this checklist:

- [MySQL least-privilege bootstrap](runbooks/bootstrap-mysql-user.md)
- [MySQL grant verification](mysql-grant-verification.md)
- [Backup and restore drill](runbooks/restore-backup.md)
- [Local bootstrap and manual testing](runbooks/bootstrap-local.md) and [manual testing flows](manual-testing.md)

### Known project-specific release gates

These are observations from the repository, not a claim about the target deployment. Assign an owner and resolve or formally accept each applicable gap before launch.

- The checked-in `.env.example` is for local development (`APP_ENV=local`, `APP_DEBUG=true`, SQLite, log mailer, and HTTP localhost URL). Create production environment configuration independently; never deploy those example values unchanged.
- The MySQL runbook requires MySQL 8.0+ with strict SQL mode. The grant verification record notes that the local MariaDB 10.4 environment does not meet that production baseline.
- The SRS requires per-device abuse limits as well as IP limits. Verify the deployed implementation and record any residual risk or approved scope decision; tests currently establish selected throttles, not every SRS abuse requirement.
- The SRS calls for alerting across queue, notification, Reverb, token lookup, image processing, audit-chain, and disk events. Confirm which services are in the release and demonstrate the corresponding alerts. Do not count an application log entry as an alert.
- The SRS includes audit-log search/export and broader configurable retention/settings requirements that are not fully represented by current web routes. Decide whether those are launch blockers for the approved MVP; record the decision and owner.
- The repository contains a backup/restore procedure and disk-usage command, but production backup storage, alert delivery, monitoring, on-call ownership, and an executed restore record must be verified in the target environment.

## 1. Code and release quality

- [ ] Release is from a reviewed, protected branch; the deployed commit and approver are recorded.
- [ ] CI passes the PHPUnit suite (`php artisan test`) and any configured critical-flow/browser tests. No known unrelated failure is silently ignored.
- [ ] PHP formatting/static checks and frontend build pass (`vendor/bin/pint --test`, `npm ci`, `npm run build`).
- [ ] Critical paths have no debug output, test credentials, unsafe TODOs, or local-only behavior.
- [ ] `composer.lock` and `package-lock.json` are committed and match the manifests; install uses the lockfiles (`composer install`, `npm ci`).
- [ ] Composer and npm dependency audits are reviewed; security fixes or documented, time-bounded exceptions are recorded.
- [ ] Release notes identify migrations, setting changes, queue changes, and rollback constraints.

## 2. Production configuration and secrets

- [ ] Production environment uses `APP_ENV=production`, `APP_DEBUG=false`, and the canonical HTTPS `APP_URL`.
- [ ] Production `APP_KEY` is unique, generated securely, backed up appropriately, and never replaced casually; no local/test key is reused.
- [ ] DB, audit DB, migration DB, mail, Turnstile, proxy, and any enabled third-party credentials are stored outside source control and differ from development/staging credentials.
- [ ] Runtime uses the least-privilege `mdrrmo_app` database account; audit writes use the restricted audit account; schema migrations use the separate `mdrrmo_migrate` account only during deployment.
- [ ] Production uses MySQL 8.0+ with `utf8mb4` and required strict SQL modes; record server version, SQL mode, charset, and collation.
- [ ] Configure `SESSION_SECURE_COOKIE=true` behind HTTPS; verify `HttpOnly`, `SameSite`, session lifetime, and session encryption decisions for the actual deployment.
- [ ] Set `TRUSTED_PROXIES` to only the exact load-balancer/proxy IPs or CIDRs. Do not use `*` without a documented, controlled-proxy justification.
- [ ] Configure persistent production cache, session, and queue backends intentionally; current defaults use database-backed services. Confirm capacity and database connectivity.
- [ ] Laravel config/view/route caching and optimization commands are exercised in staging and do not break route registration or runtime configuration.
- [ ] `.env`, keys, dumps, logs, and backups are ignored by Git and blocked from web access. Production seed configuration creates no demo accounts.

## 3. Security and privacy

- [ ] HTTP redirects to HTTPS; certificate renewal is monitored; HSTS is enabled only after HTTPS is verified across all required hosts/subdomains.
- [ ] Review security headers at the public edge: at minimum `X-Content-Type-Options`, frame protection, `Referrer-Policy`, and an appropriate Content Security Policy. Verify headers on public pages, admin pages, errors, and evidence responses.
- [ ] Test auth and authorization for each role: unauthenticated, staff, admin, and any enabled news-specific roles. Staff cannot reach admin-only settings, moderation, user management, or exports.
- [ ] Test ownership and token boundaries: tracking tokens only access their own unexpired report; public responses never reveal phone, reporter contact, internal notes, or private staff data.
- [ ] Verify server-side validation, CSRF, output escaping, mass-assignment boundaries, and SQL-safe query handling on public, staff, and admin mutations.
- [ ] Verify login, report submission, tracking, messages, uploads, and admin-sensitive operations are rate-limited through the real proxy path; confirm client IP attribution cannot be spoofed through forwarded headers.
- [ ] Test Turnstile in production-like staging, including invalid token, provider outage/widget failure, and rate-limit fallback behavior. Confirm failed protection does not silently disable abuse controls.
- [ ] Verify evidence images, uploaded admin assets, and generated files are stored outside the public web root or served only through authorized, non-cacheable controllers; uploads are type/size-validated and not executable.
- [ ] Verify password hashing, session invalidation on logout, cookie flags, login throttling, and recovery procedures for compromised accounts.
- [ ] Confirm logs redact tracking tokens, names, phone numbers, descriptions, messages, image contents, passwords, session IDs, and request bodies. Never place secrets or tracking links in URLs or support tickets.
- [ ] Restrict the admin surface to authorized users; decide and document MFA and/or network allowlisting based on deployment capabilities. If unavailable, record compensating controls and the accepted risk.
- [ ] Confirm privacy notice, retention schedule, access/deletion process, and incident contact with the municipality's privacy/data-protection owner.

## 4. Database and records

- [ ] Apply migrations to a production-like staging copy and record the result. Review destructive or long-running changes before production.
- [ ] Confirm rollback or forward-fix instructions for every release migration; do not rely on automatic rollback for data-destructive changes.
- [ ] Verify indexes and query plans for report queue, status, tracking-token lookup, expiry, audit verification, and common moderation lookups.
- [ ] Apply and verify the per-table grants in the MySQL runbooks. App and audit users must not have schema-wide DDL or audit UPDATE/DELETE privileges.
- [ ] Ensure the database is private to the application and approved operator network; firewall public access and use TLS where traffic crosses untrusted networks.
- [ ] Verify migrations and seeders do not create local manual users or sample reports in production. Disable development seeding defaults and review any production account bootstrap.
- [ ] Approve the records retention schedule for report records, evidence, messages, tracking tokens, audit/security logs, and notification delivery records. Seven-day tracking expiry is not a deletion policy for official records.
- [ ] Run `php artisan audit:verify` against the production audit store using the approved verification account; record result and schedule recurring verification/alerting.

## 5. Infrastructure and deployment

- [ ] Staging matches production PHP, extensions, MySQL major version/configuration, proxy/TLS behavior, storage permissions, queue backend, and mail integration closely enough to validate releases.
- [ ] Deployment is repeatable and records commit, operator, timestamp, migration outcome, and smoke-test result. No manual source edits on the server.
- [ ] Define maintenance/availability expectations and a low-downtime deployment strategy appropriate for the municipality's operating hours.
- [ ] Document rollback and recovery, including whether the database migration is reversible and how to restore uploaded evidence consistently with the database.
- [ ] Supported and patched OS, web server, PHP 8.2+, required PHP extensions, and MySQL 8.0+ are installed; security updates have an owner and cadence.
- [ ] Set ownership and permissions so the web process can write only required Laravel storage/cache paths and cannot modify source or execute uploaded content.
- [ ] Run queue workers under a process supervisor (for example systemd/Supervisor or the chosen platform service); configure retries, timeout, memory limits, restart-on-deploy, and crash alerting.
- [ ] Ensure workers are gracefully restarted after deploy (`php artisan queue:restart`) and scheduler commands run from one controlled scheduler. Confirm duplicate scheduler execution is prevented where needed.
- [ ] Configure domain, DNS, TLS auto-renewal, firewall, and only required inbound ports; confirm database/admin ports are not internet-accessible.
- [ ] Size PHP workers, database connections, queue workers, disk, and bandwidth for expected reporting bursts and concurrent staff; document scaling/overflow actions.
- [ ] Confirm `/up` health-check behavior and ensure the load balancer/monitor treats dependency failure appropriately without exposing sensitive diagnostics.

## 6. Performance and availability

- [ ] Exercise expected peak report submissions, evidence uploads, staff queue polling, live map use, and concurrent tracking traffic in staging; document limits and results.
- [ ] Inspect slow queries and N+1 behavior on the dashboard, queue, reports, and admin lists; verify pagination and indexes under representative data volume.
- [ ] Confirm production Vite assets are built, compressed by the serving layer, fingerprinted/cached, and served without the Vite development server.
- [ ] Confirm images are size-limited, private, and do not make reporting unusable on low-end phones or unreliable connections.
- [ ] Move slow work to the queue where appropriate; verify worker throughput and that public submission does not claim notification/dispatch success before it is confirmed.
- [ ] Set connection and request timeouts, retries, and failure handling for mail and any enabled external geolocation, CAPTCHA, map, or routing providers.
- [ ] Verify graceful behavior when mail, queue, map tiles, Turnstile, or a provider is unavailable; public report intake must clearly communicate outcomes without losing a saved report.

## 7. Monitoring, logs, and alerts

- [ ] Central error tracking or an equivalent monitored error channel is configured with sensitive-data scrubbing; test a synthetic error and verify delivery.
- [ ] Application, security, queue, and delivery logs have suitable levels, structured context/correlation IDs, rotation, retention, access control, and redaction.
- [ ] Monitor uptime, `/up`, response latency, 4xx/5xx rates, database connections, disk, CPU/memory, queue depth, and failed jobs; alerts reach a named person/team.
- [ ] Schedule `php artisan health:check-disk --threshold=80` and verify an over-threshold event reaches an operator, not only the local log.
- [ ] Configure and test alerts for stopped workers, repeated notification failures, enabled realtime-service downtime, failed token lookup spikes, image-processing failures, audit-chain failures, and disk threshold. Mark a service N/A only if it is not part of this release.
- [ ] Confirm failed jobs can be inspected, retried safely, and alerted; document who owns `php artisan queue:failed` / retry handling.
- [ ] Publish an on-call contact, escalation path, support hours, and response expectations for emergency-reporting outages.

## 8. Backups and disaster recovery

- [ ] Run encrypted scheduled backups of MySQL and `storage/app` evidence/assets; keep copies off-host and restrict access to the encryption key.
- [ ] Confirm backup jobs report success/failure to the monitoring channel and have adequate retention/capacity.
- [ ] Complete and record a restore drill using [the restore runbook](runbooks/restore-backup.md), including database and matching storage contents; verify public intake, staff queue, tracking, and private evidence access.
- [ ] Define and approve RPO/RTO targets with the service owner; verify backup frequency and restore speed meet them.
- [ ] Document incident roles, evidence preservation, privacy breach escalation, user/public notices, and recovery communication.
- [ ] Take and verify a fresh backup immediately before a high-risk schema or storage migration.

## 9. Email and external services

- [ ] Configure production mail credentials and sender identity; ensure Laravel is not using the `log` mailer in production.
- [ ] Configure and verify SPF, DKIM, and DMARC for the actual sending domain; monitor reputation and bounce/failure rates.
- [ ] Test a new-report notification end-to-end in staging and verify it contains only approved summary/reference/dashboard link data, not phone numbers, descriptions beyond policy, or photos.
- [ ] Verify `notification_deliveries` records outcomes and that retries are bounded, idempotent, and observable.
- [ ] Validate Turnstile production keys/domains and its failure mode. Keep keys out of browser-visible config except the intended site key.
- [ ] Inventory enabled map tiles/routing, realtime, object storage, SMS, or messaging integrations; verify credentials, terms, limits, privacy impact, and provider-outage behavior.
- [ ] `N/A` for payment processing: this emergency-reporting MVP has no payment flow. `N/A` for SMS/Telegram only if those channels are not enabled in the release; email remains an operational notification dependency.

## 10. User experience and compliance

- [ ] Verify custom 403/404/500/maintenance behavior does not expose stack traces, SQL, tokens, or private report data; hotline guidance remains visible where appropriate.
- [ ] Test target devices/browsers, keyboard-only flows, contrast, labels, validation errors, language switching, and reduced-motion behavior for public reporting, tracking, staff operations, and admin settings.
- [ ] Smoke-test a complete public report from phone-sized viewport through confirmation and seven-day tracking; verify status and dispatch-limit copy remains clear.
- [ ] Smoke-test staff triage and reporter messaging; verify status explanations and internal notes remain separated.
- [ ] Verify only a confirmed admin dispatch action can produce dispatch claims; submitting or accepting a report must not imply responders were sent.
- [ ] Publish current privacy notice and service limitations; obtain required legal/records/privacy approvals. Terms/cookie consent are N/A unless required by the municipality's policy or deployment jurisdiction; record that determination.
- [ ] Keep public pages indexable only as intended; admin, tracking-token, and private operational routes must not be indexed. Verify `robots.txt` is not treated as an access control.

## 11. Documentation and handover

- [ ] Replace the generic framework README with or supplement it with app-specific setup, environment variables, deploy/build/test commands, and service dependencies.
- [ ] Maintain architecture, data-flow/privacy boundaries, role matrix, migration/rollback notes, and operational decisions.
- [ ] Keep runbooks current for deploy, rollback, restore, database grants, audit verification, queue/failed jobs, secrets rotation, and provider outage response.
- [ ] Document every production environment variable and its owner; never put secret values in documentation.
- [ ] Review production account access and remove temporary/vendor accounts; admin/staff accounts use named individuals and least privilege.
- [ ] Record known issues, accepted risks, SRS deviations, and post-launch owners/due dates. Include the known audit export/retention and abuse-control gaps if still open.

## 12. Launch day

- [ ] Complete final regression on staging using production-like configuration and non-sensitive data; all critical tests and release checks pass.
- [ ] Select a launch window with the service owner, technical operator, and incident contact available; notify staff of any downtime or changed workflow.
- [ ] Take a fresh database and evidence backup and verify its completion before deploy.
- [ ] Deploy the approved commit using the runbook; run reviewed migrations with the migration-only account; build assets and restart workers/scheduler as documented.
- [ ] Smoke-test production: landing page and hotline guidance, report submission, tracking/privacy boundary, staff login/queue/triage, admin-only controls, email delivery, evidence access, `/up`, and audit verification.
- [ ] Confirm no test accounts, seed reports, debug pages, Vite dev server, or development credentials are exposed.
- [ ] Watch errors, queue, email, database, latency, disk, and abuse signals through the agreed observation period.
- [ ] Agree rollback triggers before starting (for example, report intake unavailable, private data exposure, broken staff operations, or data corruption) and execute only the documented rollback/recovery path.

## 13. Post-launch and recurring maintenance

- [ ] Review errors, delivery outcomes, latency, queues, disk, and user/staff reports after the first day and first week; assign every issue.
- [ ] Reconcile actual report and notification flow with the audit log; verify the audit hash chain and alert on any failure.
- [ ] Collect staff feedback and check whether reports are being triaged within operational expectations; avoid publishing unverified response metrics.
- [ ] Hold a short retrospective, record incidents and near misses, and update this checklist and runbooks.
- [ ] Schedule recurring dependency/security updates, access reviews, secret rotation, backup verification and restore drills, audit-chain checks, and privacy/retention reviews.

## Release record

- Release/version and commit:
- Environment/domain:
- Launch date/time and timezone:
- Technical operator:
- MDRRMO/service owner:
- Privacy/security approver:
- Open risks accepted (owner and due date):
- Backup/restore evidence:
- Smoke-test evidence:
- Rollback decision and outcome:
