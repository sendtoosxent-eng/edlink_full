@props([
    'actions' => [],
    'badge' => 'Workspace tools',
    'description' => 'Jump directly to the tasks you handle most often.',
])

@if(count($actions))
    <section aria-labelledby="quick-actions-heading" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
        <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-600">One-click shortcuts</p>
                <h2 id="quick-actions-heading" class="mt-1 text-xl font-bold text-slate-900">Quick actions</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
            </div>
            <span class="hidden rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500 sm:inline-flex">{{ $badge }}</span>
        </div>

        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
            @foreach($actions as $action)
                <a href="{{ $action['url'] }}"
                   @if($action['navigate'] ?? true) wire:navigate @endif
                   data-quick-action="{{ Str::slug($action['label']) }}"
                   class="group relative min-h-40 overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 transition-all duration-200 {{ $action['hoverClass'] ?? 'hover:border-amber-300' }} hover:-translate-y-1 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-amber-400 focus:ring-offset-2 sm:p-5">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl {{ $action['iconClass'] ?? 'bg-amber-100 text-amber-700' }} transition-transform duration-200 group-hover:scale-110">
                        <x-dynamic-component :component="$action['icon']" class="h-6 w-6" stroke-width="1.8" />
                    </span>
                    <span class="mt-5 block text-sm font-bold leading-5 text-slate-900">{{ $action['label'] }}</span>
                    <span class="mt-1 block text-xs leading-5 text-slate-500">{{ $action['description'] }}</span>
                    <span class="absolute right-4 top-4 text-slate-300 transition-all duration-200 group-hover:translate-x-1 group-hover:text-amber-600" aria-hidden="true">→</span>
                </a>
            @endforeach
        </div>
    </section>
@endif
