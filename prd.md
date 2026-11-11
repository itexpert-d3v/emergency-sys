# MDRRMO Emergency Response Reporting Web App: PRD v1.2

**Oct 12, 2026 · @Im Albert**

A mobile-friendly, account-free web app where residents report emergencies to the MDRRMO and track progress for seven days, with a staff dashboard, notifications, realtime updates and full logging.

**Database for MVP: MySQL 8.0+ (InnoDB, utf8mb4).**

---

## Overview and scope

Residents report accidents and emergencies to the MDRRMO without an account, through a guided chat that collects structured fields. They get a reference code and a private tracking link that works for seven days. Staff triage reports in a protected dashboard and are notified the moment a report arrives.

This system supports MDRRMO intake, response coordination, and private tracking. It is not a replacement for the emergency hotline, and a submitted report is never proof that responders were dispatched. Only an authorized administrator's recorded dispatch confirmation may tell a reporter that an ambulance or response group is on the way. Every public screen must tell people in immediate danger to call the local emergency hotline first.

---

## What changed from v1.0

| Change | Why |
|---|---|
| Reporter contact number added (private) | Staff need to call back to confirm location and details |
| Reporting on behalf of someone else | The reporter is often not the victim |
| Location-first design with browser geolocation and map pin | Rural locations are landmarks, not addresses |
| Text-first submission, images uploaded afterward | A weak signal must never block a report |
| Staff notifications (email, SMS or Telegram, push) | A silent queue defeats the purpose of intake |
| Staff-set priority separate from category | Triage needs severity; category must not decide it |
| Turnstile, duplicate detection, junk handling | Anonymous emergency forms attract prank reports |
| Tagalog, Hiligaynon and Aklanon option alongside English | Language should not exclude residents |
| Link-saving aids for lost tracking links | Closed tabs on mobile are the norm |
| Realtime updates and optional two-way messaging via Laravel Reverb | Live status and staff follow-up questions |
| Full logging plan: audit, application, security and queue logs | Accountability and incident debugging |
| Naming fixed to MDRRMO everywhere | v1.0 mixed MDDRMO and MDRRMO |

## What changed from v1.1 to v1.2

| Change | Why |
|---|---|
| Database pinned to **MySQL 8.0+ / InnoDB / utf8mb4** for MVP | Matches likely production host; removes PG-only features from MVP scope |
| Audit log immutability via **dedicated DB grant + hash chain** (not RLS) | MySQL has no row-level security; grant + hash chain is the real guarantee |
| Duplicate detection via **`ST_Distance_Sphere` on MySQL 8, bbox + Haversine fallback** | No PostGIS in MVP; spatial is built in on MySQL 8 |
| Internal IDs use **ULID** (not UUID v1) | MySQL `UUID()` is v1 and index-unfriendly; ULID is sortable and app-generated |
| `strict` SQL mode + `InnoDB` engine enforced in config and migrations | Prevent silent truncation of contact numbers, codes, descriptions |
| Full-text search via **`FULLTEXT` (InnoDB)** behind a repository wrapper | Portable; swap to `tsvector` later without touching callers |
| Migration note added for a future Postgres path | Keeps the door open without over-engineering |
| Token hash stored as **`CHAR(64)` hex**, compared with `hash_equals` | Least-surprising, index-friendly, constant-time compare |
| All schema, config and migration examples included | Turn-key MVP |
| Audit chain stores canonical `payload` JSON column | Deterministic verification |

---

## Roles and main user flow

Three roles exist. Public reporters stay unauthenticated; staff and administrators sign in.

| Role | Permissions |
|---|---|
| Public reporter | Submit reports, receive a reference code, view progress and exchange messages on their own report via the private tracking link |
| MDRRMO staff | Review reports, set status and priority, post public updates, add internal notes, record response actions, reply to reporters |
| MDRRMO administrator | Everything staff can do, plus staff accounts and permissions, categories, settings, notification channels, audit and log review, and confirmed ambulance/response-group dispatch notices |

### Main flow

1. The landing page shows the emergency warning, hotline numbers and a Report an Incident button.
2. A guided chat collects incident type, location, description, optional photos, then reporter details.
3. The resident reviews and submits. Text is saved first; images upload afterward.
4. The confirmation screen shows the reference code and private tracking link, with copy, share and save buttons, and tells the resident to save the link before leaving.
5. Staff are notified immediately and triage the report.
6. The resident reopens the tracking link and sees permitted status updates for seven days.

---

## Public pages

| Page | Contents |
|---|---|
| Landing | MDRRMO name and branding, short service explanation, hotline numbers, life-threatening-situation instructions, Report an Incident button, Track a report link, privacy notice, service limitations, language switch |
| Reporting chat | Guided questions, progress indicator, category selection, location with geolocation and map pin, description, photo upload, review and edit, submit |
| Confirmation | Reference code, private tracking link, copy and share buttons, expiry date, instruction to save the link, hotline reminder |
| Tracking | Reference code, submission time, current status, status timeline, public staff updates, message thread with staff, expiry countdown, clear expired message |

All pages must be usable on low-end phones: server-rendered first paint, small JavaScript budget, no required third-party scripts other than the abuse check.

---

## Guided report chat

The chat is a scripted assistant with predefined questions and validated inputs, not an open AI chatbot. It asks the most urgent information first: type, location, description. Reporter details come last so a person in a hurry gets the key facts in early.

| Field | Requirement |
|---|---|
| Incident type | Required; from the administrator's active categories |
| Incident location | Required; text (barangay, street, landmark) plus optional map pin or coordinates |
| Description | Required; plain text, configurable minimum and maximum length |
| Reporting for someone else | Toggle; if on, ask the relationship and whether the victim can be contacted |
| Reporter name | Required; reasonable name formats, configurable length |
| Reporter contact number | Required in the MVP, configurable; Philippine mobile format validation; stored privately, never shown on the tracking page |
| Evidence images | Optional; maximum two |
| Language | Selectable at the start: English, Tagalog, Hiligaynon, Aklanon; staff-facing fields stay in the language entered |
| Client idempotency key | Generated in the browser; prevents duplicate reports on retry |
| Submission timestamp | Server-generated |
| Reference code | Server-generated, unique, e.g. MDR-2026-A8K4Q2 |
| Tracking secret | Server-generated random token |
| Expiration timestamp | Server-set, seven days after submission |

---

## Location handling

- A **Use my current location** button requests browser geolocation and drops a pin the resident can drag; the text location remains required so the report is usable without GPS.
- Photo GPS is stripped (see evidence images), so device geolocation is the only coordinate source, and only with explicit permission.
- Store latitude, longitude and an accuracy value in structured columns, separate from the text location.
- Show the pin to staff on a map with the typed description beside it.

**MySQL note:** store `latitude DECIMAL(10,7)` and `longitude DECIMAL(10,7)`, plus a generated `location_point POINT SRID 4326` column with a `SPATIAL INDEX` for map queries. No PostGIS in MVP.

