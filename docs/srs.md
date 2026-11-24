# Software Requirements Specification (SRS)
## MDRRMO Emergency Response Reporting Web App

**Document version:** 1.0
**PRD baseline:** v1.2 (Oct 12, 2026)
**Date:** Oct 12, 2026
**Author:** @Im Albert
**Status:** For review — contains completion criteria per requirement

---

## Table of contents

1. Introduction
2. Overall description
3. System architecture
4. Functional requirements
5. Data requirements
6. Interface requirements
7. Non-functional requirements
8. Security and privacy requirements
9. Compliance requirements
10. Acceptance criteria
11. Traceability matrix
12. Requirement completion definitions
13. Appendices

---

## 1. Introduction

### 1.1 Purpose

This SRS specifies the functional and non-functional requirements for the MDRRMO Emergency Response Reporting Web App, a mobile-friendly, account-free web application that lets residents of the municipality report emergencies to the MDRRMO (Municipal Disaster Risk Reduction and Management Office), receive a reference code, and track their report's progress for seven days. It also specifies the staff dashboard used to triage, respond to, and close reports, and the surrounding logging, notification, and abuse-prevention subsystems.

This document is the contract between the MDRRMO (as the commissioning office) and the developer. It is written to be testable: every requirement has an identifier, a priority, and an explicit **completion definition** — the observable, verifiable condition that marks it done.

### 1.2 Scope

**In scope (MVP):**

- Anonymous public reporting via a guided chat
- Location capture (typed address or optional current-position pin through browser geolocation)
- Evidence images (up to two, processed and stored privately)
- Reference code and private tracking link with seven-day expiry
- Public status tracking page
- Staff dashboard with queue, filters, report detail, status and priority, assignment, public updates, internal notes, and two-way messaging
- Staff notifications (email, in-dashboard, optional Telegram or SMS)
- Realtime updates via Laravel Reverb with polling fallback
- Full logging: audit, application, security, job and delivery
- Abuse prevention: Turnstile, rate limits, duplicate detection, junk handling
- Multi-language: English, Tagalog, and Akeanon

**Out of scope (MVP):**

- Dispatch coordination and responder routing
- Hotline replacement
- Public accounts, login, or profiles for residents
- Analytics dashboards beyond the operational summary counts in the staff dashboard
- OCR, plate detection, or QR decoding (Phase 6)
- Native mobile apps
- Integrations with third-party municipal systems

### 1.3 Definitions, acronyms, abbreviations

| Term | Meaning |
|---|---|
| MDRRMO | Municipal Disaster Risk Reduction and Management Office |
| PRD | Product Requirements Document |
| SRS | Software Requirements Specification |
| ADR | Architecture Decision Record |
| MVP | Minimum Viable Product |
| ULID | Universally Unique Lexicographically Sortable Identifier |
| RLS | Row-Level Security (Postgres feature; not used on MySQL) |
| Turnstile | Cloudflare's privacy-respecting bot challenge |
| Reverb | Laravel's first-party WebSocket server |
| EXIF | Exchangeable image file format metadata (may contain GPS) |
| WebP | Image format used for processed evidence |
| PH mobile format | Philippine mobile number, e.g. `+63 9XX XXX XXXX` |
| Tracking token | Cryptographically random secret authorizing access to one report |

### 1.4 References

- PRD v1.2, MDRRMO Emergency Response Reporting Web App, Oct 12, 2026
- Data Privacy Act of 2012 (Republic Act 10173), Philippines
- National Privacy Commission issuances on personal data protection
- Laravel 12 documentation
- Laravel Reverb documentation
- Cloudflare Turnstile documentation
- ISO/IEC/IEEE 29148:2018 (SRS structure)

### 1.5 Document conventions

- Requirement identifiers: `FR-<area>-<n>` for functional, `NFR-<area>-<n>` for non-functional, `SEC-<n>` for security, `PRIV-<n>` for privacy, `COMP-<n>` for compliance.
- Priority: **M** (Must, MVP), **S** (Should, target MVP), **C** (Could, Phase 6).
- **Completion definition** is the single, observable condition that a reviewer can verify without reading code.

---

## 2. Overall description

### 2.1 Product perspective

The system is a self-contained Laravel 12 monolith. It does not replace the emergency hotline and does not assert that a submitted report resulted in a dispatch. It is an intake and tracking tool used by the MDRRMO alongside its existing operations.

### 2.2 Product functions (summary)

- Accept anonymous emergency reports from residents
- Capture location, description, optional images, and reporter contact
- Generate a reference code and a private tracking link
- Notify staff immediately of new reports
- Provide staff a triage dashboard
- Allow two-way messaging between staff and reporter
- Provide live status updates and a seven-day public timeline
- Record every staff and administrator action in an append-only audit log
- Protect against bots, duplicates, and junk reports
- Enforce retention and expiry rules

### 2.3 User classes and characteristics

| User class | Characteristics | Frequency of use | Critical needs |
|---|---|---|---|
| Public reporter | Resident, any age, on a mobile phone, possibly under stress or poor signal | Occasional, emergency-driven | Fast, clear, works on weak connections |
| MDRRMO staff | Trained office staff, desktop or tablet, in an office or on duty | Daily | Fast triage, clear queue, safe actions |
| MDRRMO administrator | Senior staff with account and settings authority | Weekly plus ad hoc | Auditable control, safe configuration |
| Privacy officer | Municipal officer responsible for personal data | Ad hoc, on review cycles | Access to audit and retention controls |

### 2.4 Operating environment

| Component | Requirement |
|---|---|
| Server OS | Linux (VPS or equivalent) |
| Web server | Nginx |
| Runtime | PHP 8.2+ (8.3 recommended), PHP-FPM |
| Framework | Laravel 12 |
| Database | MySQL 8.0+ (InnoDB, `utf8mb4`, `STRICT_ALL_TABLES`) |
| Queue | Database queue (MVP); Redis optional |
| Scheduler | Cron |
| WebSockets | Laravel Reverb, behind Nginx `wss://` |
| Cache / session | Database (MVP); Redis optional |
| Storage | Private local storage (MVP); object storage optional |
| CDN / edge | Cloudflare (DNS, DDoS, Turnstile) |
| Client | Any modern mobile or desktop browser; server-rendered first paint |

### 2.5 Design and implementation constraints

