<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#155f73">
    <meta name="robots" content="noindex">
    <title>{{ $title }} · Kalibo MDRRMO</title>
    <style>
        /*
         * Self-contained: no web fonts, no external CSS or JS, so the page
         * still renders when assets or a CDN are what failed.
         */
        :root {
            color-scheme: light;
            --field: #eaf2f4;
            --ink: #102a34;
            --muted: #465e66;
            --paper: #f8fbfc;
            --rule: #b7cbd0;
            --alert-bg: #fff3d3;
            --alert-bar: #9a570c;
            --sans: "Segoe UI", system-ui, -apple-system, "Helvetica Neue", Arial, sans-serif;
        }

        * { box-sizing: border-box; }
        html { -webkit-text-size-adjust: 100%; }

        body {
            min-width: 320px;
            margin: 0;
            color: var(--ink);
            background: var(--field);
            font-family: var(--sans);
            font-synthesis: none;
            text-rendering: optimizeLegibility;
        }

        a { color: inherit; text-underline-offset: 0.2em; }
        a:focus-visible { outline: 3px solid var(--ink); outline-offset: 4px; border-radius: 2px; }

        .error-shell {
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
        }

        .error-main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 24px;
        }

        /* The one bold element: an incident ticket with a tear-off stub */
        .ticket {
            display: flex;
            width: min(880px, 100%);
            border-radius: 6px;
            background: var(--paper);
            box-shadow: 0 2px 0 rgba(20, 32, 43, 0.25);
        }

        .ticket-body { flex: 1; min-width: 0; padding: 36px 40px 40px; }

        .error-brand { display: inline-flex; align-items: center; gap: 10px; text-decoration: none; }
        .error-brand img { width: 36px; height: 36px; object-fit: contain; }
        .error-brand-name { display: block; font-size: 14px; font-weight: 700; line-height: 1.2; }
        .error-brand-unit { display: block; margin-top: 2px; color: var(--muted); font-size: 12px; }

        h1 {
            max-width: 16ch;
            margin: 36px 0 0;
            font-size: clamp(32px, 5vw, 46px);
            font-weight: 800;
            line-height: 1.08;
            letter-spacing: -0.02em;
            text-wrap: balance;
        }
        .error-message {
            max-width: 52ch;
            margin: 16px 0 0;
            color: var(--muted);
            font-size: 17px;
            line-height: 1.65;
            text-wrap: pretty;
        }

        .error-urgent {
            max-width: 52ch;
            margin: 24px 0 0;
            padding: 12px 16px;
            border-left: 4px solid var(--alert-bar);
            background: var(--alert-bg);
            font-size: 15px;
            font-weight: 600;
            line-height: 1.6;
        }

        .error-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 14px 24px; margin-top: 32px; }
        .error-primary {
            display: inline-flex;
            min-height: 48px;
            align-items: center;
            justify-content: center;
            padding: 0 24px;
            border-radius: 4px;
            color: #fff;
            background: #10384e;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            transition: background-color 120ms ease;
        }
        .error-primary:hover { background: #155f73; }
        .error-secondary { font-size: 15px; font-weight: 600; }

        /* Tear-off stub: holds what someone would quote when reporting the problem */
        .ticket-stub {
            position: relative;
            width: 224px;
            flex: none;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 32px;
            padding: 36px 28px 40px;
            border-left: 2px dashed var(--rule);
        }
        .ticket-stub::before,
        .ticket-stub::after {
            content: "";
            position: absolute;
            left: -13px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--field);
        }
        .ticket-stub::before { top: -12px; }
        .ticket-stub::after { bottom: -12px; }

        .stub-label { margin: 0; color: var(--muted); font-size: 13px; line-height: 1.4; }
        .stub-code {
            margin: 4px 0 0;
            font-size: 72px;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            line-height: 1;
            letter-spacing: -0.03em;
        }
        .stub-time { margin: 4px 0 0; font-size: 14px; font-weight: 600; line-height: 1.45; }

        .error-foot {
            margin: 0;
            padding: 0 24px 28px;
            color: var(--ink);
            font-size: 14px;
            font-weight: 600;
            line-height: 1.5;
            text-align: center;
        }

        /* One entrance, once */
        @media (prefers-reduced-motion: no-preference) {
            .ticket { animation: drop 520ms cubic-bezier(0.16, 1, 0.3, 1) both; }
            @keyframes drop {
                from { opacity: 0; transform: translateY(-14px); }
                to { opacity: 1; transform: translateY(0); }
            }
        }

        @media (max-width: 720px) {
            .error-main { align-items: flex-start; padding: 24px 16px; }
            .ticket { flex-direction: column; }
            .ticket-body { padding: 28px 24px 32px; }
            h1 { margin-top: 28px; }
            .error-message { font-size: 16px; }
            .error-primary { width: 100%; }

            /* Stub moves to the top and tears off along a horizontal line */
            .ticket-stub {
                order: -1;
                width: auto;
                flex-direction: row;
                align-items: flex-end;
                justify-content: space-between;
                gap: 16px;
                padding: 24px 24px 22px;
                border-left: 0;
                border-bottom: 2px dashed var(--rule);
            }
            .ticket-stub::before { top: auto; bottom: -13px; left: -12px; }
            .ticket-stub::after { top: auto; bottom: -13px; left: auto; right: -12px; }
            .stub-code { font-size: 56px; }
            .stub-time { text-align: right; }
        }
    </style>
</head>
<body>
    <div class="error-shell">
        <main class="error-main">
            <article class="ticket" aria-labelledby="error-title">
                <section class="ticket-body">
                    <a class="error-brand" href="{{ url('/') }}" aria-label="Kalibo MDRRMO home">
                        <img src="{{ asset('assets/sou.png') }}" alt="">
                        <span>
                            <span class="error-brand-name">Kalibo MDRRMO</span>
                            <span class="error-brand-unit">Special Operations Unit</span>
                        </span>
                    </a>

                    <h1 id="error-title">{{ $title }}</h1>
                    <p class="error-message">{{ $message }}</p>

                    @if (! empty($urgentMessage))
                        <p class="error-urgent" role="note">{{ $urgentMessage }}</p>
                    @endif

                    <nav class="error-actions" aria-label="Error page actions">
                        <a class="error-primary" href="{{ url($primaryHref) }}">{{ $primaryLabel }}</a>
                        @if (! empty($secondaryHref) && ! empty($secondaryLabel))
                            <a class="error-secondary" href="{{ url($secondaryHref) }}">{{ $secondaryLabel }}</a>
                        @endif
                    </nav>
                </section>

                <aside class="ticket-stub">
                    <div>
                        <p class="stub-label">Error code</p>
                        <p class="stub-code">{{ $statusCode }}</p>
                    </div>
                    <div>
                        <p class="stub-label">Recorded</p>
                        <p class="stub-time">{{ now()->format('j M Y') }}<br>{{ now()->format('g:i A') }}</p>
                    </div>
                </aside>
            </article>
        </main>

        <p class="error-foot">You can return to the portal or try the action again.</p>
    </div>
</body>
</html>