---

## Language

Questions, validation messages and status labels are translatable strings. The MVP ships English and Tagalog; Hiligaynon and Aklanon are added as translation files once the office reviews wording. Category names and public status messages are translatable per language in the admin settings.

**MySQL note:** all tables `utf8mb4_unicode_ci`; strings round-trip without corruption.

---

## Drafts

Progress is kept in browser storage between steps. A draft is labelled as unsent and is never mistaken for a submitted report. Drafts older than 24 hours are discarded.

---

## Suggested categories (configurable)

Enable only those within the office's responsibility: vehicular accidents; fire incidents; flooding and water-related incidents; landslides and other natural hazards; medical emergencies; rescue and missing persons; structural collapse and other accidents; other emergencies. Category helps triage but never sets severity.

---

## Low-connectivity submission

Emergencies happen where signal is poor, so a slow upload must never block or lose a report.

1. **Text first.** On submit, the browser sends only the text fields, coordinates and idempotency key. The server commits the report, then returns the reference code and tracking link.
2. **Images after.** Photos upload separately against the new report, one at a time, with progress and a retry button. The confirmation screen is already shown and says "photos uploading".
3. **Idempotent retries.** The same idempotency key always returns the same report, so a retry after a dropped connection never creates a duplicate.
4. **Automatic retry.** If the connection fails, the browser keeps the payload, retries with backoff when the connection returns, and shows an honest "not yet sent" state, never a success screen.
5. **Light payloads.** The reporting page works without heavy JavaScript to load, and images are compressed in the browser before upload to save bandwidth.
6. **Success only after commit.** The success screen appears only after the server has committed the report and tracking record.

---

## Evidence images

The server is the source of truth. Client-side compression is a bandwidth optimization only; the server independently validates and reprocesses every file.

1. The resident selects or captures up to two photos.
2. The browser checks size, type and dimensions, then resizes and compresses to save bandwidth.
3. The server validates the file signature (not the extension), rejects corrupt or disguised files, resizes to the maximum dimension, converts to WebP and strips all EXIF including GPS.
4. Processing runs in a queued job; the original upload is deleted from temporary storage afterward unless an evidence-preservation policy says otherwise.
5. Files are stored in private storage under a random name. Staff view them through short-lived signed URLs, and each view is written to the audit log.

| Limit | Initial value |
|---|---|
| Images per report | 2 |
| Original size | 10 MB each |
| Longest dimension | 2,000 px |
| Processed target size | 500 KB or less when quality permits |
| Accepted input | JPEG, PNG, WebP, HEIC if the server library supports it |

**Hosting check:** confirm Imagick or GD with WebP support on the target host before committing to this pipeline; shared hosting often lacks it.

**Optional OCR and code detection (Phase 5):** extract plates, QR codes or visible text as an assistive feature only. Store extracted values apart from the description, show them to staff for verification, never execute embedded content, and never expose detected personal data publicly.

WebP copies are optimized evidence, not proof of authenticity. If forensic-quality originals are needed, define a separate restricted preservation policy.

---

## Status lifecycle and priority

**Acknowledged** and **Assigned** are two separate statuses in v1.1+, since receipt and routing are different events. Every change records who, when and why, and nothing silently deletes a report.

| Status | Meaning | Public message |
|---|---|---|
| Submitted | Report received and recorded; no dispatch implied | Your report was received. |
| Under review | Staff are checking details, location and evidence | Staff are reviewing your report. |
| Acknowledged | A staff member confirmed the report is valid and actionable | Your report was confirmed by MDRRMO staff. |
| Assigned | Routed to a responsible team or person | Your report was passed to a responsible team. |
| In progress | A recorded follow-up or response action is active; requires a real staff update | MDRRMO is acting on your report. |
| Resolved | Outcome recorded | Your report has been resolved. |
| Closed | Administratively closed | Your report is closed. |
| Duplicate, Invalid, Unable to verify | Require a reason and an authorized staff action | A neutral message set by the office |

**Priority.** Staff set a priority of Low, Medium, High or Critical separately from category. Priority is internal, drives queue sorting and notification urgency, and is never auto-set from the category. Changing it is logged.

Each status change records: previous and new status, public explanation, optional internal note, acting staff member, server timestamp. Internal notes, contact details, responder details and restricted evidence are never exposed by the public API.

---

## Anonymous tracking and expiration

The reference code identifies a report; only the private token authorizes access to it. A reference code alone never grants access.

1. On submission, generate a unique reference code and a cryptographically secure, high-entropy token (at least 32 random bytes).
2. Store only a hash of the token with the report ID, expiry and revocation fields. Never log the raw token.
3. Show the private tracking URL once on the confirmation screen.
4. On every tracking request, the server validates the token hash and compares the current server time with the expiry. The scheduled job is cleanup only, never the enforcement mechanism.
5. Seven days after submission, the token stops working and the page shows an expiration message without any report details.
6. Invalid, revoked and expired tokens return the same non-disclosing response, rate limited per IP.

**MySQL note:** `token_hash CHAR(64)` (SHA-256 hex) with `UNIQUE` index; compare with `hash_equals`. Never store the raw token.

---

## Link-saving aids

- Copy link and Share buttons (native share sheet on mobile) on the confirmation screen.
- A saved-reports list in the submitting browser's local storage, so the same device can reopen the link. It holds the link only, expires with the report, and is wiped on expiry.
- Optional and deferred: a Text me this link feature, which needs an SMS gateway and consent text.
- A lost link cannot be recovered from the reference code alone. The confirmation screen says so plainly.

Tracking page headers: `Referrer-Policy: no-referrer`, `X-Robots-Tag: noindex`, `Cache-Control: no-store`, and no third-party analytics on tracking routes.

---

## Retention vs access

| Data | Policy |
|---|---|
| Public tracking access | Expires seven days after submission |
| Tracking token | Unusable at expiry |
| Public status endpoint | Returns an expiration response only |
| Official report record | Kept per the municipality's records policy |
| Evidence images | Kept per the approved evidence and privacy policy |
| Logs | Kept per the schedule in the logging section |

Deleting reports after seven days would be a separate decision needing explicit approval, including backup handling.

---

## Staff dashboard and notifications

### Dashboard

- **Incident queue:** reference code, category, priority, location, submitted time, status; search, filters (status, category, priority, date range) and pagination. New and unacknowledged reports sort to the top.
- **Report details:** full report, map pin, evidence via signed URLs, reporter contact (staff only), status history, public updates, internal notes, message thread with the reporter.
- **Actions with confirmation:** change status, set priority, assign to a person or team, post a public update, mark duplicate or invalid with a required reason.
- **Overview:** counts by status, category and date range for operational summaries.
- **Admin settings:** categories, public status messages, notification channels, languages, staff accounts and roles.

### New-report notifications

A report that nobody sees defeats the purpose of intake, so notification is part of the MVP.

