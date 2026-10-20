<style>
    /* Keep navigation reachable on small screens without a visible scrollbar. */
    #app-sidebar { scrollbar-width: none; -ms-overflow-style: none; }
    #app-sidebar::-webkit-scrollbar { display: none; width: 0; }
    #app-sidebar .nav-submenu {
        margin: .35rem .75rem .5rem 1.35rem;
        padding: .15rem 0 .15rem .85rem;
        border-left: 1px solid rgba(255,255,255,.16);
        max-height: none;
        overflow: visible;
    }
    #app-sidebar .nav-submenu .nav-sub-link {
        position: relative;
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .5rem .6rem;
        border-radius: .5rem;
    }
    #app-sidebar .nav-submenu .nav-sub-link::before {
        content: '';
        position: absolute;
        left: -.85rem;
        top: 50%;
        width: .65rem;
        height: 1px;
        transform: translateY(-50%);
        border-radius: 0;
        background: rgba(255,255,255,.16);
    }
    #app-sidebar .nav-submenu .nav-sub-link:hover,
    #app-sidebar .nav-submenu .nav-sub-link.active { background: rgba(234,179,8,.08); color: #facc15; }
    #app-sidebar .nav-submenu .nav-sub-link.active::before { background: #facc15; }
</style>
