# Live map production operations

## Required production settings

- Set `SESSION_DRIVER=redis`, `CACHE_STORE=redis`, `RATE_LIMIT_CACHE_STORE=redis`, and `QUEUE_CONNECTION=redis`.
- Enable Redis AOF persistence and monitor availability, memory, evictions, and latency. Redis is required by authenticated sessions, live-map caching, rate limiting, and queued work.
- Run `php artisan queue:work redis` and `php artisan reverb:start` under Supervisor or systemd. Restart workers and Reverb during deploys.
- When the app runs on more than one server, set `REVERB_SCALING_ENABLED=true` and configure the shared Redis connection in `config/reverb.php`.
- Set `REVERB_ALLOWED_ORIGINS` to the real application hostname.
- Configure `MAP_TILE_KEY` with a MapTiler key restricted to the production origin. Use a separate key for local development and allow `localhost` only on that development key.
- Keep `NAVIGATION_API` on the server. The live map calls the authenticated `/admin/route` proxy.
- Include `public/assets/ambulance.jpg` in the deployment artifact; the background references it at runtime and Vite leaves that path unchanged.
- On each dispatch workstation, add the live-map site to Chrome's **Always keep these sites active** list so Memory Saver does not discard the tab.
- Configure workstation power and screen-lock policies so the machine stays awake during staffed dispatch hours. A sleeping or locked workstation cannot play browser alarms.
- Use the on-page alarm toggle only as a five-minute snooze. Confirm the acceptance card displays **ALARM MUTED** while reports await acceptance and that sound re-arms automatically; do not leave alarm sound muted between shifts.

Example Supervisor programs (adjust the deployment path and service account):

```ini
[program:emergency-sys-queue]
directory=/var/www/emergency-sys
command=php artisan queue:work redis --sleep=1 --tries=3 --timeout=90
user=www-data
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true

[program:emergency-sys-reverb]
directory=/var/www/emergency-sys
command=php artisan reverb:start
user=www-data
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
```

## Staging go-live drill

1. Start Reverb locally first. Confirm the map connects, `/broadcasting/auth` authorizes an allowed staff account, `Echo.connector.pusher.connection.state` is `connected`, a Tinker `LiveMapChanged` dispatch causes an immediate refetch, and a real test report appears. Then repeat the socket, authorization, and report checks on staging.
2. Test autoplay on staging with a fresh browser profile: load the map and do not touch the page, submit a test report from another device, and verify the visual alert. Then interact with the page and confirm the alarm can play; browsers may block audio before the first gesture.
3. With a pending report, mute the alarm and confirm the toggle and acceptance card show the muted state. Confirm sound re-arms after the five-minute snooze expires, including when the tab was in the background.
4. Put the map in a hidden tab and submit a test report. Confirm the marker and alarm arrive promptly through Reverb. Stop Reverb and confirm the badge changes to **Polling** and alerts still arrive through polling.
5. Check Chrome Memory Saver behavior and confirm the dispatch site is in **Always keep these sites active**. Check the workstation's sleep and screen-lock behavior during an idle period; confirm it stays awake during staffed operation.
6. Simulate a Redis outage and record the session, cache, limiter, and queue behavior. Confirm the operational alert reaches the on-call owner.
7. Expire a session and confirm polling stops with the session-expired message.
8. Open the map as two staff accounts in separate browsers and confirm both receive updates without hitting the per-user poll limit.
9. Take one browser offline and restore it. Confirm the badge progresses to **Offline**, then recovers and refetches data.