- **C-1** (M): Database for MVP is MySQL 8.0+. MariaDB 10.5+ is acceptable if `ST_Distance_Sphere` parity is unavailable, with a Haversine fallback.
- **C-2** (M): All tables use `InnoDB` and `utf8mb4_unicode_ci`.
- **C-3** (M): Internal primary keys are ULIDs (`CHAR(26)`), except `audit_logs` (auto-increment) and `users`/`incident_categories` (auto-increment).
- **C-4** (M): Tracking tokens are stored only as SHA-256 hex in `CHAR(64)` columns.
- **C-5** (M): Public pages must not require JavaScript for first paint; the guided chat is progressive.
- **C-6** (M): No third-party scripts on public pages except Cloudflare Turnstile.
- **C-7** (M): Realtime is an enhancement, not a dependency; polling must be a supported fallback.
- **C-8** (M): Every public screen directs life-threatening emergencies to the hotline.
- **C-9** (M): The system is not a dispatch system and must not imply dispatch.

### 2.6 Assumptions and dependencies

- The MDRRMO provides official hotline numbers, an approved category list, and a monitored staff email address before Stage 01 is finalized.
- The privacy officer confirms retention periods before public launch.
- A Turnstile site key and secret are available before Stage 05.
- A Telegram bot (or SMS gateway) is available before Stage 04 if that channel is enabled.
- Host supports Imagick or GD with WebP; verified in Stage 00.
- Host supports a persistent process for Reverb; otherwise realtime falls back to polling.

---

## 3. System architecture

### 3.1 Logical architecture

One Laravel 12 monolith with Vue 3, Inertia, and Tailwind. Modules are logical, not separate packages: `PublicReporting`, `IncidentManagement`, `ReportTracking`, `EvidenceManagement`, `Messaging`, `Notifications`, `StaffAdministration`, `AuditLog`, `RetentionManagement`.

### 3.2 Component responsibilities

| Component | Responsibility |
|---|---|
| Laravel 12 app | Validation, authorization, workflow, tracking, dashboard |
| Vue 3 + Inertia + Tailwind | Public pages, guided chat, tracking, dashboard |
| MySQL 8.0+ | Persistence |
| Private file storage | Processed WebP evidence |
| Queue worker | Image processing, notifications, broadcasts |
| Scheduler | Token expiry cleanup, draft purge, escalation, log pruning, audit-chain verification |
| Laravel Reverb | WebSocket delivery |
| Cloudflare Turnstile | Bot challenge |

### 3.3 Deployment view

Single 2 GiB / 1 vCPU / 50 GiB VPS running Nginx, PHP-FPM, MySQL 8, queue worker, Reverb, and cron. Cloudflare in front. Backups: scheduled droplet snapshots plus encrypted off-site `mysqldump`.

### 3.4 Data flow (high level)

1. Reporter → chat → submission endpoint → `incident_reports` row + `report_tracking_tokens` row → confirmation.
2. Reporter → evidence endpoint → temp storage → queue job → private storage + `incident_evidence` row.
3. Report creation → broadcast event (staff channel) + email + `notification_deliveries` row.
4. Staff action → status/priority/assignment/message → history row + audit row + broadcast event.
5. Reporter tracking request → token hash validation + expiry → public timeline (public fields only).

---

## 4. Functional requirements

Every requirement has an identifier, priority, description, and completion definition.

### 4.1 Landing and public pages

**FR-PUB-1** (M): The landing page must display MDRRMO branding, a short service explanation, official hotline numbers, life-threatening-situation instructions, a Report an Incident button, a Track a report link, a privacy notice, service limitations, and a language switch.

- **Completion:** A reviewer loads `/` on a mobile browser and sees all nine elements; the hotline warning appears above the fold; the language switch changes visible strings between English and Tagalog.

**FR-PUB-2** (M): The reporting page must present a guided chat with a progress indicator and validated inputs.

- **Completion:** A reviewer completes the chat end-to-end with valid inputs; invalid inputs block progression with a clear message; the progress indicator advances and regresses correctly.

**FR-PUB-3** (M): The in-page confirmation must display the reference code, private tracking link, copy and share controls, expiry date, and an option to start another report without reloading the page.

- **Completion:** The in-page confirmation shows the reference, tracking URL, and expiry; Copy copies the URL; Share uses the native share sheet where supported; Submit another report returns to the first step without a page reload.

**FR-PUB-4** (M): The tracking page must display reference code, submission time, current status, staff status-change timeline, attached photo when present, reporter/staff message thread, expiry countdown, and a clear expired message when applicable. Status and messages refresh in place.

- **Completion:** With a valid link, status history and messages render and refresh without navigating away; with an expired link, the protected page and photo become inaccessible and no report details are returned.

**FR-PUB-5** (M): All public pages must be usable on low-end phones.

- **Completion:** Lighthouse mobile performance ≥ 80 on the landing and chat pages; first paint is server-rendered; total blocking JS on first load ≤ 150 KB compressed.

### 4.2 Guided report chat

**FR-CHAT-1** (M): The chat must ask incident type first, from active categories, and require a selection.

- **Completion:** Categories are loaded from `incident_categories` where `active = 1`; submission is blocked without a selection.

**FR-CHAT-2** (M): The guided form must accept a location either as manually typed text (barangay, street, landmark) or as a current-location pin placed through the website location control.

- **Completion:** At least one of typed location or valid latitude/longitude is required; if only coordinates are provided, a readable coordinate label is stored in `incident_reports.location_text`.

**FR-CHAT-3** (M): The location step must offer a "Use my current location" control that asks the browser to pin coordinates and stores latitude, longitude, and accuracy; typed location remains sufficient on its own.

- **Completion:** On permission grant, latitude, longitude, and accuracy populate the request and the report may submit with coordinates only; on denial or unavailability, the form may submit with only typed location.

**FR-CHAT-4** (M): The chat must require a description with configurable minimum and maximum length.

- **Completion:** Description below minimum or above maximum is rejected with a message; length limits come from `settings`.

**FR-CHAT-5** (M): The chat must offer a "Reporting for someone else" toggle; if enabled, it asks for relationship and whether the victim can be contacted.

- **Completion:** Enabling the toggle reveals both fields; disabling it clears them; the two values persist on `incident_reports.relationship` and `.victim_contactable`.

**FR-CHAT-6** (M): The chat must require reporter name and reporter contact number, with PH mobile format validation, and contact is stored privately and never shown on the tracking page.

- **Completion:** Invalid PH mobile formats are rejected with a specific message; the value is present in staff views only; a public tracking response contains no contact number in body, headers, or JSON.

**FR-CHAT-7** (M): The guided form may attach one optional evidence photo captured only through the in-page camera control. It must not provide a file-picker upload path.

