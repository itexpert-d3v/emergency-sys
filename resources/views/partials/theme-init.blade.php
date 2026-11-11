<script>
    (function () {
        try {
            var t = localStorage.getItem('emergency-sys-theme');
            if (t !== 'dark' && t !== 'light') {
                t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.classList.toggle('dark', t === 'dark');
            document.documentElement.style.colorScheme = t;
        } catch (e) {}
    })();
</script>