| Channel | MVP | Notes |
|---|---|---|
| Email to a monitored office address | Yes | Reference, category, priority-less summary, link to the dashboard; no contact number or photos in the email body |
| In-dashboard alert (sound and banner) | Yes | Delivered over WebSockets, with polling as the fallback |
| SMS or Telegram bot to the duty officer | Recommended | Needs a gateway or bot token; decide before Phase 3 |
| Browser push | Later | Useful when the dashboard tab is closed |

**Escalation:** if a report stays in Submitted past a configurable threshold (for example 10 minutes), re-notify and flag it in the queue. Each notification attempt and failure is logged.

---

## Abuse prevention

Prank and junk reports waste responder time, but friction must not stop a genuine reporter.

- **Bot check:** Cloudflare Turnstile (or an equivalent privacy-respecting challenge) on submission, invisible by default. If it fails to load, fall back to rate limits rather than blocking the report.
- **Rate limits:** per IP and per device fingerprint-free token (cookie) on submission, tracking lookups, message sending and upload endpoints, with stricter limits on failed token lookups.
- **Request-size limits** and strict validation on every public endpoint.
- **Duplicate detection:** flag new reports with the same category within a configurable radius and time window (for example 200 m and 30 minutes) so staff can link or merge them. Detection suggests; staff decide.
- **Junk handling:** staff can mark a report Invalid or Unable to verify with a reason, bulk-select likely junk, and flag an IP or device token for review. Repeat offenders are throttled, never silently blackholed without a log entry.
- **No hard blocks on first contact.** A suspected abusive source is rate limited and logged, and the report still reaches staff with a suspicion flag.

## Authorization and rate-limiting specification

### Authorization matrix

| Surface or action | Required authorization |
|---|---|
| Landing, report form, news, tracking pages | Public; no account required |
| Create a report or read categories | Public request, validated input, bot check where configured, and public rate limit |
| Read a report or send a reporter message | A valid, unexpired, non-revoked tracking token; the reference code alone is insufficient |
| Staff dashboard, report triage, staff messages, report photos | Authenticated `staff` or `admin` user |
| Admin report dashboard and incident queue | Authenticated `admin` user |
| News create, edit, publish, and archive | Authenticated `admin`, `mdrrmo_officer`, or `information_officer` user |
| Staff/admin logout | Authenticated session for the matching guard |

Authorization is enforced server-side with route middleware and token checks. UI visibility is not an authorization boundary. Public responses must never include reporter contact details, internal notes, restricted evidence, or another report's messages.

### Named rate limits

| Limiter | Applies to | Limit and key |
|---|---|---|
| `public-report` | Public report form and API submission | 3 requests/minute per client IP |
| `public-tracking` | Tracking pages, status, categories, and token reads | 10 requests/minute per client IP |
| `public-message` | Reporter messages through a tracking token | 10 requests/minute per client IP |
| `auth` | Staff and admin login POSTs | 5 requests/minute per IP and per normalized email hash |
| `staff-read` | Authenticated staff/admin dashboard reads | 60 requests/minute per user, falling back to IP |
| `staff-write` | Triage and staff replies | 30 requests/minute per user, falling back to IP |
| `admin-write` | News changes and confirmed responder-dispatch notices | 30 requests/minute per authorized user, falling back to IP |

Rate limits return the framework's standard `429 Too Many Requests` response. Failed and unauthorized requests are still subject to the relevant limiter, and rate-limit keys must not contain raw tracking tokens or passwords.

### Duplicate detection (MySQL 8)

1. Compute a lat/lng bounding box in PHP from `(center_lat, center_lng, radius_meters)`.
2. Query `incident_reports` with `BETWEEN` on `latitude`/`longitude`, filtered by `category`, `submitted_at >= now - window`, and `status NOT IN ('duplicate','invalid')`.
3. For each candidate, run `ST_Distance_Sphere(POINT(longitude, latitude), POINT(?, ?))` and keep rows within `radius_meters`.
4. Return candidates to the caller. Staff see suggestions; staff decide.

**Index required:** `idx_dup (category_id, submitted_at)` plus `idx_geo (latitude, longitude)`, or a `SPATIAL INDEX` on `location_point` (`SRID 4326`, `NOT NULL`).

**Fallback:** if the host is MySQL 5.7 or MariaDB without `ST_Distance_Sphere` parity, compute Haversine in PHP on the bbox-filtered candidate set. Same code path, no schema change.

**Postgres note:** if the deployment later moves to Postgres, replace with PostGIS `ST_DWithin`; keep the query behind a `DuplicateDetector` repository so callers don't change.

---

## Realtime and websockets

Yes, Laravel supports WebSockets natively. The recommended server is **Laravel Reverb**, Laravel's first-party WebSocket server. It speaks the Pusher protocol, so Laravel Echo and pusher-js work against it unchanged, and it runs inside your own Laravel project.

### What realtime is used for

| Use | Audience | Channel |
|---|---|---|
| New-report alert and queue refresh | Staff | Private channel, staff only, authorized by staff session |
| Live status and timeline updates | Reporter | Private channel per report, authorized by the tracking token |
| Two-way messages between staff and the reporter | Both | Same per-report private channel |
| Staff presence on a report ("Ana is viewing") | Staff | Presence channel, optional |

The guided report chat itself is scripted and runs in the browser; it does not need WebSockets. WebSockets power the follow-up messaging and live updates after submission.

### Setup in Laravel 12

```
php artisan install:broadcasting   # choose Reverb; installs config and Echo scaffolding
npm install laravel-echo pusher-js
php artisan reverb:start            # long-running WebSocket server
php artisan queue:work              # broadcasts are queued by default
```

Events implement `ShouldBroadcast` (or `ShouldBroadcastNow` for the urgent staff alert) and are dispatched when a report is created, a status changes, or a message is posted.

### Resident channel authorization

Residents have no account, so channel auth is token-based. The channel name uses the public reference, for example `private-report.MDR-2026-A8K4Q2`, and a custom broadcasting auth endpoint checks the tracking token (sent in a header, not the URL) against the stored hash, then checks that the report has not expired and the token is not revoked. At expiry the server pushes a final expired event and rejects any reconnect.

### Payload rules

- Broadcast minimal payloads (report ID, new status, message ID) and let the client refetch details through the normal authorized endpoint.
- Never broadcast contact numbers, internal notes, responder details or evidence URLs.
- Staff-only events go only to the staff channel, never the per-report channel.

### Messaging rules

- Message thread table `report_messages` with sender type (reporter or staff), body, timestamp and staff actor.
- Plain text only, length limit, per-token rate limit, and a staff switch to close a thread on a report.
- Staff can send a templated quick reply (for example "Please share a nearby landmark").
- Messages follow the report's retention policy, are visible to the reporter only until tracking expiry, and a reporter message never changes status by itself.
- Messages are untrusted input: escape on render, never interpret as markup or code, and log moderation actions.

