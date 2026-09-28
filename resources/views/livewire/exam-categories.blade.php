<div class="space-y-6">
    <header class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-xs">
        <div class="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="mt-3 text-2xl font-black sm:text-3xl text-amber-300 tracking-tight">Examination Categories</h1>
                 <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-500">
                       Set this term's assessment periods and how much each contributes to the final subject mark. Exams in the same category are averaged together.</p>
            </div>
            <a href="{{ route('settings.index') }}" wire:navigate class="text-sm font-semibold text-amber-300 hover:text-amber-300">Back to System Settings</a>
        </div>
    </header>

    @if (session('status'))
        <div class="border-l-4 border-emerald-600 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900">{{ session('status') }}</div>
    @endif

    <form wire:submit="save" class="max-w-4xl space-y-5">
        @if($terms->isNotEmpty())
            <label class="block max-w-sm text-xs font-bold text-slate-700">Academic term
                <select wire:model.live="termId" class="mt-1.5 w-full rounded-md border-slate-300 py-2 text-sm font-semibold">
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}">{{ $term->name }} {{ $term->year }}{{ $term->is_current ? ' · Current' : '' }}</option>
                    @endforeach
                </select>
            </label>
        @else
            <p class="border-l-4 border-amber-500 bg-amber-50 px-4 py-3 text-sm text-amber-900">Create an academic term before configuring its examination categories.</p>
        @endif
        <section class="divide-y divide-slate-200 border-y border-slate-200">
            <div class="grid grid-cols-[1fr_9rem_2.5rem] gap-3 py-3 text-xs font-bold uppercase text-slate-500">
                <span>Category name</span><span>Final mark share</span><span></span>
            </div>
            @forelse ($categories as $index => $category)
                <div wire:key="exam-category-{{ $category['id'] ?? 'new-'.$index }}" class="grid grid-cols-[1fr_9rem_2.5rem] items-start gap-3 py-3">
                    <div>
                        <input wire:model="categories.{{ $index }}.name" type="text" maxlength="100" placeholder="e.g. Beginning of term" class="w-full rounded-md border-slate-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-amber-500">
                        @error("categories.$index.name") <span class="mt-1 block text-xs text-rose-700">{{ $message }}</span> @enderror
                    </div>
                    <div class="relative">
                        <input wire:model="categories.{{ $index }}.weight" type="number" min="0.01" max="100" step="0.01" class="w-full rounded-md border-slate-300 px-3 py-2 pr-8 text-sm focus:border-amber-500 focus:ring-amber-500">
                        <span class="pointer-events-none absolute right-3 top-2 text-sm text-slate-400">%</span>
                        @error("categories.$index.weight") <span class="mt-1 block text-xs text-rose-700">{{ $message }}</span> @enderror
                    </div>
                    <button type="button" wire:click="removeCategory({{ $index }})" aria-label="Remove {{ $category['name'] ?: 'category' }}" title="Remove category" class="flex h-10 w-10 items-center justify-center rounded-md text-slate-500 hover:bg-rose-50 hover:text-rose-700">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M5 7h14M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3"/></svg>
                    </button>
                </div>
            @empty
                <p class="py-6 text-sm text-slate-600">No examination categories are configured. Exams will continue using the school's existing result calculation until categories are added.</p>
            @endforelse
        </section>

        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <button type="button" wire:click="addCategory" @disabled(count($categories) >= 12 || $termId === '') class="bg-slate-900 rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-amber-300 disabled:opacity-50">Add category</button>
                <p class="mt-2 text-xs text-slate-500">Weights must total 100%. Clear all categories to return to the existing report calculation.</p>
                @error('categories') <p class="mt-1 text-sm font-semibold text-rose-700">{{ $message }}</p> @enderror
            </div>
            <button type="submit" wire:loading.attr="disabled" @disabled($termId === '') class="rounded-md bg-slate-900 px-4 py-2.5 text-sm font-bold text-amber-300 hover:bg-slate-700 disabled:opacity-50">Save categories</button>
        </div>
    </form>
</div>
