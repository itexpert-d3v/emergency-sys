# PWA and Layout Handoff

Updated: 2026-11-23

## Current Request

The active request is to make `emergency-sys` an installable, production-grade PWA while preserving existing behavior and protecting sensitive incident data. The user explicitly requires an inspect-first plan and approval before any PWA edits. No PWA implementation changes have been made.

## PWA Inspection Findings

- Vite uses `laravel-vite-plugin`, Tailwind v4, and Vue. Existing entries are `resources/css/app.css`, `resources/js/app.js`, and `resources/js/landing-motion.js`; all must remain.
- `package.json` has no PWA plugin. `vite-plugin-pwa` is not currently installed.
- Current templates have been partially refactored to `resources/views/layouts/base.blade.php`, `public.blade.php`, `admin.blade.php`, and `auth.blade.php`. The base exposes `@stack('head')`, `@includeIf('partials.pwa-meta')`, and Vite entries. Public pages use the public shell; admin and login use noindex shells; tracking pages use public shell with noindex.
- Routes include public `/`, `/report` GET/POST, `/news`, tracking `/track` and `/track/{token}`, reporter messages under `/track/{token}/messages`, authenticated `/queue`, `/dashboard`, `/live-map`, and private report APIs under `/api`.
- Report POST is CSRF/session protected, throttled, checks banned IP/phone, and runs Turnstile middleware. The current report JS submits asynchronously and requires JSON `tracking_url` to declare success. Report endpoint uses an idempotency key and may attach a photo.
- Turnstile token is required when the secret key is configured; tokens are one-time/short-lived. An offline queued submission cannot bypass this check. On reconnection, it must foreground the page and request fresh CSRF/session and Turnstile state before retrying. Do not silently report success.
- Tracking responses already use `Cache-Control: private, no-store`, `Referrer-Policy: no-referrer`, and `X-Robots-Tag: noindex, nofollow`. There is no general authenticated response no-store policy in the current inspected app. Session config defaults to database sessions, `HttpOnly`, `SameSite=Lax`; HTTPS-only cookie is environment-configured.
- No application CSP or service-worker-scope headers were found in Laravel config/bootstrap/app/routes.
- `public/assets/sou.png` is the proposed icon source. Initial tool lookup did not find `magick` or `cwebp`; PHP CLI and a Python launcher exist, but PHP GD/Imagick capability check did not return a result before handoff. Verify icon-generation tooling before creating icons.
- Live map and report maps use external Leaflet/OpenStreetMap tiles. Do not precache tiles. The safest minimal plan is no tile runtime cache.

## Recommended Implementation Plan (Not Yet Approved)

1. Add a manifest, generated 192/512/maskable icons and Apple touch icon; add a shared metadata partial under `resources/views/partials/` and include it through the base layout. Set the title/short name/description, root start URL and scope, standalone display, and Sea-Glass colors.
2. Test `vite-plugin-pwa` with the Laravel Vite plugin using a minimal branch/config experiment. Prefer `injectManifest` only if the generated worker can be served with root scope. If not, add a hand-written worker at `public/sw.js` and keep it outside Vite output so it naturally has `/` scope.
3. Restrict precache to build manifest CSS/JS, local fonts, icons, and the offline document. Runtime handling must never cache navigations, `/admin*`, `/track*`, `/queue*`, `/dashboard*`, `/live-map*`, `/profile*`, any API/report/message response, or any non-GET request. Use stale-while-revalidate only for same-origin versioned static assets/images; no OSM tiles.
4. Add a branded offline Blade view and include its built static equivalent only if it can be served without private data.
5. Extend `resources/js/report-form.js` with IndexedDB draft persistence, short TTL, no photos/tokens/CSRF stored, visible `Saved on this device, not yet sent` state, manual send flow and supported Background Sync signaling. Treat Background Sync as a wake-up only: when session/Turnstile verification is needed, require foreground interaction and preserve the draft. Remove draft only on confirmed server response.
6. Add a small install/update prompt using `beforeinstallprompt`, dismissed-state persistence, iOS Add to Home Screen instructions, admin suppression, and a waiting-worker refresh prompt. Do not activate an update automatically while a form is in progress.
7. Add `Cache-Control: private, no-store` to authenticated route responses via narrowly scoped middleware or equivalent response policy; preserve tracking headers. Ensure logout does not leave user-specific application caches. The worker should not cache any authenticated page in the first place.
8. Add tests for manifest delivery, worker response/scope/cache headers, private page no-store headers, and noindex/layout behavior. Run build and focused tests, then full tests where practical.

## Risks and Deployment Notes

- PWA installation requires HTTPS in production; localhost is accepted for development.
- Background Sync cannot safely replay an expired CSRF session or Turnstile proof. The user-facing flow must say the report is pending and only redirect/show receipt after the server confirms the submission.
- IndexedDB draft storage necessarily contains report details temporarily. Exclude photos and security tokens, set a short expiration, provide clear pending state, and clear after confirmed send.
- Root service worker must be served from `/sw.js` with JavaScript MIME type and no long-lived cache. If emitted outside root, configure `Service-Worker-Allowed: /` explicitly.
- Ensure production CSP permits same-origin manifest/worker and the existing external Turnstile, Leaflet, OSM, and map-service origins used by current pages. No CSP currently exists in inspected app configuration.
- A tile cache was not recommended because tile storage grows quickly and user map activity reveals location; leave map tiles network-only.

## Related UI Refactor Progress

The previous approved layout refactor is partially implemented in the same worktree. Current created/edited files include the four shared layouts, semantic CSS tokens, anonymous UI components, migrated public/news/report/tracking views, a migrated login, self-contained offline/error views, and feature tests. The admin shared CSS was nested under the new admin layout. `php artisan view:cache` passed. Focused tests `LayoutRenderingTest`, `TrackingTest`, `UnifiedAuthenticationTest`, and `StaffDashboardTest` passed (24 tests, 197 assertions). The environment reports `npm run build` exited successfully, but verify again after any further CSS changes.

There are pre-existing user changes in routes, controllers, security middleware/models, admin settings/map/login, CSS, docs, and tests. Do not revert or overwrite these. Current `git status --short` includes uncommitted changes across those paths and the refactor files.

## Resume Point

1. Resume the active PWA request, not the earlier layout refactor.
2. Reconfirm current `git status` and inspect the now-migrated layout file heads if needed.
3. The PWA request still needs an approval-gated plan response. This document contains the inspection results and recommended plan; do not edit PWA code until the user approves that plan.