---

## Hosting reality check

Reverb is a persistent PHP process. That affects deployment more than any other decision in this document.

| Hosting | Reverb works? | Approach |
|---|---|---|
| VPS or cloud VM | Yes | Run `reverb:start` under Supervisor or PM2, reverse proxy WebSocket traffic through Nginx on `wss://`, raise the open-file limit, add Redis if you scale to several servers |
| Shared hosting (cPanel) | No | Use a hosted provider (Pusher Channels, Ably) with the same Echo code, or fall back to polling |
| Managed platform | Depends | Check WebSocket support and idle timeouts |

**Fallback is mandatory.** The tracking page and dashboard must work with polling every 10 to 20 seconds when the socket is down, because rural mobile connections drop WebSockets often. Realtime is an enhancement, not a dependency.

**MySQL note:** MySQL 8.0+ required for `ST_Distance_Sphere` and JSON improvements. MariaDB 10.5+ works with the Haversine fallback. Shared hosting with MySQL 5.7 still works if you take the fallback and accept slightly slower duplicate checks.

---

## Logging

Four separate logs, each with a different audience, format and retention. They share a correlation ID generated per request and carried through queued jobs and broadcasts, so one report's journey can be traced end to end.

| Log | Storage | Who reads it | What goes in |
|---|---|---|---|
| Audit log | Database table `audit_logs`, insert-only | Administrators, privacy officer | Who did what to which record, and when |
| Security log | Dedicated Laravel channel, JSON lines | Administrators, developer | Auth and abuse events |
| Application log | Laravel daily channel, JSON lines | Developer | Errors, warnings, performance |
| Job and delivery log | `failed_jobs` plus a delivery table | Developer, administrators | Image processing, notifications, broadcasts |

### Audit log (accountability) — MySQL enforcement

The v1.1 guarantee ("app DB user has no UPDATE or DELETE on `audit_logs`") is preserved, but on MySQL it is enforced by **grants**, not RLS, and backed by a **hash chain**.

**Two DB users:**

| User | Grants | Used by |
|---|---|---|
| `mdrrmo_app` | `SELECT, INSERT, UPDATE, DELETE` on all tables **except** `audit_logs` (where it has only `SELECT, INSERT`) | The application |
| `mdrrmo_audit` | `INSERT, SELECT` only on `audit_logs` | A dedicated audit-writer connection |

**Hash chain:** each row stores `prev_hash` and `row_hash = sha256(prev_hash || canonical_payload)`. On insert, the writer locks the last row (`SELECT ... FOR UPDATE`) and computes the new hashes in one transaction. Any tamper attempt by a DBA is detectable by a verification job.

**Verification job:** scheduled daily; scans `audit_logs` in id order and recomputes `row_hash` from the stored `payload` JSON. Mismatch raises an administrator alert. Counts and status only are logged to the application log.

**What gets recorded**, with actor, role, timestamp, IP, correlation ID, target record and a before/after summary:

- Staff sign-in, sign-out, failed sign-in and permission changes.
- Report viewed by staff, status and priority changes, assignment, public update posted, internal note added, report marked duplicate or invalid.
- Evidence image viewed or downloaded, contact number revealed.
- Staff messages sent to reporters and thread closed.
- Category, setting, notification-channel and account changes.
- Exports and any deletion or retention action.

The table is insert-only: the application database user has no UPDATE or DELETE permission on it, and the hash chain makes tampering detectable. Administrators can search and export it; nobody can edit it.

**MySQL notes:**
- `audit_logs.id BIGINT UNSIGNED AUTO_INCREMENT` for monotonic ordering.
- `prev_hash` and `row_hash` are `CHAR(64)`; `prev_hash` nullable only for the genesis row.
- Canonical `payload JSON NOT NULL` column so the chain is deterministic.
- `ENGINE=InnoDB`, `ROW_FORMAT=DYNAMIC`, `utf8mb4`.

### Security log

- Failed and rate-limited tracking-token lookups (token never logged, only a prefix hash and the IP).
- Expired-token access attempts, Turnstile failures, rejected uploads and their reasons.
- Duplicate-submission and suspected-abuse flags, throttling decisions.
- Channel authorization failures on the WebSocket endpoint.

### Application log

- Unhandled exceptions, validation failure rates, slow queries, queue backlog, Reverb connection errors.
- Each entry carries the correlation ID, route name and user type (public, staff, admin), not the payload.

### Job and delivery log

- Image processing result, size before and after, and rejection reason.
- Each notification attempt per channel with success, failure and retry count.
- Scheduled cleanup runs: tokens expired, drafts purged, counts only.

### Never logged

Raw tracking tokens, reporter names, contact numbers, incident descriptions, message bodies, image contents, passwords, session IDs, or full request bodies. A Monolog redaction processor strips known sensitive keys as a safety net, and error trackers (if used) are configured to drop request data.

### Retention and review

| Log | Suggested retention | Review |
|---|---|---|
| Audit log | Per the municipality's records policy; at least one year suggested | Monthly sample review by the administrator |
| Security log | 90 to 180 days | Weekly check of abuse and failed-lookup trends |
| Application log | 14 to 30 days | On failure alerts |
| Job and delivery log | 30 to 90 days | Daily failed-job check |

Retention values are proposals for the privacy officer to confirm.

### Alerts

Email or Telegram alert to the administrator when: queue workers stop, notification delivery fails repeatedly, Reverb is down, failed token lookups spike, image processing failures exceed a threshold, audit-chain verification fails, or the disk holding private storage passes 80 percent.

---

## Architecture, hosting and data model

One Laravel 12 monolith with Vue 3, Inertia and Tailwind, matching your current stack. **MySQL 8.0+ (InnoDB, utf8mb4) for MVP.** No separate backend is needed.

| Component | Role |
|---|---|
| Laravel 12 app | Validation, authorization, reporting workflow, tracking endpoints, staff dashboard |
| Vue 3 + Inertia + Tailwind | Public pages, guided chat, tracking page, dashboard |
| **MySQL 8.0+ (InnoDB, utf8mb4)** | Reports, statuses, users, messages, audit history |
| Private file storage | Processed WebP evidence, served only by signed URL |
| Queue worker | Image processing, notifications, broadcasts |
| Scheduler (cron) | Token expiry cleanup, draft purge, escalation checks, log pruning, **audit hash-chain verification** |
| Laravel Reverb | WebSockets for staff alerts, live tracking and messages |
| Redis (optional) | Queue, cache, rate limiting; required if Reverb scales to several servers |
| Cloudflare Turnstile | Bot challenge |

Logical modules (not separate packages): `PublicReporting`, `IncidentManagement`, `ReportTracking`, `EvidenceManagement`, `Messaging`, `Notifications`, `StaffAdministration`, `AuditLog`, `RetentionManagement`.

### Hosting decision to make early

