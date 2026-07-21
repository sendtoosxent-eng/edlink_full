{{--
    Global Edlink loader.

    Include this ONCE, right after <body> opens, in every layout:
      - layouts/guest.blade.php
      - layouts/guest-split.blade.php
      - layouts/guest-blank.blade.php
      - dashboard.blade.php
      - any future layout

    It automatically shows/hides itself for:
      1. Every full page load or browser refresh (visible immediately on paint,
         hidden once the page finishes loading)
      2. Every wire:navigate transition (Livewire SPA-style page swap)
      3. Every Livewire action anywhere on the page (form submits, wire:click,
         etc.) — no need to add wire:loading to individual buttons

    Requires Livewire's scripts to be present on the page (@livewireScripts)
    for #2 and #3 to work; #1 works even without Livewire.
--}}
<x-edlink-loader overlay id="global-loader" />

<script>
(function () {
    const loader = document.getElementById('global-loader');
    if (!loader) return;

    const show = () => loader.classList.remove('hidden');
    const hide = () => loader.classList.add('hidden');

    // 1. Full page load / refresh — loader is visible by default in the HTML,
    //    hide it once everything has finished loading.
    window.addEventListener('load', hide);

    // 2. Livewire SPA-style navigation (wire:navigate links).
    document.addEventListener('livewire:navigate', show);
    document.addEventListener('livewire:navigated', hide);

    // 3. Every Livewire component request anywhere on the page.
    document.addEventListener('livewire:init', () => {
        Livewire.hook('request', ({ fail, succeed }) => {
            show();
            succeed(() => hide());
            fail(() => hide());
        });
    });
})();
</script>