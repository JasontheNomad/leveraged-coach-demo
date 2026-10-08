{{-- Renders [data-localtime] / [data-countdown] in each viewer's local timezone. --}}
<script>
    (function () {
        const time = d => d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });

        const formatters = {
            full:  d => d.toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' }) + ' · ' + time(d),
            long:  d => d.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' }) + ' · ' + time(d),
            short: d => d.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ' · ' + time(d),
            time:  d => time(d),
        };

        document.querySelectorAll('[data-localtime]').forEach(el => {
            const d = new Date(el.dataset.localtime);
            if (isNaN(d)) return;
            el.textContent = (formatters[el.dataset.format] || formatters.time)(d);
        });

        document.querySelectorAll('[data-countdown]').forEach(el => {
            const start = new Date(el.dataset.countdown);
            if (isNaN(start)) return;
            const ms = start - new Date();

            if (ms <= 0) {
                el.textContent = 'Live now';
                el.style.background = '#7f1d1d';
                el.style.color = '#fca5a5';
                return;
            }

            const plural = (n, unit) => 'Starts in ' + n + ' ' + unit + (n === 1 ? '' : 's');

            if (ms < 3600000) {                                   // < 1 hour → minutes
                el.textContent = plural(Math.max(1, Math.floor(ms / 60000)), 'minute');
            } else if (ms < 86400000) {                           // 1h–<24h → whole hours
                el.textContent = plural(Math.floor(ms / 3600000), 'hour');
            } else {                                              // >= 24h → ceil to days
                el.textContent = plural(Math.ceil(ms / 86400000), 'day');
            }
        });
    })();
</script>