The app needs a cron entry, a queue worker and, for realtime, a persistent Reverb process. A small VPS with Nginx, PHP-FPM, MySQL 8, Supervisor or PM2, and HTTPS covers all of it. Shared hosting can run the web app and a cron-driven database queue, but not Reverb, so realtime would use a hosted provider or polling there.

### Core tables (MySQL 8, InnoDB, utf8mb4)

All tables: `ENGINE=InnoDB`, `CHARSET=utf8mb4`, `COLLATE=utf8mb4_unicode_ci`, `ROW_FORMAT=DYNAMIC`. Internal IDs are **ULID** (`CHAR(26)`), app-generated with `Str::ulid()`.

#### `incident_reports`

| Column | Type | Notes |
|---|---|---|
| `id` | `CHAR(26)` PK | ULID |
| `reference_code` | `VARCHAR(20)` UNIQUE | e.g. `MDR-2026-A8K4Q2` |
| `category_id` | `BIGINT UNSIGNED` FK → `incident_categories.id` | |
| `description` | `TEXT` | |
| `location_text` | `VARCHAR(500)` | |
| `latitude` | `DECIMAL(10,7)` NULL | |
| `longitude` | `DECIMAL(10,7)` NULL | |
| `location_accuracy` | `DECIMAL(8,2)` NULL | meters |
| `location_point` | `POINT SRID 4326` NULL, generated | `SPATIAL INDEX` |
| `reporter_name` | `VARCHAR(120)` | |
| `contact_number` | `VARCHAR(20)` | private |
| `reporting_for_other` | `TINYINT(1)` DEFAULT 0 | |
| `relationship` | `VARCHAR(80)` NULL | |
| `victim_contactable` | `TINYINT(1)` NULL | |
| `language` | `CHAR(5)` | `en`, `fil`, `hil`, `krj` |
| `priority` | `VARCHAR(10)` | `low`, `medium`, `high`, `critical` |
| `status` | `VARCHAR(20)` | `submitted` … `closed` |
| `idempotency_key` | `CHAR(36)` UNIQUE | client-generated |
| `suspicion_flag` | `TINYINT(1)` DEFAULT 0 | |
| `submitted_at` | `TIMESTAMP` | server |
| `expires_at` | `TIMESTAMP` | submitted_at + 7d |
| `created_at`, `updated_at` | `TIMESTAMP` | |

Indexes: `UNIQUE(reference_code)`, `UNIQUE(idempotency_key)`, `idx_queue (status, priority, submitted_at)`, `idx_dup (category_id, submitted_at)`, `idx_geo (latitude, longitude)`, `SPATIAL(location_point)`, `FULLTEXT ft_search (description, location_text)`.

#### `incident_evidence`

| Column | Type |
|---|---|
| `id` | `CHAR(26)` PK |
| `report_id` | `CHAR(26)` FK |
| `path` | `VARCHAR(255)` |
| `mime_type` | `VARCHAR(80)` |
| `size_bytes` | `INT UNSIGNED` |
| `width`, `height` | `INT UNSIGNED` NULL |
| `processing_status` | `VARCHAR(20)` |
| `rejection_reason` | `VARCHAR(255)` NULL |
| `extracted_text` | `TEXT` NULL (Phase 5) |
| timestamps | |

Index: `idx_evidence_report (report_id, processing_status)`.

#### `incident_status_histories`

| Column | Type |
|---|---|
| `id` | `CHAR(26)` PK |
| `report_id` | `CHAR(26)` FK |
| `previous_status` | `VARCHAR(20)` NULL |
| `new_status` | `VARCHAR(20)` |
| `public_update` | `TEXT` NULL |
| `internal_note` | `TEXT` NULL |
| `actor_id` | `BIGINT UNSIGNED` NULL FK → `users.id` |
| `created_at` | `TIMESTAMP` |

Index: `idx_history_report (report_id, created_at)`.

#### `incident_categories`

| Column | Type |
|---|---|
| `id` | `BIGINT UNSIGNED` PK AUTO_INCREMENT |
| `slug` | `VARCHAR(60)` UNIQUE |
| `name_translations` | `JSON` |
| `active` | `TINYINT(1)` DEFAULT 1 |
| `sort_order` | `INT` DEFAULT 0 |
| timestamps | |

#### `report_tracking_tokens`

| Column | Type |
|---|---|
| `id` | `CHAR(26)` PK |
| `report_id` | `CHAR(26)` FK |
| `token_hash` | `CHAR(64)` UNIQUE | SHA-256 hex |
| `expires_at` | `TIMESTAMP` |
| `revoked_at` | `TIMESTAMP` NULL |
| `created_at` | `TIMESTAMP` |

Indexes: `UNIQUE(token_hash)`, `idx_token_report (report_id)`.

#### `report_messages`

| Column | Type |
|---|---|
| `id` | `CHAR(26)` PK |
| `report_id` | `CHAR(26)` FK |
| `sender_type` | `VARCHAR(10)` | `reporter` or `staff` |
| `staff_actor_id` | `BIGINT UNSIGNED` NULL |
| `body` | `TEXT` |
| `created_at` | `TIMESTAMP` |

Index: `idx_msg_report (report_id, created_at)`.

#### `assignments`

| Column | Type |
|---|---|
| `id` | `CHAR(26)` PK |
| `report_id` | `CHAR(26)` FK |
| `assigned_to_user_id` | `BIGINT UNSIGNED` NULL |
| `assigned_to_team` | `VARCHAR(80)` NULL |
| `assigned_by_user_id` | `BIGINT UNSIGNED` |
| `created_at` | `TIMESTAMP` |

#### `users`

| Column | Type |
|---|---|
| `id` | `BIGINT UNSIGNED` PK AUTO_INCREMENT |
| `name` | `VARCHAR(120)` |
| `email` | `VARCHAR(190)` UNIQUE |
| `password` | `VARCHAR(255)` |
| `role` | `VARCHAR(20)` | `staff` or `admin` |
| `active` | `TINYINT(1)` DEFAULT 1 |
| `two_factor_secret` | `VARCHAR(255)` NULL |
| timestamps | |

#### `notification_deliveries`

| Column | Type |
|---|---|
| `id` | `CHAR(26)` PK |
| `report_id` | `CHAR(26)` FK |
| `channel` | `VARCHAR(20)` | `email`, `telegram`, `sms`, `push`, `dashboard` |
| `attempt_count` | `INT UNSIGNED` DEFAULT 0 |
| `outcome` | `VARCHAR(20)` | `pending`, `sent`, `failed` |
| `last_attempt_at` | `TIMESTAMP` NULL |
| timestamps | |

#### `security_events`

| Column | Type |
|---|---|
| `id` | `CHAR(26)` PK |
| `event_type` | `VARCHAR(60)` |
| `ip` | `VARBINARY(16)` NULL | packed inet |
| `context` | `JSON` NULL | redacted |
| `created_at` | `TIMESTAMP` |