- **Completion:** The camera icon opens a live camera preview, capture creates a reviewable image, retake is available, and camera denial or absence does not block a report without a photo.

**FR-CHAT-8** (M): Language must be selectable from the public-page navbar among English, Tagalog, and Akeanon. Changing the selection must render the destination page in that language.

- **Completion:** Selecting a language changes visible strings; categories and public status messages render from the translated set; missing translations fall back to English without an error.

**FR-CHAT-9** (M): Progress must be saved as a draft in browser storage between steps, labelled unsent, and discarded after 24 hours.

- **Completion:** Reloading mid-chat restores progress; the label states "unsent"; drafts older than 24 hours do not restore.

**FR-CHAT-10** (M): The client must generate an idempotency key for submission, and a repeated submission with the same key must return the same report.

- **Completion:** Submitting twice with the same key creates one `incident_reports` row, preserves one reference code, and returns a usable tracking link on retry; duplicate clicks are prevented while the first request is pending.

### 4.3 Submission and reference generation

**FR-SUB-1** (M): Submission must commit validated report fields, optional captured photo, coordinates, status event, tracking token, and idempotency key as one report workflow.

- **Completion:** A successful response contains a reference code, expiry time, and working tracking URL; a repeated request with the same idempotency key returns the same report and a usable tracking URL.

**FR-SUB-2** (M): The server must generate a unique reference code of the form `MDR-YYYY-XXXXXX`.

- **Completion:** `reference_code` is unique; the pattern is validated in tests; collisions are retried server-side.

**FR-SUB-3** (M): The server must generate a 32-byte random tracking token, store only `token_hash CHAR(64)`, and return the raw token only in the private response. A same-key retry may rotate the token and revoke its predecessor so a lost response can be recovered.

- **Completion:** Database contains only the hash; the raw token appears in the confirmation response and nowhere in logs; `hash_equals` is used for comparison.

**FR-SUB-4** (M): The server must set `submitted_at` and `expires_at = submitted_at + 7 days`.

- **Completion:** Fields are populated server-side and matched in tests.

**FR-SUB-5** (M): The submission endpoint must be protected by Turnstile, with fallback to rate limits if Turnstile fails to load.

- **Completion:** With Turnstile configured, valid tokens pass and invalid tokens are rejected; with Turnstile unavailable, rate limits still apply and legitimate submissions are not blocked.

**FR-SUB-6** (M): The success screen must appear only after the server has committed the report and tracking record.

- **Completion:** Simulated commit failure returns a non-success state; the confirmation page never renders on failure.

### 4.4 Evidence images

**FR-IMG-1** (M): The browser must capture one optional photo using `getUserMedia` behind the website camera icon, downscale it to a maximum 1,600 px longest edge, and encode it as JPEG before submission.

- **Completion:** No file input is present; camera permission is requested only after the user activates the camera; captured preview and retake are available; camera permission failure leaves the report flow usable.

**FR-IMG-2** (M): The server must validate the uploaded image content and accept JPEG, PNG, or WebP up to 4 MB; unsupported, corrupt, or oversized images are rejected.

- **Completion:** A valid captured image is stored under a randomized private path; invalid image content receives a field-level validation response. Browser canvas re-encoding removes original camera metadata from the captured output.

**FR-IMG-3** (M): Evidence must be stored on the private local disk and associated with the report only after a successful report transaction; failed report creation must clean up the stored file.

- **Completion:** No public storage URL is generated; report records retain only the private storage path; failures leave no orphan evidence file.

**FR-IMG-4** (M): A report photo may be viewed only by a staff/admin session or the report's valid, unexpired, unrevoked tracking token; evidence responses must be private and non-cacheable.

- **Completion:** Unauthenticated, wrong-role, invalid-token, expired-token, and revoked-token requests cannot fetch the photo; staff and the valid reporter link can display it.

**FR-IMG-5** (M): Limit: one optional camera-captured JPEG, maximum 1,600 px longest edge and 4 MB request file size; text-only submission remains available.

- **Completion:** Browser capture applies the dimension limit and the API enforces the server file-size and image-content limits.

### 4.5 Tracking and expiration

**FR-TRK-1** (M): Tracking must require the private token; a reference code alone must not grant access.

- **Completion:** Requesting the tracking page with only the reference code returns the non-disclosing response.

**FR-TRK-2** (M): The server must validate the token hash and the current server time on every request.

- **Completion:** Server time is the sole source of expiry; modifying `expires_at` to the past immediately denies access.

**FR-TRK-3** (M): Invalid, revoked, and expired tokens must return the same non-disclosing response, rate limited per IP.

- **Completion:** All three cases return identical bodies and status codes; rate-limit counters increase.

**FR-TRK-4** (M): Expiry must never delete the official report.

- **Completion:** After expiry, the row remains in `incident_reports`; the tracking token is unusable; `expires_at` in the past is the only visible difference.

**FR-TRK-5** (M): The tracking page must send `Referrer-Policy: no-referrer`, `X-Robots-Tag: noindex`, and `Cache-Control: no-store`.

- **Completion:** Response headers verified in tests; no third-party analytics script is loaded.

**FR-TRK-6** (M): A saved-reports list must persist links in local storage and be wiped on expiry.

- **Completion:** On the same device, reopening the link works from the saved list; after expiry, the entry is removed or marked expired and cannot be used.

**FR-TRK-7** (M): Every staff status change must be stored as a status event and appear in the reporter's timeline; the tracking page must poll for status changes without exposing reporter contact details.

- **Completion:** Moving a report from Submitted to In progress and then Resolved creates ordered, timestamped timeline entries; the tracking view updates within one polling interval and its API response contains no contact number.

### 4.6 Staff dashboard

#### Role-based access control

All operational users must sign in. Authorization is enforced on the server for routes and actions; hiding a control in the interface is not authorization. The `admin` role includes the core report-operating permissions of `staff` and adds system-administration and moderation privileges.

| Role | Permissions |
|---|---|
| `staff` | Open the operations dashboard, queue, and live map; review and triage reports; exchange messages with reporters; manage own profile; participate in the news workflow. Cannot manage system users, settings, moderation, or administrative reports/exports. |
| `admin` | All `staff` permissions, plus manage users and their `admin`/`staff` roles, configure system settings, access administrative reports and exports, and perform moderation and response-control actions such as bans, report acceptance, and responder dispatch. |
| `mdrrmo_officer` | Additional application role currently accepted at sign-in and authorized for the news workflow. Not a substitute for `staff` or `admin` access to report operations or system administration. |
| `information_officer` | Additional application role currently accepted at sign-in and authorized for the news workflow. Not a substitute for `staff` or `admin` access to report operations or system administration. |

