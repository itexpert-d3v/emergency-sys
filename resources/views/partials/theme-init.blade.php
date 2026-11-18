<script>
    (function () {
        try {
            var t = localStorage.getItem('emergency-sys-theme');
            var dark = t === 'dark' || ((t !== 'light' && t !== 'dark') && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
            document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
        } catch (e) {}
    })();
</script>