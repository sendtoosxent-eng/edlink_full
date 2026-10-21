<div class="space-y-6 font-poppins text-slate-800">
    <!-- Header Section -->
    <header class="relative overflow-hidden rounded-2xl bg-slate-900 p-6 text-white shadow-lg sm:p-8">
        <div class="relative z-10 flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1.5">
                <div class="inline-flex items-center gap-2 rounded-full bg-slate-800/80 px-3 py-1 text-xs font-semibold text-amber-300 backdrop-blur-sm">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    Assessment Engine
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-amber-300">Examination Category Groups</h1>
                <p class="text-sm text-slate-400 mt-2 leading-relaxed">Create assessment rules such as Lower Primary, define weighted grading categories, and assign them directly to target classes.</p>
            </div>
            <a href="{{ route('settings.index') }}" wire:navigate class="inline-flex items-center gap-2 self-start rounded-xl bg-slate-800/90 px-4 py-2.5 text-xs font-bold text-amber-300 transition-all hover:bg-slate-800 hover:text-amber-200 sm:self-center">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Settings
            </a>
        </div>
    </header>

    <!-- Session & Error Flash Banners -->
    @if(session('status'))
        <div class="flex items-center gap-3 rounded-xl border-l-4 border-emerald-600 bg-emerald-50/90 p-4 text-xs font-medium text-emerald-900 shadow-sm backdrop-blur-sm sm:text-sm">
            <svg class="h-5 w-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if($errors->has('groups'))
        <div class="flex items-center gap-3 rounded-xl border-l-4 border-rose-600 bg-rose-50/90 p-4 text-xs font-medium text-rose-900 shadow-sm backdrop-blur-sm sm:text-sm">
            <svg class="h-5 w-5 shrink-0 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ $errors->first('groups') }}</span>
        </div>
    @endif

    <!-- Term Switcher Bar -->
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <label class="block min-w-72 flex-1 sm:flex-none">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Academic Term</span>
            <div class="relative mt-1.5">
                <select wire:model.live="termId" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 pl-3.5 pr-10 text-sm font-semibold text-slate-800 transition-all focus:border-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}">{{ $term->name }} {{ $term->year }}{{ $term->is_current ? ' · Current' : '' }}</option>
                    @endforeach
                </select>
            </div>
            @error('termId')<span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>@enderror
        </label>

        <button type="button" wire:click="addGroup" @disabled(!$selectedTerm?->isOpen() || count($groups) >= 20) class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-xs font-bold text-amber-300 shadow-md transition-all hover:bg-slate-800 disabled:opacity-40">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Category Group
        </button>
    </div>

    <!-- Closed Term Notice -->
    @if($selectedTerm && !$selectedTerm->isOpen())
        <div class="flex items-center gap-3 rounded-xl border-l-4 border-amber-500 bg-amber-50 p-4 text-xs font-medium text-amber-900 shadow-sm sm:text-sm">
            <svg class="h-5 w-5 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <span>This term is closed. Its assessment rules are available for review and cannot be changed.</span>
        </div>
    @endif

    <!-- Main Groups Dynamic Editor -->
    <form wire:submit="save" class="space-y-6">
        @forelse($groups as $groupIndex => $group)
            <section wire:key="exam-group-{{ $group['id'] ?? 'new-'.$groupIndex }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition-all hover:shadow-md">
                <!-- Group Header Toolbar -->
                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 bg-slate-50/80 p-5">
                    <div class="min-w-64 flex-1">
                        <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Group Name</label>
                        <input wire:model="groups.{{ $groupIndex }}.name" type="text" maxlength="100" placeholder="e.g. Lower Primary" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-bold text-slate-900 transition-all placeholder:font-normal placeholder:text-slate-400 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                        @error("groups.$groupIndex.name")<span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span>@enderror
                    </div>
                    <button type="button" wire:click="removeGroup({{ $groupIndex }})" class="inline-flex items-center gap-1.5 rounded-lg border border-transparent px-3 py-2 text-xs font-bold text-rose-700 transition-colors hover:border-rose-200 hover:bg-rose-50">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Remove Group
                    </button>
                </div>

                <!-- Group Content Area -->
                <div class="grid gap-6 p-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(18rem,1fr)]">
                    <!-- Categories Weighting Column -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Categories & Weightings</h3>
                                <p class="text-xs font-normal text-slate-500">Percentages must sum up to exactly 100%.</p>
                            </div>
                            <button type="button" wire:click="addCategory({{ $groupIndex }})" @disabled(count($group['categories']) >= 12) class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-800 shadow-sm transition-all hover:bg-slate-50 hover:border-slate-300 disabled:opacity-40">
                                <svg class="h-3.5 w-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Add Category
                            </button>
                        </div>

                        <div class="space-y-2.5">
                            @foreach($group['categories'] as $categoryIndex => $category)
                                <div wire:key="exam-category-{{ $category['id'] ?? 'new-'.$groupIndex.'-'.$categoryIndex }}" class="grid grid-cols-[1fr_7.5rem_2.25rem] items-start gap-2">
                                    <div>
                                        <input wire:model="groups.{{ $groupIndex }}.categories.{{ $categoryIndex }}.name" type="text" placeholder="e.g. Midterm" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-xs font-medium text-slate-900 transition-all placeholder:text-slate-400 focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                        @error("groups.$groupIndex.categories.$categoryIndex.name")<span class="mt-0.5 block text-[11px] font-medium text-rose-600">{{ $message }}</span>@enderror
                                    </div>
                                    <div class="relative">
                                        <input wire:model="groups.{{ $groupIndex }}.categories.{{ $categoryIndex }}.weight" type="number" min="0.01" max="100" step="0.01" placeholder="0" class="w-full rounded-xl border border-slate-200 px-3 py-2 pr-7 text-xs font-semibold text-slate-900 transition-all focus:border-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-900/10">
                                        <span class="pointer-events-none absolute right-3 top-2 text-xs font-bold text-slate-400">%</span>
                                    </div>
                                    <button type="button" wire:click="removeCategory({{ $groupIndex }}, {{ $categoryIndex }})" aria-label="Remove category" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition-colors hover:bg-rose-50 hover:text-rose-700">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            @endforeach
                            @error("groups.$groupIndex.categories")<p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <!-- Assigned Classes Selector -->
                    <div class="flex flex-col rounded-xl border border-slate-200/80 bg-slate-50/50 p-4">
                        <div class="mb-3">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">Assigned Classes</h3>
                            <p class="text-xs font-normal text-slate-500">Each class uses one group per term.</p>
                        </div>
                        <div class="grid max-h-56 grid-cols-2 gap-1.5 overflow-y-auto rounded-lg border border-slate-200 bg-white p-2">
                            @foreach($classes as $class)
                                <label class="flex cursor-pointer items-center gap-2.5 rounded-md p-2 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-50">
                                    <input wire:model="groups.{{ $groupIndex }}.class_ids" type="checkbox" value="{{ $class->id }}" class="h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500 focus:ring-offset-0">
                                    <span class="truncate">{{ $class->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        @empty
            <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="mt-4 text-sm font-bold text-slate-900">No category groups set up</h3>
                <p class="mt-1 max-w-sm text-xs font-normal text-slate-500">Add a group above, define weighted categories, and attach the target classes.</p>
            </div>
        @endforelse

        <!-- Save Action Toolbar -->
        <div class="flex justify-end pt-2">
            <button type="submit" wire:loading.attr="disabled" @disabled(!$selectedTerm?->isOpen() || $termId === '') class="inline-flex items-center gap-2 rounded-xl bg-amber-400 px-6 py-3 text-xs font-black text-slate-950 shadow-md transition-all hover:bg-amber-300 active:scale-95 disabled:opacity-40">
                <svg wire:loading class="h-4 w-4 animate-spin text-slate-950" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span>Save Assessment Rules</span>
            </button>
        </div>
    </form>
</div>