The role names and permissions above are server-side authorization categories, not a hierarchy that grants access merely because a role name contains “officer.” User administration currently permits creating and assigning only `admin` and `staff` accounts. The additional news roles require an explicit account setup path before operational use.

**Implementation alignment (2026-11-20):** The current routes enforce the shared `staff`/`admin` report-operation access and restrict user management, settings, moderation, and administrative reporting to `admin`. Feature tests verify staff denial for several admin-only routes and self-profile role protection, but do not yet constitute the complete cross-role authorization matrix required below. The news routes also explicitly permit `mdrrmo_officer` and `information_officer`; these roles are not represented in the original two-role requirement and should be retained only if they are intended product roles. The SRS's settings scope and audit requirements are broader than the currently exposed settings and audit routes; see FR-STF-9 and FR-ADM-1 below.

**FR-STF-1** (M): Staff and administrators must sign in. Both roles may access core report operations; administrator-only routes and actions must be separately restricted to `admin`. Every protected route, action, and file must be authorized server-side, with no client-only gating.

- **Completion:** Authorization tests cover each role against shared operations, admin-only operations, and role-specific news access; unauthorized combinations are denied and authorized combinations succeed. No client-only gating exists.

**FR-STF-2** (M): The queue must show reference code, category, priority, location, submitted time, and status, with search, filters (status, category, priority, date range), and pagination.

- **Completion:** Each filter narrows results correctly; search matches reference code and description; pagination preserves filters.

**FR-STF-3** (M): New and unacknowledged reports must sort to the top.

- **Completion:** A freshly created report appears at the top before older acknowledged reports; ordering uses `idx_queue`.

**FR-STF-4** (M): The report detail must show full report, map pin, evidence via signed URLs, reporter contact (staff only), status history, public updates, internal notes, and the message thread.

- **Completion:** All listed elements render for a report with history; contact is absent from any public response.

**FR-STF-5** (M): Actions must support change status, set priority, assign, post public update, and mark duplicate or invalid with a required reason.

- **Completion:** Each action writes its corresponding row(s) and an `audit_logs` entry; marking duplicate or invalid without a reason is rejected.

**FR-STF-6** (M): Priority must be Low, Medium, High, Critical; independently set from category; never auto-set from category.

- **Completion:** Changing category does not change priority; changing priority is logged.

**FR-STF-7** (M): Status must follow the lifecycle in PRD §Status lifecycle: Submitted → Under review → Acknowledged → Assigned → In progress → Resolved → Closed, plus Duplicate/Invalid/Unable to verify terminal states.

- **Completion:** Transitions are validated; every change records previous and new status, public explanation, optional internal note, actor, and server timestamp.

**FR-STF-8** (M): Overview counts must show counts by status, category, and date range.

- **Completion:** Counts match a hand-verified fixture set.

**FR-STF-9** (M): Administrators must be able to manage categories, public status messages, notification channels, languages, system settings, and staff accounts and roles. Staff may use the operational dashboard but cannot change these system-wide settings or manage accounts.

- **Completion:** Each change is possible in the UI, persisted, and audited.

- **Implementation comparison:** Current admin routes expose report-intake settings, command-center location/logo, and alarm audio, plus user management. Category, public status-message, notification-channel, and language management are not represented by the current admin settings routes, so this requirement is not fully met by the routes currently in place.

### 4.7 Notifications

**FR-NOT-1** (M): A new report must email a monitored office address with reference, category, priority-less summary, and a link to the dashboard; the email body must not contain contact number or photos.

- **Completion:** A generated email contains the listed fields and no contact number or image; delivery outcome is recorded in `notification_deliveries`.

**FR-NOT-2** (M): The dashboard must show an in-dashboard alert (sound and banner) delivered over WebSockets, with polling as fallback.

- **Completion:** New report triggers the alert within one minute over WebSockets; killing Reverb still delivers the alert via polling within the polling interval.

**FR-NOT-3** (S): An optional SMS or Telegram bot notification must be available to the duty officer.

- **Completion:** If enabled, a new report produces a message in the configured channel; the outcome is recorded.

**FR-NOT-4** (M): If a report stays in Submitted past a configurable threshold (for example 10 minutes), the system must re-notify and flag it in the queue.

- **Completion:** A test report that ages past the threshold triggers a second notification and the flag appears in the queue.

**FR-NOT-5** (M): Every notification attempt and failure must be logged.

- **Completion:** `notification_deliveries` records each attempt with channel, attempt count, outcome, and timestamps.

### 4.8 Messaging and realtime

**FR-MSG-1** (M): A message thread must exist between staff and reporter with sender type, body, timestamp, and staff actor.

- **Completion:** `report_messages` rows match the thread on both sides; sender type is correct.

**FR-MSG-2** (M): Messages must be plain text, length-limited, rate-limited per token, and rendered escaped.

- **Completion:** HTML/script content in a message renders as text; oversized messages are rejected; rate limits apply.

**FR-MSG-3** (M): Staff must be able to close a thread on a report.

- **Completion:** After closing, no new reporter messages are accepted on that report; the state is logged.

**FR-MSG-4** (M): The reporter channel must be authorized by the tracking token, checked server-side on every connect and every message.

- **Completion:** A valid token subscribes; an expired or revoked token is rejected; a token from report A cannot read report B.

**FR-MSG-5** (M): Broadcast payloads must be minimal (report ID, new status, message ID); sensitive data (contact number, internal notes, evidence URLs) must never be broadcast.

- **Completion:** Captured broadcast payloads contain only the allowed keys; a scan of broadcast logs shows no sensitive fields.

**FR-MSG-6** (M): When the WebSocket is unavailable, tracking and dashboard must fall back to polling every 10 to 20 seconds.

- **Completion:** With Reverb stopped, updates appear within one polling interval on both pages.

**FR-MSG-7** (S): Staff presence ("Ana is viewing") on a report via a presence channel is optional.

- **Completion:** When enabled, presence appears and clears within one interval of join and leave.

### 4.9 Abuse prevention

**FR-ABU-1** (M): Cloudflare Turnstile (or equivalent) must protect submission, invisible by default, with rate-limit fallback.

- **Completion:** Invalid Turnstile tokens are rejected; a failed Turnstile widget load does not block legitimate submission and rate limits still apply.

**FR-ABU-2** (M): Rate limits must apply per IP and per device cookie on submission, tracking lookups, message sending, and upload endpoints, with stricter limits on failed token lookups.

