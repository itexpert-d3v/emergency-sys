<footer class="system-footer">
    <div class="system-footer-inner">
        <div class="system-footer-grid">
            <div class="system-footer-brand-block">
                <div class="system-footer-identity">
                    @include('components.brand-marks')
                    <div>
                        <p class="system-footer-kicker">Kalibo MDRRMO · SOU</p>
                        <p class="system-footer-name">Special Operations Unit</p>
                    </div>
                </div>
                <p class="system-footer-description">Emergency reporting and verified public updates for the Municipality of Kalibo.</p>
                <a href="{{ route('public.report') }}" class="system-footer-report">Report an incident <span aria-hidden="true">→</span></a>
            </div>

            <section class="system-footer-hotlines" aria-labelledby="footer-hotline-heading">
                <p class="system-footer-section-label">Emergency contact</p>
                <h2 id="footer-hotline-heading">Kalibo hotlines</h2>
                <ul>
                    <li><span>MDRRMO</span><a href="tel:02628991">(036) 262-8991</a></li>
                    <li><span>Emergency</span><a href="tel:159">159</a></li>
                    <li><span>Mobile</span><a href="tel:+639078951904">0907-895-1904</a></li>
                    <li><span>Mobile</span><a href="tel:+639186312150">0918-631-2150</a></li>
                </ul>
            </section>

            <nav class="system-footer-nav" aria-label="Footer navigation">
                <p class="system-footer-section-label">Portal</p>
                <h2>Explore</h2>
                <a href="{{ route('about') }}">About</a>
                <a href="{{ route('how-it-works') }}">How it works</a>
                <a href="{{ route('news.index') }}">News &amp; advisories</a>
                <a href="{{ route('track') }}">Track a report</a>
                <a href="{{ route('login') }}">Operations sign in</a>
            </nav>
        </div>

        <div class="system-footer-bottom">
            <span>© {{ date('Y') }} Municipality of Kalibo · MDRRMO</span>
            <p>Directory numbers published October 2020 · Confirm current availability</p>
        </div>
    </div>
</footer>