Index: `idx_sec_event (event_type, created_at)`.

#### `audit_logs` (insert-only)

| Column | Type | Notes |
|---|---|---|
| `id` | `BIGINT UNSIGNED` PK AUTO_INCREMENT | monotonic, chain order |
| `actor_user_id` | `BIGINT UNSIGNED` NULL | |
| `actor_role` | `VARCHAR(20)` NULL | |
| `action` | `VARCHAR(80)` | |
| `target_type` | `VARCHAR(60)` NULL | |
| `target_id` | `VARCHAR(64)` NULL | |
| `payload` | `JSON` NOT NULL | canonical, hashed |
| `before_summary` | `JSON` NULL | redacted |
| `after_summary` | `JSON` NULL | redacted |
| `ip` | `VARBINARY(16)` NULL | |
| `correlation_id` | `CHAR(26)` | |
| `prev_hash` | `CHAR(64)` NULL | genesis row null |
| `row_hash` | `CHAR(64)` NOT NULL | |
| `created_at` | `TIMESTAMP` | |

Indexes: `idx_audit_actor (actor_user_id, created_at)`, `idx_audit_action (action, created_at)`, `idx_audit_corr (correlation_id)`.

**Insert path:** dedicated connection `mysql_audit` uses DB user `mdrrmo_audit` (INSERT, SELECT only). Writer acquires `SELECT ... FOR UPDATE` on the last row, computes `row_hash`, inserts, commits. The app connection `mdrrmo_app` has only `SELECT, INSERT` on this table.

#### `settings`

| Column | Type |
|---|---|
| `key` | `VARCHAR(80)` PK |
| `value` | `JSON` |
| `updated_at` | `TIMESTAMP` |

#### `failed_jobs`, `jobs`, `cache`, `sessions` — Laravel defaults, MySQL-compatible.

Index reference: reference code, status, priority, submission date, category, coordinates and the spatial point. Duplicate detection uses a bbox query first and a `ST_Distance_Sphere` check second. Full-text search uses the `FULLTEXT` index.

### MySQL grants (dev and prod bootstrap)

```sql
-- Application user
CREATE USER 'mdrrmo_app'@'%' IDENTIFIED BY '...';
GRANT SELECT, INSERT, UPDATE, DELETE ON mdrrmo.* TO 'mdrrmo_app'@'%';
REVOKE UPDATE, DELETE ON mdrrmo.audit_logs FROM 'mdrrmo_app'@'%';
GRANT SELECT, INSERT ON mdrrmo.audit_logs TO 'mdrrmo_app'@'%';

-- Audit writer user
CREATE USER 'mdrrmo_audit'@'%' IDENTIFIED BY '...';
GRANT INSERT, SELECT ON mdrrmo.audit_logs TO 'mdrrmo_audit'@'%';
FLUSH PRIVILEGES;
```

The app uses two connections: `mysql` (default) and `mysql_audit` (used only by the audit writer service).

### `config/database.php` (relevant additions)

```php
'default' => env('DB_CONNECTION', 'mysql'),

'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'mdrrmo'),
        'username' => env('DB_USERNAME', 'mdrrmo_app'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => 'InnoDB',
    ],

    'mysql_audit' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'mdrrmo'),
        'username' => env('DB_AUDIT_USERNAME', 'mdrrmo_audit'),
        'password' => env('DB_AUDIT_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'strict' => true,
        'engine' => 'InnoDB',
    ],
],
```

`.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mdrrmo
DB_USERNAME=mdrrmo_app
DB_PASSWORD=...
DB_AUDIT_USERNAME=mdrrmo_audit
DB_AUDIT_PASSWORD=...
```

### Key migrations (MySQL 8)

**`create_incident_reports_table`**

```php
Schema::create('incident_reports', function (Blueprint $t) {
    $t->engine = 'InnoDB';
    $t->charset = 'utf8mb4';
    $t->collation = 'utf8mb4_unicode_ci';

    $t->char('id', 26)->primary();
    $t->string('reference_code', 20)->unique();
    $t->unsignedBigInteger('category_id');
    $t->text('description');
    $t->string('location_text', 500);
    $t->decimal('latitude', 10, 7)->nullable();
    $t->decimal('longitude', 10, 7)->nullable();
    $t->decimal('location_accuracy', 8, 2)->nullable();
    $t->string('reporter_name', 120);
    $t->string('contact_number', 20);
    $t->boolean('reporting_for_other')->default(false);
    $t->string('relationship', 80)->nullable();
    $t->boolean('victim_contactable')->nullable();
    $t->char('language', 5)->default('en');
    $t->string('priority', 10)->default('medium');
    $t->string('status', 20)->default('submitted');
    $t->char('idempotency_key', 36)->unique();
    $t->boolean('suspicion_flag')->default(false);
    $t->timestamp('submitted_at')->useCurrent();
    $t->timestamp('expires_at')->nullable();
    $t->timestamps();

    $t->index(['status', 'priority', 'submitted_at'], 'idx_queue');
    $t->index(['category_id', 'submitted_at'], 'idx_dup');
    $t->index(['latitude', 'longitude'], 'idx_geo');
    $t->foreign('category_id')->references('id')->on('incident_categories');
});

DB::statement('ALTER TABLE incident_reports ADD FULLTEXT ft_search (description, location_text)');

DB::statement("
    ALTER TABLE incident_reports
    ADD COLUMN location_point POINT SRID 4326
    GENERATED ALWAYS AS (
        CASE WHEN latitude IS NOT NULL AND longitude IS NOT NULL
             THEN ST_SRID(POINT(longitude, latitude), 4326)
             ELSE NULL END
    ) STORED
");
DB::statement('ALTER TABLE incident_reports ADD SPATIAL INDEX idx_spatial (location_point)');
```

**`create_audit_logs_table`**

```php
Schema::connection('mysql')->create('audit_logs', function (Blueprint $t) {
    $t->engine = 'InnoDB';
    $t->charset = 'utf8mb4';
    $t->collation = 'utf8mb4_unicode_ci';

    $t->bigIncrements('id');
    $t->unsignedBigInteger('actor_user_id')->nullable();
    $t->string('actor_role', 20)->nullable();
    $t->string('action', 80);
    $t->string('target_type', 60)->nullable();
    $t->string('target_id', 64)->nullable();
    $t->json('payload');
    $t->json('before_summary')->nullable();
    $t->json('after_summary')->nullable();
    $t->binary('ip', 16)->nullable();
    $t->char('correlation_id', 26);
    $t->char('prev_hash', 64)->nullable();
    $t->char('row_hash', 64);
    $t->timestamp('created_at')->useCurrent();

    $t->index(['actor_user_id', 'created_at'], 'idx_audit_actor');
    $t->index(['action', 'created_at'], 'idx_audit_action');
    $t->index('correlation_id', 'idx_audit_corr');
});
```

**`create_report_tracking_tokens_table`**