- **Completion:** Exceeding limits returns 429; thresholds are configurable via `settings`.

**FR-ABU-3** (M): Duplicate detection must flag new reports with the same category within a configurable radius and time window (for example 200 m and 30 minutes).

- **Completion:** Two reports in the same category within 200 m and 30 minutes produce a suggestion; staff can link or dismiss it; no automatic merge occurs.

**FR-ABU-4** (M): Staff must be able to mark a report Invalid or Unable to verify with a required reason, bulk-select likely junk, and flag an IP or device token for review.

- **Completion:** Each action works, requires a reason where applicable, and is logged.

**FR-ABU-5** (M): No hard blocks on first contact; a suspected abusive source must be rate limited and logged, and the report must still reach staff with a suspicion flag.

- **Completion:** A suspicious report appears in the queue with `suspicion_flag = 1`; its rate limit applies; no automatic deletion occurs.

### 4.10 Logging

**FR-LOG-1** (M): Four separate logs must exist: audit (`audit_logs`), security (JSON channel), application (daily channel), job and delivery (`failed_jobs` + delivery table).

- **Completion:** Each log receives entries in its format; a reviewer can locate a specific event in the correct log.

**FR-LOG-2** (M): A correlation ID (ULID) must be generated per request and carried through queued jobs and broadcasts.

- **Completion:** One request produces log lines with the same correlation ID across web, queue, and broadcast; tracing a report end-to-end by correlation ID is possible.

**FR-LOG-3** (M): The audit log must record actor, role, timestamp, IP, correlation ID, target record, and before/after summaries for every listed action.

- **Completion:** Each action in the PRD audit list produces an entry with all listed fields; a fixture audit run produces the full set.

**FR-LOG-4** (M): The audit table must be insert-only, enforced by the database user's grants, and backed by a hash chain (`prev_hash`, `row_hash`).

- **Completion:** `UPDATE` or `DELETE` on `audit_logs` as `mdrrmo_app` fails with `ER_TABLEACCESS_DENIED_ERROR`; a deliberate row edit causes `audit:verify` to fail and alert.

**FR-LOG-5** (M): Logs must never contain raw tracking tokens, reporter names, contact numbers, incident descriptions, message bodies, image contents, passwords, session IDs, or full request bodies.

- **Completion:** A redaction test runs a scenario containing these values and confirms none appear in any log output.

**FR-LOG-6** (M): Alerts must fire for stopped queue workers, repeated notification failures, Reverb downtime, failed token lookup spikes, image processing failure thresholds, audit-chain verification failure, and disk usage over 80 percent.

- **Completion:** Each alert is reproducible in a test or simulated environment and reaches the administrator.

### 4.11 Retention

**FR-RET-1** (M): Public tracking access must expire exactly seven days after submission, enforced on every request.

- **Completion:** At `submitted_at + 7 days + 1 second` (server time), the tracking page shows only the expiration message.

**FR-RET-2** (M): Tracking tokens must be unusable at expiry.

- **Completion:** Post-expiry requests, including direct token submissions, are rejected.

**FR-RET-3** (M): The official report record must remain after expiry, per the municipality's records policy.

- **Completion:** The `incident_reports` row is present after expiry; only access is ended.

**FR-RET-4** (M): Retention values for reports, evidence, messages, and each log must be configurable and documented.

- **Completion:** Settings exist; proposed defaults match the PRD; the privacy officer's confirmed values can be set without code change.

### 4.12 Internationalization

**FR-I18N-1** (M): English and Tagalog must ship with the MVP.

- **Completion:** Every user-visible string has both translations; switching language changes all visible strings.

**FR-I18N-2** (C): Akeanon translation strings must be served from the Akeanon locale file.

- **Completion:** Adding a language file registers the language in the switch and renders strings when present.

**FR-I18N-3** (M): Category names and public status messages must be translatable per language in admin settings.

- **Completion:** Changing a category's translated name in the admin updates public output.

### 4.13 Accessibility

**FR-ACC-1** (M): Public and dashboard pages must meet basic accessibility checks (keyboard navigation, readable text, clear validation errors, responsive layout).

- **Completion:** Automated checks pass on landing, chat, tracking, and dashboard; manual keyboard-only walkthrough completes reporting and triage.

### 4.14 Audit and administrative exports

**FR-ADM-1** (M): Administrators must be able to search and export the audit log; nobody may edit it.

- **Completion:** Search and export work; edit and delete are impossible via the application.

- **Implementation comparison:** The current web routes do not expose an admin audit search/export endpoint; this remains an SRS requirement rather than an implemented route.

**FR-ADM-2** (M): Staff account and permission changes must be audited.

- **Completion:** Creating, modifying, deactivating a staff or admin account, or changing a role, produces an audit entry.

---

## 5. Data requirements

### 5.1 Entity list

`incident_reports` (including private `photo_path`), `report_status_events`, `incident_categories`, `report_tracking_tokens`, `report_messages`, `users`, `notification_deliveries`, `audit_logs`, `settings`, plus Laravel defaults (`jobs`, `failed_jobs`, `cache`, `sessions`).

### 5.2 Column-level specifications

See PRD v1.2 §Core tables for full column types and constraints. This SRS inherits them verbatim.

### 5.3 Indexes

- `UNIQUE(reference_code)`
- `UNIQUE(idempotency_key)`
- `idx_queue (status, priority, submitted_at)`
- `idx_dup (category_id, submitted_at)`
- `idx_geo (latitude, longitude)`
- `SPATIAL(location_point)`
- `FULLTEXT ft_search (description, location_text)`
- `UNIQUE(token_hash)`
- Audit and history indexes as specified in PRD v1.2.

**Completion:** `EXPLAIN` on the queue query, duplicate-detection query, and search query uses the intended index; documented in Stage 01 and Stage 03 reports.

### 5.4 Data retention

| Data | Retention | Enforcement |
|---|---|---|
| Tracking access | 7 days | Per-request check |
| Tracking token | Unusable at expiry | Per-request check |
| Incident report | Per municipal records policy | Manual, by policy |
| Evidence | Per evidence and privacy policy | Manual, by policy |
| Audit log | ≥ 1 year (proposed) | Retention job |
| Security log | 90–180 days (proposed) | Retention job |
| Application log | 14–30 days (proposed) | Retention job |
| Job and delivery log | 30–90 days (proposed) | Retention job |

### 5.5 Data integrity rules

