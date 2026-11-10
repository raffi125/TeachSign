<script>
    (function () {
        var key = 'theme';
        var root = document.documentElement;
        var stored = null;
        try { stored = localStorage.getItem(key); } catch (e) {}
        var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        var theme = stored === 'dark' || stored === 'light' ? stored : (prefersDark ? 'dark' : 'light');
        root.setAttribute('data-theme', theme);

        function syncLabels() {
            var dark = root.getAttribute('data-theme') === 'dark';
            document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
                btn.setAttribute('aria-label', dark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap');
            });
        }

        document.addEventListener('click', function (event) {
            var btn = event.target.closest('[data-theme-toggle]');
            if (!btn) return;
            var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            try { localStorage.setItem(key, next); } catch (e) {}
            syncLabels();
        });

        document.addEventListener('DOMContentLoaded', syncLabels);
    })();
</script>
