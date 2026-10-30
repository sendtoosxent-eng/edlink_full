(() => {
    const toggle = document.querySelector('.nav-toggle');
    const navigation = document.querySelector('#site-navigation');
    function closeNavigation() {
        navigation.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Open navigation');
    }
    toggle?.addEventListener('click', () => {
        const open = toggle.getAttribute('aria-expanded') !== 'true';
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
        navigation.classList.toggle('is-open', open);
    });
    navigation?.querySelectorAll('a').forEach(link => link.addEventListener('click', closeNavigation));
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && toggle?.getAttribute('aria-expanded') === 'true') {
            closeNavigation();
            toggle.focus();
        }
    });
    const themeToggle = document.querySelector('[data-theme-toggle]');
    function labelTheme() {
        themeToggle?.setAttribute('aria-label', document.documentElement.classList.contains('dark') ? 'Switch to light mode' : 'Switch to dark mode');
    }
    labelTheme();
    themeToggle?.addEventListener('click', () => {
        const dark = document.documentElement.classList.toggle('dark');
        try { localStorage.setItem('edlink_theme', dark ? 'dark' : 'light'); } catch {}
        labelTheme();
    });

    // Product figures count up once on entry; reduced-motion users see static values.
    const statSections = document.querySelectorAll('.testimonial-stats, [data-animated-stats]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    statSections.forEach(stats => {
    if ('IntersectionObserver' in window && !reducedMotion.matches) {
        stats.classList.add('stats-ready');
        const observer = new IntersectionObserver(entries => {
            if (!entries.some(entry => entry.isIntersecting)) return;
            observer.disconnect();
            stats.classList.add('stats-entered');
            stats.querySelectorAll('[data-count-to]').forEach(counter => {
                const target = Number(counter.dataset.countTo);
                if (!Number.isSafeInteger(target) || target < 0) return;
                const started = performance.now();
                function tick(now) {
                    if (reducedMotion.matches) { counter.textContent = target.toLocaleString(); return; }
                    const progress = Math.min(1, (now - started) / 1250);
                    counter.textContent = Math.round(target * (1 - Math.pow(1 - progress, 3))).toLocaleString();
                    if (progress < 1) requestAnimationFrame(tick);
                }
                requestAnimationFrame(tick);
            });
        }, { threshold: 0.35 });
        observer.observe(stats);
        reducedMotion.addEventListener('change', event => {
            if (event.matches) { observer.disconnect(); stats.classList.remove('stats-ready'); }
        });
    }
    });
})();
