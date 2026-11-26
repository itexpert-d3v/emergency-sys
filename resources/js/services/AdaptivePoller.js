const DEFAULTS = {
    visibleSteps: [3000, 5000, 10000, 20000],
    hiddenSteps: [15000, 30000, 60000],
    realtimeInterval: { visible: 60000, hidden: 60000 },
    urgentInterval: { visible: 5000, hidden: 15000 },
    jitter: 0.1,
    onError: () => {},
    onModeChange: () => {},
    onPoll: () => {},
};

export function conditionalJson(url, options = {}) {
    let etag = null;
    return async () => {
        const headers = new Headers(options.headers || {});
        if (etag) headers.set('If-None-Match', etag);
        const response = await fetch(url, { ...options, headers, cache: 'no-store' });
        if (response.status === 304) return null;
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        etag = response.headers.get('ETag') || etag;
        return response.json();
    };
}

export class AdaptivePoller {
    #fetch; #render; #opts; #timer = null; #idle = 0; #running = false; #realtime = false; #urgent = false; #errors = 0;
    constructor(fetchData, render, options = {}) {
        this.#fetch = fetchData;
        this.#render = render;
        this.#opts = { ...DEFAULTS, ...options, urgentInterval: { ...DEFAULTS.urgentInterval, ...options.urgentInterval }, realtimeInterval: { ...DEFAULTS.realtimeInterval, ...options.realtimeInterval } };
        this.#visibility = () => this.#schedule();
    }
    #visibility;
    start() { if (this.#running) return; this.#running = true; document.addEventListener('visibilitychange', this.#visibility); this.#tick(); }
    stop() { this.#running = false; clearTimeout(this.#timer); document.removeEventListener('visibilitychange', this.#visibility); }
    setUrgent(urgent) { if (urgent === this.#urgent) return; this.#urgent = urgent; this.#schedule(); }
    async refresh() { this.#idle = 0; await this.#tick(); }
    #delay() {
        const o = this.#opts;
        let base;
        if (this.#realtime) base = document.hidden ? o.realtimeInterval.hidden : o.realtimeInterval.visible;
        else { const steps = document.hidden ? o.hiddenSteps : o.visibleSteps; base = steps[Math.min(this.#urgent ? 0 : this.#idle, steps.length - 1)]; }
        if (this.#urgent) base = Math.min(base, document.hidden ? o.urgentInterval.hidden : o.urgentInterval.visible);
        return base * (1 + (Math.random() * 2 - 1) * o.jitter);
    }
    #schedule() { if (!this.#running) return; clearTimeout(this.#timer); this.#timer = setTimeout(() => this.#tick(), this.#delay()); }
    async #tick() {
        if (!this.#running && this.#timer !== null) return;
        try {
            const data = await this.#fetch();
            if (this.#errors >= 3) this.#opts.onModeChange(this.#realtime ? 'live' : 'polling');
            this.#errors = 0;
            this.#opts.onPoll();
            if (data !== null) { this.#idle = 0; this.#render(data); } else this.#idle++;
        }
        catch (error) {
            this.#idle++;
            this.#errors++;
            if (this.#errors === 3) this.#opts.onModeChange('offline');
            this.#opts.onError(error);
        }
        finally { if (this.#running) this.#schedule(); }
    }
    realtime(connected) {
        if (connected === this.#realtime) return;
        this.#realtime = connected;
        if (this.#errors < 3) this.#opts.onModeChange(connected ? 'live' : 'polling');
        this.#schedule();
    }
}

export function bindEcho(poller, echo, channelName, eventName) {
    const channel = echo.private(channelName);
    let refreshTimer = null;
    channel.listen(eventName, () => {
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(() => poller.refresh(), 250);
    });
    const connection = echo.connector?.pusher?.connection;
    const updateState = (state) => poller.realtime(state?.current === 'connected' || connection?.state === 'connected');
    connection?.bind('state_change', updateState);
    updateState();
    return () => {
        clearTimeout(refreshTimer);
        connection?.unbind('state_change', updateState);
        echo.leave(channelName);
        poller.realtime(false);
    };
}