- `expires_at` is server-set; client-supplied values are ignored.
- `status` transitions are validated; invalid transitions are rejected.
- `priority` is never derived from `category`.
- `audit_logs` is insert-only via grants and hash chain.
- `report_tracking_tokens.token_hash` is unique.

### 5.6 Backup and recovery

- Nightly encrypted off-site backup of the database and private storage.
- Documented restore procedure; a restore drill performed before public launch.
- **Completion:** `docs/runbooks/restore-backup.md` exists; a drill is logged with results.

---

## 6. Interface requirements

### 6.1 Public HTTP endpoints (illustrative, not exhaustive)

| Method | Path | Auth | Purpose |
|---|---|---|---|
| GET | `/` | None | Landing |
| GET | `/report` | None | Guided chat |
| POST | `/api/reports` | Turnstile + rate limit | Submit report and optional camera photo as multipart form data |
| GET | `/track?reference={code}&token={token}` | Private token | Tracking page |
| GET/POST | `/track/{token}/messages` | Private token | Read or send reporter messages |
| GET | `/track/{token}/photo` | Private token | View attached photo |
| GET | `/api/reports/{reference}/status` | Tracking token header | Poll status |

**Completion:** Each endpoint has an integration test; auth failures return the documented status codes.

### 6.2 Staff HTTP endpoints (illustrative)

| Method | Path | Auth | Purpose |
|---|---|---|---|
| GET | `/dashboard` | Staff session | Queue |
| GET | `/dashboard/reports/{id}` | Staff session | Detail |
| POST | `/dashboard/reports/{id}/status` | Staff session | Change status |
| POST | `/dashboard/reports/{id}/priority` | Staff session | Change priority |
| POST | `/dashboard/reports/{id}/assign` | Staff session | Assign |
| POST | `/dashboard/reports/{id}/messages` | Staff session | Reply |
| GET | `/admin/settings` | Admin session | Settings |
| GET | `/admin/audit` | Admin session | Audit search and export |

### 6.3 Broadcast channels

- `private-staff.reports` — new reports and queue refresh.
- `private-report.{reference}` — status, timeline, messages for one report, authorized by tracking token header.
- `presence-report.{reference}` — optional staff presence.

**Completion:** Channel authorization tests cover valid, invalid, expired, and cross-report scenarios.

### 6.4 File and storage interfaces

- Private storage path prefix: `evidence/{report_id}/{ulid}.webp`.
- Signed URL TTL: configurable, default 5 minutes.
- Temp path cleared after processing; verified in tests.

### 6.5 Notification interfaces

- Email via Laravel Mail; transport configurable.
- Telegram via bot token and chat ID (optional).
- SMS via gateway (optional).
- Browser push (Phase 6).

### 6.6 External interfaces

- Cloudflare Turnstile verify endpoint.
- Browser geolocation API (client side only).
- Native share sheet (client side only).

---

## 7. Non-functional requirements

### 7.1 Performance

**NFR-PERF-1** (M): First paint on public pages must be server-rendered and complete within 2 seconds on a mid-range mobile device on 3G.

- **Completion:** Measured in a device-throttled test on landing and chat pages.

**NFR-PERF-2** (M): Submission must return a reference code within 3 seconds of the text fields being sent on a stable connection.

- **Completion:** Timed in tests and observed in manual verification.

**NFR-PERF-3** (M): The dashboard queue must render within 1 second for 100 reports and within 2 seconds for 1,000 reports on the pilot VPS.

- **Completion:** Seeded benchmark; documented in Stage 03.

**NFR-PERF-4** (M): Image processing must complete within 30 seconds per image at the configured limits on the pilot VPS.

- **Completion:** Job timing recorded in the job and delivery log.

**NFR-PERF-5** (M): Polling fallback must deliver updates within 20 seconds of the event.

- **Completion:** With Reverb stopped, a status change appears within one polling interval.

### 7.2 Scalability

**NFR-SCALE-1** (M): The system must handle 100 to 500 reports per month without tuning.

- **Completion:** Load-test scenario simulates one month's volume in one day; no errors, no queue backlog beyond the configured threshold.

**NFR-SCALE-2** (S): The system must not require Redis for MVP; Redis must be optional for queue, cache, and rate limiting.

- **Completion:** App runs with `QUEUE_CONNECTION=database` and `CACHE_STORE=database`.

### 7.3 Reliability

**NFR-REL-1** (M): A submission must never be lost due to a dropped connection after the client sends the payload; retries with the same idempotency key must not duplicate.

- **Completion:** Simulated connection drop and retry produces one report.

**NFR-REL-2** (M): No success screen before server commit.

- **Completion:** Verified in FR-SUB-6.

**NFR-REL-3** (M): Queue jobs must retry with backoff and be visible in `failed_jobs` on permanent failure.

- **Completion:** Forced-failure test lands in `failed_jobs` with retry history.

**NFR-REL-4** (M): Realtime is optional; tracking and dashboard must remain functional without WebSockets.

- **Completion:** Verified in FR-MSG-6.

### 7.4 Usability

**NFR-USE-1** (M): A first-time resident must complete a report in under 3 minutes on a mobile device without assistance.

- **Completion:** Usability test with 5 representative users; median completion time ≤ 3 minutes.

**NFR-USE-2** (M): A first-time staff member must be able to triage a report (open, review, set status, assign) in under 2 minutes after a 15-minute orientation.

- **Completion:** Same usability method with staff.

**NFR-USE-3** (M): Error messages must be specific, actionable, and translated.

- **Completion:** Every validation error has a translation key and a clear message.

### 7.5 Maintainability

**NFR-MAIN-1** (M): TypeScript types must be generated from PHP (Spatie Laravel Data + TypeScript transformer) and typed routes generated (Laravel Wayfinder); generation runs in CI.

- **Completion:** A renamed PHP field fails the CI build.

**NFR-MAIN-2** (M): All public and staff routes must have tests; coverage target ≥ 80 % on critical paths (submission, tracking, status changes, audit writes).

- **Completion:** Coverage report in CI; critical paths individually verified.

**NFR-MAIN-3** (M): Every stage must produce a stage report in `docs/stages/`, an entry in `CHANGELOG.md`, and, where a decision was made, an ADR.

- **Completion:** Documented and checked at each stage exit gate.

### 7.6 Portability

**NFR-PORT-1** (S): The system must run on shared hosting with database queue and polling, without Reverb.

- **Completion:** A documented configuration and a test run in that mode.

**NFR-PORT-2** (S): The system must be migratable to Postgres with repository-level changes only.

- **Completion:** Search and spatial behavior isolated behind repositories; documented in ADRs.

