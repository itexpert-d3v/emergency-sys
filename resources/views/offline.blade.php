<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#155f73">
    <meta name="robots" content="noindex,nofollow">
    <title>Connection unavailable · Kalibo MDRRMO</title>
    <style>
        :root {
            color-scheme: light;
            --field: #eaf2f4;
            --ink: #102a34;
            --muted: #465e66;
            --paper: #f8fbfc;
            --rule: #b7cbd0;
            --action: #ffd074;
            --action-ink: #102a34;
            --sans: "Public Sans", "Segoe UI", system-ui, sans-serif;
        }
        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }
        body {
            min-width: 320px;
            min-height: 100vh;
            min-height: 100dvh;
            margin: 0;
            color: var(--ink);
            background: var(--field);
            font-family: var(--sans);
            font-synthesis: none;
            text-rendering: optimizeLegibility;
        }
        a { color: inherit; text-underline-offset: 0.2em; }
        a:focus-visible { outline: 3px solid #0b6676; outline-offset: 4px; }
        .offline-shell { display: flex; min-height: 100vh; min-height: 100dvh; flex-direction: column; }
        .offline-main { display: flex; flex: 1; align-items: center; justify-content: center; padding: 40px 24px; }
        .offline-ticket {
            display: flex;
            width: min(880px, 100%);
            border: 1px solid var(--rule);
            border-radius: 6px;
            background: var(--paper);
            box-shadow: 0 8px 24px rgba(16, 42, 52, 0.09);
        }
        .offline-body { flex: 1; min-width: 0; padding: 36px 40px 40px; }
        .offline-brand { display: inline-flex; align-items: center; gap: 10px; text-decoration: none; }
        .offline-brand img { width: 40px; height: 40px; object-fit: contain; }
        .offline-brand strong, .offline-brand span { display: block; }
        .offline-brand strong { font-size: 14px; }
        .offline-brand span { margin-top: 2px; color: var(--muted); font-size: 12px; }
        h1 { max-width: 16ch; margin: 36px 0 0; font-size: 42px; font-weight: 800; line-height: 1.08; }
        .offline-message { max-width: 52ch; margin: 16px 0 0; color: var(--muted); font-size: 17px; line-height: 1.65; }
        .offline-urgent { max-width: 52ch; margin: 24px 0 0; padding: 12px 16px; border: 1px solid #9a570c; background: #fff3d3; font-size: 15px; font-weight: 700; line-height: 1.6; }
        .offline-actions { display: flex; flex-wrap: wrap; gap: 14px 24px; margin-top: 32px; }
        .offline-primary { display: inline-flex; min-height: 48px; align-items: center; justify-content: center; padding: 0 24px; border-radius: 4px; background: #10384e; color: #fff; font-size: 15px; font-weight: 700; text-decoration: none; }
        .offline-secondary { display: inline-flex; min-height: 48px; align-items: center; font-size: 15px; font-weight: 700; }
        .offline-foot { margin: 0; padding: 0 24px 28px; color: var(--ink); font-size: 14px; font-weight: 700; line-height: 1.5; text-align: center; }
        @media (max-width: 720px) {
            .offline-main { align-items: flex-start; padding: 24px 16px; }
            .offline-ticket { flex-direction: column; }
            .offline-body { padding: 28px 24px 32px; }
            h1 { margin-top: 28px; font-size: 34px; }
            .offline-primary { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="offline-shell">
        <main class="offline-main">
            <article class="offline-ticket" aria-labelledby="offline-title">
                <section class="offline-body">
                    <a class="offline-brand" href="/" aria-label="Kalibo MDRRMO home">
                        <img src="{{ asset('assets/sou.png') }}" alt="">
                        <span><strong>Kalibo MDRRMO</strong><span>Special Operations Unit</span></span>
                    </a>
                    <h1 id="offline-title">You are offline</h1>
                    <p class="offline-message">This page could not be reached because your device has no internet connection. Any information shown here may be out of date.</p>
                    <p class="offline-urgent" role="note">This website does not dispatch responders.</p>
                    <nav class="offline-actions" aria-label="Offline page actions">
                        <a class="offline-primary" href="/">Try again</a>
                        <a class="offline-secondary" href="/report">Open report form</a>
                    </nav>
                </section>
            </article>
        </main>
        <p class="offline-foot">Do not rely on this page for live incident updates.</p>
    </div>
</body>
</html>
