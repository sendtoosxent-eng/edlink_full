<style>
    @media (max-width: 1023px) {
        /* Clip sideways overflow without creating a scroll container that traps sticky headers. */
        html, body.mobile-shell-page { overflow-x: clip; }
        :root { --mobile-nav-height: calc(64px + env(safe-area-inset-top, 0px)); }

        body .mobile-shell-bar {
            box-sizing: border-box;
            height: var(--mobile-nav-height);
            padding-top: calc(10px + env(safe-area-inset-top, 0px));
            padding-bottom: 10px;
        }
        body .mobile-shell-bar img {
            width: auto;
            height: 36px;
            max-width: 160px;
            object-fit: contain;
        }
        body .mobile-shell-bar button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            flex-shrink: 0;
        }
        body .mobile-shell-main { padding-top: var(--mobile-nav-height); }
        body .mobile-shell-header { top: var(--mobile-nav-height); }
    }
</style>