### 7.7 Localization

**NFR-LOC-1** (M): Timezone is Asia/Manila; timestamps stored in UTC and rendered in Asia/Manila.

- **Completion:** Verified in a test that crosses a date boundary.

**NFR-LOC-2** (M): Character set for all tables and connections is `utf8mb4`; Tagalog and Akeanon strings round-trip without corruption.

- **Completion:** Round-trip test with sample strings.

---

## 8. Security and privacy requirements

### 8.1 Security

**SEC-1** (M): HTTPS everywhere, including `wss://`; HSTS enabled.

**SEC-2** (M): Server-side authorization on every staff route, evidence file, internal note, and admin function.

**SEC-3** (M): Sessions with idle timeout; two-factor authentication mandatory for administrators, optional for staff.

**SEC-4** (M): High-entropy tracking tokens stored hashed; `hash_equals` for comparison; token never logged.

**SEC-5** (M): File signature validation, size and dimension limits, safe processing, random names, private storage, no executable uploads.

**SEC-6** (M): Strict content security policy; no user content interpreted as markup or code.

**SEC-7** (M): Rate limiting on submission, tracking, messaging, and upload endpoints; stricter limits on failed token lookups.

**SEC-8** (M): Audit table insert-only via database grants; hash chain makes tampering detectable.

**SEC-9** (M): Log redaction of sensitive keys; error trackers configured to drop request data.

**SEC-10** (M): SSH keys only on the host; firewall; fail2ban; scheduled OS, PHP, and MySQL security updates; named person responsible for patching.

**SEC-11** (M): Backups encrypted; restore drill completed before public launch.

**Completion (all SEC):** Each item covered by a test, a configuration check, or a documented procedure with evidence in `docs/runbooks/` and the Stage 05 report.

### 8.2 Privacy

**PRIV-1** (M): Privacy notice shown before submission with purpose, data collected, recipients, retention, and contact for personal data concerns.

**PRIV-2** (M): Data minimization — collect only what intake and follow-up need.

**PRIV-3** (M): Contact number and messages visible only to authorized staff.

**PRIV-4** (M): Public tracking responses contain no contact number, internal notes, responder details, or evidence URLs.

**PRIV-5** (M): Evidence EXIF including GPS stripped on processing.

**PRIV-6** (M): Retention per policy, configurable, and documented for the privacy officer.

**PRIV-7** (M): Access to evidence and to the contact number is audited.

**Completion (all PRIV):** Each item covered by a test or a documented review with the privacy officer; sign-off recorded in Stage 05.

---

## 9. Compliance requirements

**COMP-1** (M): Review against the Data Privacy Act of 2012 and National Privacy Commission guidance, with the municipality's privacy officer or legal adviser.

**COMP-2** (M): Retention of reports, evidence, messages, and logs reviewed and approved by the privacy officer.

**COMP-3** (M): Accessibility review against basic WCAG expectations (keyboard, contrast, labels).

**COMP-4** (M): The app must state clearly on every public screen that it does not replace the emergency hotline.

**Completion:** Each item has a written review artifact, signed off by the responsible party, filed in `docs/`.

---

## 10. Acceptance criteria

Grouped, with completion defined at the group level.

### 10.1 Public reporting

- [ ] A resident can submit a report without registering or logging in. **(FR-CHAT-1…10, FR-SUB-1)**
- [ ] The chat collects type, location, description, name, contact, and on-behalf toggle. **(FR-CHAT-1…6)**
- [ ] A typed location or current-position pin is required; each method works on its own. **(FR-CHAT-2, FR-CHAT-3)**
- [ ] At most one optional photo can be captured through the in-page camera control; no file picker is offered; the image is validated and privately stored. **(FR-CHAT-7, FR-IMG-1…5)**
- [ ] Submission stays on the page, prevents duplicate clicks, keeps a stable idempotency key for retries, and returns the same report plus a usable tracking link. **(FR-SUB-1, FR-CHAT-10)**
- [ ] Unique reference code and secure tracking link produced, with copy and share. **(FR-SUB-2, FR-SUB-3, FR-PUB-3)**
- [ ] English, Tagalog, and Akeanon are selectable and render translated strings. **(FR-I18N-1, FR-I18N-2)**

### 10.2 Tracking and expiration

- [ ] Valid link shows current status and permitted public timeline. **(FR-PUB-4)**
- [ ] Invalid, revoked, and expired tokens return the same response and reveal nothing. **(FR-TRK-3)**
- [ ] Expiry enforced on every request by server time. **(FR-TRK-2, FR-RET-1)**
- [ ] Lost link cannot be recovered from reference code alone. **(FR-TRK-1)**
- [ ] Expiry never deletes the official record. **(FR-RET-3)**
- [ ] Reporter can message staff, receive replies, and live updates fall back to polling. **(FR-MSG-1…7, FR-MSG-6)**

### 10.3 Staff and notifications

- [ ] Staff can search, filter, open, set status and priority, assign, and post public updates. **(FR-STF-1…9)**
- [ ] New report triggers email and in-dashboard alert within one minute; unacknowledged reports escalate. **(FR-NOT-1, FR-NOT-2, FR-NOT-4)**
- [ ] Internal notes, contact numbers, and restricted evidence never exposed publicly. **(PRIV-3, PRIV-4)**
- [ ] Duplicate suggestions appear; staff mark Duplicate, Invalid, or Unable to verify with a reason. **(FR-ABU-3, FR-ABU-4)**

### 10.4 Logging

- [ ] Every audit event is recorded with actor, time, IP, correlation ID. **(FR-LOG-3)**
- [ ] Audit table cannot be edited or deleted via the app. **(FR-LOG-4)**
- [ ] Logs never contain tokens, descriptions, contact numbers, message bodies, or images. **(FR-LOG-5)**
- [ ] Alerts fire for stopped workers, repeated notification failures, Reverb downtime, audit-chain failure, disk threshold. **(FR-LOG-6)**

### 10.5 Security and usability

- [ ] Mobile and desktop work; basic accessibility checks pass. **(FR-ACC-1, COMP-3)**
- [ ] Oversized, malformed, unsupported uploads rejected safely. **(FR-IMG-5)**
- [ ] Turnstile and rate limits protect submission, tracking, messaging, and upload. **(FR-ABU-1, FR-ABU-2)**
- [ ] Every public screen directs life-threatening emergencies to the hotline. **(FR-PUB-1, COMP-4)**

### 10.6 MySQL specifics