```php
Schema::create('report_tracking_tokens', function (Blueprint $t) {
    $t->char('id', 26)->primary();
    $t->char('report_id', 26);
    $t->char('token_hash', 64)->unique();
    $t->timestamp('expires_at');
    $t->timestamp('revoked_at')->nullable();
    $t->timestamp('created_at')->useCurrent();

    $t->index('report_id', 'idx_token_report');
    $t->foreign('report_id')->references('id')->on('incident_reports');
});
```

Other tables (`incident_evidence`, `incident_status_histories`, `incident_categories`, `report_messages`, `assignments`, `users`, `notification_deliveries`, `security_events`, `settings`) follow the schema tables above; migrations are mechanical.

### Audit writer service

`App\Services\Audit\AuditWriter`:

```php
final class AuditWriter
{
    public function __construct(
        private Connection $auditDb, // 'mysql_audit'
    ) {}

    public function record(array $payload): void
    {
        $this->auditDb->transaction(function () use ($payload) {
            $last = $this->auditDb->table('audit_logs')
                ->orderByDesc('id')->lockForUpdate()->first();

            $prev = $last->row_hash ?? null;
            $canonical = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $rowHash = hash('sha256', ($prev ?? '') . $canonical);

            $this->auditDb->table('audit_logs')->insert([
                ...$payload,
                'payload' => $canonical,
                'prev_hash' => $prev,
                'row_hash' => $rowHash,
                'created_at' => now(),
            ]);
        });
    }

    public function verifyChain(): array
    {
        $prev = null; $checked = 0;
        $this->auditDb->table('audit_logs')->orderBy('id')
            ->chunk(500, function ($rows) use (&$prev, &$checked) {
                foreach ($rows as $row) {
                    $expected = hash('sha256', ($prev ?? '') . $row->payload);
                    if (!hash_equals($expected, $row->row_hash)) {
                        throw new AuditChainBrokenException($row->id);
                    }
                    $prev = $row->row_hash; $checked++;
                }
            });
        return ['checked' => $checked];
    }
}
```

### Duplicate detector

```php
final class DuplicateDetector
{
    public function findCandidates(
        float $lat, float $lng, string $categorySlug, int $radiusMeters = 200, int $windowMinutes = 30
    ): Collection {
        $bbox = $this->boundingBox($lat, $lng, $radiusMeters);

        return DB::table('incident_reports as r')
            ->join('incident_categories as c', 'c.id', '=', 'r.category_id')
            ->where('c.slug', $categorySlug)
            ->where('r.submitted_at', '>=', now()->subMinutes($windowMinutes))
            ->whereNotIn('r.status', ['duplicate', 'invalid'])
            ->whereBetween('r.latitude', [$bbox['minLat'], $bbox['maxLat']])
            ->whereBetween('r.longitude', [$bbox['minLng'], $bbox['maxLng']])
            ->selectRaw(
                'r.id, r.reference_code, r.latitude, r.longitude, '.
                'ST_Distance_Sphere(POINT(r.longitude, r.latitude), POINT(?, ?)) AS distance_m',
                [$lng, $lat]
            )
            ->havingRaw('distance_m <= ?', [$radiusMeters])
            ->orderBy('distance_m')
            ->get();
    }

    private function boundingBox(float $lat, float $lng, int $m): array
    {
        $dLat = $m / 111_320;
        $dLng = $m / (111_320 * cos(deg2rad($lat)));
        return [
            'minLat' => $lat - $dLat, 'maxLat' => $lat + $dLat,
            'minLng' => $lng - $dLng, 'maxLng' => $lng + $dLng,
        ];
    }
}
```

Fallback Haversine path (MySQL 5.7 / MariaDB): replace the `selectRaw` + `havingRaw` with a computed `distance_m` in PHP after the bbox fetch. Interface unchanged.

### Repository boundary for search

```php
interface ReportSearchRepository
{
    public function search(array $filters, int $perPage = 25): LengthAwarePaginator;
}
```

MySQL implementation uses `MATCH ... AGAINST` on `ft_search` for free text, `LIKE` on `reference_code` for exact, and standard `WHERE` for filters. Postgres implementation later swaps to `tsvector`. Callers see only the interface.

---

## Stack decision, hosting and cost

**Stack decision:** Laravel 12 with Vue 3 and Inertia, written in TypeScript on the frontend (`<script setup lang="ts">`). **MySQL 8.0+ (InnoDB, utf8mb4)** in all environments for MVP. Laravel's built-in auth, policies, validation, rate limiting and queues cover the riskiest parts of this system, and the stack is already familiar, which shortens the path to a safe pilot.

**Types across the boundary:** generate TypeScript types from PHP using Spatie Laravel Data with its TypeScript transformer, and Laravel Wayfinder for typed route helpers. Run generation in CI so a renamed PHP field fails the build instead of breaking the Vue side silently.

**Pilot hosting:** one 2 GiB / 1 vCPU / 50 GiB DigitalOcean droplet running Nginx, PHP-FPM, **MySQL 8**, a queue worker, Reverb and the scheduler, behind Cloudflare (DNS, DDoS protection, Turnstile). A 1 GiB droplet works with swap and Reverb replaced by polling, but leaves little headroom during an incident burst. Resize the droplet if memory stays above about 60 percent.

**Expected load:** 100 to 500 reports a month is about 3 to 17 a day. Photos add at most roughly 500 MB a month (500 reports × 2 images × about 500 KB), and transfer stays far below the plan allowance.

| Item | Monthly estimate |
|---|---|
| 2 GiB droplet | $12 |
| Automatic droplet backups | about $2.40 (about 20% of the plan price; confirm) |
| Domain | about $1 ($10 to $15 a year) |
| HTTPS, Cloudflare, Turnstile, Telegram alerts | Free |
| Transactional email | Free tier at this volume |
| SMS alerts (optional) | Per message |
| **Total** | **about $15 to $16** |

Prices other than the droplet plans are approximate and must be confirmed before budgeting. A managed MySQL database and object storage are later upgrades that would raise the cost.

**Operating requirements for a self-managed droplet:** SSH keys only, firewall, fail2ban, scheduled OS, PHP and MySQL security updates, off-site encrypted backups with a tested restore, memory, disk and worker monitoring wired to the alerts in the logging section, and a named person responsible for patching.

---

## Security and privacy

