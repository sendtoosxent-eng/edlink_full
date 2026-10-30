(() => {
    if (window.EdlinkSkeleton) return;
    const root = document.documentElement;
    let safetyTimer;
    const hide = () => {
        clearTimeout(safetyTimer);
        root.classList.remove('edlink-page-loading');
    };
    const show = () => {
        clearTimeout(safetyTimer);
        root.classList.add('edlink-page-loading');
        safetyTimer = setTimeout(hide, 10000);
    };
    window.EdlinkSkeleton = { show, hide };
    window.EdlinkLoader = { show, hide };
    const ready = () => {
        const criticalImages = [...document.querySelectorAll('img[fetchpriority="high"]')];
        const imagesReady = Promise.all(criticalImages.map(img => img.complete ? Promise.resolve() : new Promise(resolve => {
            img.addEventListener('load', resolve, { once: true });
            img.addEventListener('error', resolve, { once: true });
        })));
        // Reveal immediately when critical content is ready; never wait indefinitely on an image.
        Promise.race([imagesReady, new Promise(resolve => setTimeout(resolve, 800))]).then(hide);
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready, { once: true });
    else ready();
    window.addEventListener('load', hide, { once: true });
    window.addEventListener('pageshow', hide);
    window.addEventListener('pagehide', hide);
    // `navigate` fires before the fetch; `navigating` fires only before the DOM swap.
    document.addEventListener('livewire:navigate', event => {
        queueMicrotask(() => { if (!event.defaultPrevented) show(); });
    });
    document.addEventListener('livewire:navigating', show);
    document.addEventListener('livewire:navigated', hide);
    document.addEventListener('click', event => {
        if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
        const link = event.target.closest?.('a[href]');
        if (!link || link.hasAttribute('download') || link.hasAttribute('data-no-loader') || (link.target && link.target !== '_self')) return;
        const next = new URL(link.href, location.href);
        if (next.origin !== location.origin || !/^https?:$/.test(next.protocol)) return;
        if (next.pathname === location.pathname && next.search === location.search) return;
        if (/\.(pdf|exe|apk|zip|csv|xlsx?|docx?|png|jpe?g|webp)$/i.test(next.pathname) || /^\/(downloads|updates|media)\//.test(next.pathname)) return;
        if (link.hasAttribute('wire:navigate') || link.hasAttribute('wire:navigate.hover')) return;
        show();
    });
    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-no-loader') || form.hasAttribute('wire:submit') || form.hasAttribute('wire:submit.prevent') || (form.target && form.target !== '_self')) return;
        queueMicrotask(() => { if (!event.defaultPrevented) show(); });
    });
})();