- [ ] MySQL 8.0+ running; `ST_Distance_Sphere` verified. **(C-1)**
- [ ] `audit_logs` insert-only enforced; `UPDATE` fails with `ER_TABLEACCESS_DENIED_ERROR`. **(FR-LOG-4)**
- [ ] Hash chain verifies end-to-end; a deliberate row edit alerts. **(FR-LOG-4)**
- [ ] Duplicate detection returns candidates in 200 m / 30 min window. **(FR-ABU-3)**
- [ ] `utf8mb4_unicode_ci` everywhere; Tagalog and Akeanon round-trip. **(NFR-LOC-2)**
- [ ] `STRICT_ALL_TABLES` active. **(C-2)**
- [ ] `SPATIAL INDEX` present and used. **(FR-ABU-3)**

---

## 11. Traceability matrix

Map each requirement to stage, tests, and verification method. Excerpt below; a full matrix lives in `docs/traceability.md` and is updated at each stage.

| Requirement | Stage | Test type | Verification | Artifact |
|---|---|---|---|---|
| FR-PUB-1 | 01 | Feature | Manual + Lighthouse | Stage 01 report |
| FR-CHAT-1…10 | 01 | Feature + Unit | Automated | Stage 01 report |
| FR-SUB-1…6 | 01 | Feature | Automated + manual | Stage 01 report |
| FR-IMG-1…5 | 01 | Feature + Job | Automated | Stage 01 report |
| FR-TRK-1…6 | 02 | Feature | Automated | Stage 02 report |
| FR-STF-1…9 | 03 | Feature | Automated + UAT | Stage 03 report |
| FR-NOT-1…5 | 03 | Feature + Queue | Automated | Stage 03 report |
| FR-MSG-1…7 | 04 | Feature + Broadcast | Automated | Stage 04 report |
| FR-ABU-1…5 | 01, 03, 05 | Feature + Load | Automated + manual | Stage 05 report |
| FR-LOG-1…6 | 00, 01, 03, 05 | Feature + Config | Automated + audit drill | Stage 05 report |
| FR-RET-1…4 | 02, 05 | Feature + Scheduler | Automated | Stage 02, 05 reports |
| FR-I18N-1…3 | 01, 03 | Feature | Automated + review | Stage 01, 03 reports |
| SEC-1…11 | 00, 03, 05 | Config + Feature | Automated + drill | Stage 05 report |
| PRIV-1…7 | 01, 03, 05 | Review + Feature | Manual review | Stage 05 report |
| NFR-* | All | Various | Measured | Stage reports |

**Completion (this section):** `docs/traceability.md` exists and is current at each stage exit.

---

## 12. Requirement completion definitions

To remove ambiguity, "complete" for any requirement means **all** of the following:

1. **Implemented.** Code exists in the main branch.
2. **Tested.** At least one automated test exercises the behavior; critical paths have more.
3. **Verified.** A manual verification step is documented and was performed.
4. **Documented.** A corresponding entry exists in the stage report, and, if a decision was made, an ADR.
5. **Audited.** Where the requirement is an action, an `audit_logs` entry is produced (or a security event, where applicable).
6. **Logged.** Where the requirement is an operational event, it appears in the correct log with a correlation ID.
7. **Reversible.** A documented rollback or off-switch exists where the requirement changes data or state (for example, disabling a notification channel).
8. **Reviewed.** The reviewer signs the stage report; the MDRRMO liaison signs where the requirement is user-facing.

A requirement is **not complete** if any of these is missing. Stage exit gates check this list.

### 12.1 Stage-level completion

Each stage is complete when:

- [ ] All in-scope requirements have an "Implemented / Tested / Verified / Documented / Audited / Logged / Reversible / Reviewed" pass.
- [ ] Stage report in `docs/stages/NN-*.md` is complete.
- [ ] CHANGELOG entry appended.
- [ ] ADRs written for decisions made.
- [ ] CI green on the stage's final commit.
- [ ] Tag `stage-NN-complete` created.
- [ ] Reviewer signed off in the stage report.

### 12.2 MVP completion

The MVP is complete when:

- [ ] Stages 00 through 05 are complete.
- [ ] Every MVP acceptance criterion in §10 is checked.
- [ ] Production URL serves the app over HTTPS, with `wss://` for Reverb or documented polling fallback.
- [ ] Backups are running and a restore drill has succeeded.
- [ ] Privacy officer has signed off on retention and data flows.
- [ ] Staff have been trained and the operating runbook is in `docs/runbooks/`.
- [ ] A one-week pilot with a small staff group has run without a Sev-1 incident.
- [ ] `docs/TIMELINE.md` and `docs/traceability.md` reflect the actual final state.

---

## 13. Appendices

### Appendix A — Glossary of status values

Submitted, Under review, Acknowledged, Assigned, In progress, Resolved, Closed, Duplicate, Invalid, Unable to verify.

### Appendix B — Suggested categories

Vehicular accidents; fire incidents; flooding and water-related incidents; landslides and other natural hazards; medical emergencies; rescue and missing persons; structural collapse and other accidents; other emergencies.

### Appendix C — Alert triggers

Queue workers stopped; repeated notification failures; Reverb down; failed token lookup spike; image processing failures above threshold; audit chain verification failure; disk usage above 80 percent.

### Appendix D — Non-goals

- Replacing the emergency hotline.
- Asserting that a report results in a dispatch.
- Public accounts or profiles for residents.
- Dispatch coordination (out of MVP).
- OCR or content extraction as an operational function (Phase 6, assistive only).
- Analytics beyond operational counts.
- Native mobile applications.

### Appendix E — Open decisions carried from the PRD

Hosting choice; dispatch coordination; contact-number requirement; staff alert channel; retention values; delete vs end access at seven days; hotline numbers and category list; translation review; staff 2FA; original-image preservation; MySQL 8.0+ minimum; Postgres migration path; audit payload column; `mdrrmo_audit` user separation.

Each becomes an ADR when decided and is reflected in `docs/decisions/`.

### Appendix F — Document control

| Version | Date | Author | Notes |
|---|---|---|---|
| 1.0 | 2026-10-12 | @Im Albert | Initial SRS derived from PRD v1.2, with completion definitions |

Changes to this SRS follow the ADR process and are recorded in this table.

---

If you want, next I can produce:

- `docs/traceability.md` as a full matrix (every requirement ID → stage → test file → artifact).
- A one-page **Stage Exit Checklist** PDF/markdown to hand to the reviewer for each stage.
- The **Stage 00 stage report skeleton** pre-filled, so as we build, we just tick the boxes.