- **Privacy notice** shown before submission: purpose, data collected (including contact number and optional location), recipients, retention, and how to contact the municipality about personal data.
- **Data minimization:** collect only what intake and follow-up need; contact number and messages are visible to authorized staff only.
- **Access control:** server-side authorization on every staff route, evidence file, internal note and admin function; role-based permissions; sessions with timeouts; optional two-factor authentication for staff and mandatory for administrators.
- **Anonymous tracking:** high-entropy tokens stored hashed, expiry checked on every request, non-disclosing responses, rate limiting.
- **Files:** signature validation, size and dimension limits, safe processing, random storage names, private storage, no executable uploads.
- **Transport and storage:** HTTPS everywhere including `wss://`; restricted database and storage access; encrypted backups with a documented retention period.
- **Input safety:** escape all user text on render, including messages; strict content security policy; no user content interpreted as code or markup.
- **Logging:** see the logging section; sensitive content stays out of logs.
- **Accessibility:** readable text, keyboard-accessible controls, clear validation errors, responsive layout.
- **Reliability:** no success screen until the report is committed; queue failures retried and alerted.
- **Compliance:** review against the Data Privacy Act of 2012 and National Privacy Commission guidance with the municipality's privacy officer or legal adviser, including retention of reports, evidence, messages and logs.

**MySQL-specific notes:**

- `STRICT_ALL_TABLES` active; oversized inputs raise errors, not silent truncation.
- All tables `InnoDB`; no MyISAM anywhere.
- `utf8mb4` at server, database and connection level.
- Audit immutability via grants + hash chain (verified daily).
- Backups encrypted; `mysqldump --single-transaction` for consistent snapshots.
- Sensitive columns (`contact_number`) never in logs; Monolog redaction processor strips known keys.

---

## MVP acceptance criteria

### Public reporting

- [ ] A resident can submit a report without registering or logging in.
- [ ] The chat collects incident type, location, description, reporter name and contact number, and the on-behalf toggle.
- [ ] Browser geolocation, a draggable pin and typed location all work, and typed location alone is enough.
- [ ] The resident can attach at most two valid images; they are validated and stored as WebP with EXIF removed.
- [ ] Submission works on a poor connection: text saves first, images retry, and a repeated submission never creates a duplicate.
- [ ] A unique reference code and secure tracking link are produced, with copy and share buttons.
- [ ] English and Tagalog are selectable; Hiligaynon and Aklanon strings load when provided.

### Tracking and expiration

- [ ] A valid link shows current status and the permitted public timeline.
- [ ] Invalid, revoked and expired tokens return the same response and reveal nothing.
- [ ] Access expires exactly seven days after submission by server time, enforced on every request.
- [ ] A lost link cannot be recovered from the reference code alone.
- [ ] Expiry never deletes the official record.
- [ ] The reporter can message staff and receive replies, and live updates fall back to polling when the socket is down.

### Staff and notifications

- [ ] Staff can search, filter and open reports, set status and priority, assign, and post public updates.
- [ ] A new report triggers an email and in-dashboard alert within one minute, and unacknowledged reports escalate.
- [ ] Internal notes, contact numbers and restricted evidence are never exposed publicly.
- [ ] Duplicate suggestions appear and staff can mark reports Duplicate, Invalid or Unable to verify with a reason.

### Logging

- [ ] Every audit event listed in the logging section is recorded with actor, time, IP and correlation ID.
- [ ] The audit table cannot be edited or deleted through the application.
- [ ] Logs never contain tokens, descriptions, contact numbers, message bodies or images.
- [ ] Alerts fire for stopped workers, repeated notification failures and Reverb downtime.

### Security and usability

- [ ] The experience works on mobile and desktop and meets basic accessibility checks.
- [ ] Oversized, malformed and unsupported uploads are rejected safely.
- [ ] Turnstile and rate limits protect submission, tracking, messaging and upload endpoints.
- [ ] Every public screen directs life-threatening emergencies to the hotline.

### MySQL specifics

- [ ] MySQL 8.0+ running; `ST_Distance_Sphere` verified on the target host.
- [ ] `audit_logs` insert-only enforced: `UPDATE audit_logs` as `mdrrmo_app` fails with `ER_TABLEACCESS_DENIED_ERROR`.
- [ ] Hash chain verifies end-to-end; a deliberate row edit causes the verification job to alert.
- [ ] Duplicate detection returns candidates within 200 m / 30 min for the same category.
- [ ] All tables `utf8mb4_unicode_ci`; Hiligaynon and Aklanon strings round-trip without corruption.
- [ ] `STRICT_ALL_TABLES` active; oversized inputs raise errors, not silent truncation.
- [ ] `SPATIAL INDEX` present on `location_point`; query planner uses it for bbox queries.

---

## Development phases

1. **Phase 1, core reporting:** landing page, guided chat, categories, location and geolocation, contact number, text-first submission with idempotency, image pipeline, reference generation, English and Tagalog strings, base logging with correlation IDs, **MySQL users and grants bootstrap, audit writer and chain verifier wired into CI**.
2. **Phase 2, anonymous tracking:** hashed tokens, tracking page, status history, seven-day expiry enforced per request, expiration messaging, link-saving aids.
3. **Phase 3, staff dashboard:** staff authentication and roles, queue, filters, report details, status and priority, assignments, public updates, duplicate and junk handling, email and in-dashboard notifications, escalation, audit log.
4. **Phase 4, realtime and messaging:** Reverb setup, staff channel, token-authorized report channels, two-way messaging with moderation, polling fallback, SMS or Telegram notification.
5. **Phase 5, hardening and deployment:** Turnstile and rate-limit tuning, upload and access-control testing, log redaction tests, alerts, retention jobs, backups, mobile and low-signal testing, privacy review, staff training and operating procedures.
6. **Phase 6, optional enhancements:** map-based selection refinements, OCR and QR detection, browser push, analytics, Hiligaynon and Aklanon completion, integrations with existing municipal systems, and dispatch coordination only if the MDRRMO has an agreed procedure.

---

## Open decisions

| Decision | Recommendation | Owner |
|---|---|---|
| Hosting: VPS, shared hosting or managed platform | VPS, so Reverb and queue workers run reliably | Developer and office |
| Dispatch coordination in scope? | No for the MVP; keep intake and tracking only | MDRRMO head |
| Contact number required or optional? | Required, since follow-up calls depend on it | MDRRMO head |
| Staff alert channel beyond email (SMS or Telegram) | Telegram bot first; SMS if a gateway budget exists | MDRRMO head |
| Record, evidence, message and log retention periods | Set by the privacy officer; proposals are in this document | Privacy officer |
| Delete anything at seven days, or only end public access? | End public access only | Privacy officer |
| Official hotline numbers and approved category list | Needed before the landing page is final | MDRRMO |
| Hiligaynon and Aklanon translation review | Office staff review the wording | MDRRMO |
| Staff two-factor authentication | Mandatory for administrators | Developer |
| Original-image preservation policy | Define only if forensic-quality evidence is needed | MDRRMO and legal |
| MySQL 8.0+ as the MVP minimum | Yes; MariaDB 10.5+ acceptable with Haversine fallback | Developer |
| Postgres/PostGIS migration path | Keep repositories around spatial + search; do not rewrite callers | Developer |
| Audit payload column | Store canonical `payload` JSON in `audit_logs` for deterministic chain verification | Developer |
| `mdrrmo_audit` user separate from `mdrrmo_app` | Yes, in all environments including dev | Developer |
