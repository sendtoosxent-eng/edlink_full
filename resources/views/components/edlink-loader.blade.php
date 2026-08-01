@props([
    'size' => 18,
    'fullscreen' => false,
    'title' => 'Edlink',
    'subtitle' => 'Loading...',
])

@if($fullscreen)
<div id="global-loader" aria-live="polite" aria-label="Edlink is loading"
     class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/70 backdrop-blur-md transition-opacity duration-200">
    <div class="flex flex-col items-center rounded-3xl border border-white/10 bg-slate-900/95 px-10 py-8 text-center shadow-2xl">
        <div class="relative flex h-24 w-24 items-center justify-center">
            <span class="absolute inset-0 animate-spin rounded-full border-[3px] border-white/10 border-t-yellow-400 border-r-yellow-400"></span>
            <span class="absolute inset-2 animate-pulse rounded-full bg-yellow-400/10"></span>
            <img src="{{ asset('img/webfav.png') }}" alt="Edlink" class="relative h-16 w-16 object-contain drop-shadow-xl">
        </div>
        <p class="mt-5 text-xs font-black uppercase tracking-[.24em] text-white">{{ $title }}</p>
        <p class="mt-1 text-[11px] font-medium text-slate-300">{{ $subtitle }}</p>
        <div class="mt-4 flex gap-1.5"><span class="h-1.5 w-1.5 animate-bounce rounded-full bg-yellow-400"></span><span class="h-1.5 w-1.5 animate-bounce rounded-full bg-yellow-400" style="animation-delay:120ms"></span><span class="h-1.5 w-1.5 animate-bounce rounded-full bg-yellow-400" style="animation-delay:240ms"></span></div>
    </div>
</div>

@once
<script>
(() => {
    const findLoader = () => document.getElementById('global-loader');
    let safetyTimer;
    const showLoader = () => {
        const loader = findLoader(); if (!loader) return;
        clearTimeout(safetyTimer);
        loader.classList.remove('opacity-0', 'pointer-events-none');
        loader.classList.add('opacity-100', 'pointer-events-auto');
        safetyTimer = setTimeout(hideLoader, 15000);
    };
    const hideLoader = () => {
        const loader = findLoader(); if (!loader) return;
        clearTimeout(safetyTimer);
        loader.classList.remove('opacity-100', 'pointer-events-auto');
        loader.classList.add('opacity-0', 'pointer-events-none');
    };
    window.EdlinkLoader = { show: showLoader, hide: hideLoader };
    window.addEventListener('load', hideLoader);
    window.addEventListener('pageshow', hideLoader);
    document.addEventListener('DOMContentLoaded', () => requestAnimationFrame(hideLoader));
    document.addEventListener('livewire:navigate', showLoader);
    document.addEventListener('livewire:navigated', hideLoader);
    document.addEventListener('livewire:init', () => {
        Livewire.hook('request', ({ respond, fail }) => {
            showLoader(); respond(hideLoader); fail(hideLoader);
        });
    });
    document.addEventListener('submit', event => {
        if (!event.defaultPrevented && event.target.matches('form:not([data-no-loader])')) showLoader();
    });
})();
</script>
@endonce
@else
<span {{ $attributes->class('relative inline-flex shrink-0 items-center justify-center align-middle') }} style="width:{{ (int)$size }}px;height:{{ (int)$size }}px" role="status" aria-label="Loading">
    <span class="absolute inset-0 animate-spin rounded-full border-2 border-current border-t-transparent opacity-80"></span>
    <img src="{{ asset('img/webfav.png') }}" alt="" class="relative object-contain" style="width:{{ max(8,(int)$size-7) }}px;height:{{ max(8,(int)$size-7) }}px">
</span>
@endif