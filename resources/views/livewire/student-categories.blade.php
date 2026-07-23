<div>
    <div class="mx-auto max-w-7xl space-y-6">
        
        <!-- HEADER BLOCK -->
          <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-sm">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-amber-300">
                    Student Categories
                </h1>
                <p class="text-sm font-medium text-slate-500 mt-1 max-w-3xl">
                    Categories like Day, Boarding, or Special determine which fee structure a student is automatically mapped to.
                </p>
                <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
            </div>
        </div>

        <!-- SESSION FEEDBACK ALERTS -->
        @if (session('status'))
            <div class="flex items-center justify-between gap-3 bg-emerald-50 border border-emerald-200/80 text-emerald-900 text-sm rounded-2xl px-4 py-3.5 shadow-xs">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-semibold">{{ session('status') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        @if (session('error'))
            <div class="flex items-center justify-between gap-3 bg-rose-50 border border-rose-200/80 text-rose-900 text-sm rounded-2xl px-4 py-3.5 shadow-xs">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-semibold">{{ session('error') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-700 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        <!-- MAIN LAYOUT GRID -->
        <div class="grid lg:grid-cols-3 gap-6 items-start">

            <!-- FORM CARD: ADD CATEGORY -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
                <div class="flex items-center gap-2 mb-5 pb-3 border-b border-slate-100">
                    <div class="w-8 h-8 rounded-xl bg-yellow-400/20 text-yellow-700 flex items-center justify-center font-bold">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <h2 class="font-bold text-slate-900 text-base">Add a Category</h2>
                </div>

                <form wire:submit="add" class="space-y-4">
                    <!-- Category Name Input -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Category Name</label>
                        <input type="text" 
                               wire:model="name" 
                               placeholder="e.g. Day, Boarding, Special"
                               class="w-full px-3.5 py-2.5 text-sm font-semibold bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent transition text-slate-900 shadow-2xs placeholder:text-slate-400 placeholder:font-normal" />
                        @error('name') 
                            <span class="text-rose-500 font-medium text-xs mt-1.5 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                {{ $message }}
                            </span> 
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" 
                            wire:loading.attr="disabled" 
                            wire:target="add"
                            class="w-full mt-2 inline-flex items-center justify-center gap-2 bg-yellow-400 hover:bg-yellow-300 text-slate-950 font-extrabold px-5 py-3 rounded-xl shadow-xs transition-all active:scale-95 text-sm cursor-pointer disabled:opacity-60">
                        <span wire:loading wire:target="add"><x-edlink-loader size="16" /></span>
                        <span>Add Category</span>
                    </button>
                </form>
            </div>

            <!-- LIST CARD: EXISTING CATEGORIES -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
                <div class="flex items-center justify-between mb-5 pb-3 border-b border-slate-100">
                    <h2 class="font-bold text-slate-900 text-base">Existing Categories</h2>
                    <span class="text-xs font-bold text-slate-400 bg-slate-100 px-2.5 py-1 rounded-lg">
                        {{ $categories->count() }} {{ Str::plural('Category', $categories->count()) }}
                    </span>
                </div>

                @if($categories->isEmpty())
                    <!-- Empty State -->
                    <div class="py-12 text-center border-2 border-dashed border-slate-100 rounded-2xl">
                        <div class="w-10 h-10 rounded-full bg-slate-50 text-slate-300 flex items-center justify-center mx-auto mb-3">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <p class="text-sm font-bold text-slate-700">No Categories Found</p>
                        <p class="text-xs text-slate-400 mt-0.5">Use the form on the left to add your first student category.</p>
                    </div>
                @else
                    <div class="divide-y divide-slate-100">
                        @foreach($categories as $category)
                            <div class="py-3.5 first:pt-0 last:pb-0 flex items-center justify-between gap-4 group">
                                <!-- Category Info -->
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200/60 flex items-center justify-center shrink-0 text-slate-700 font-bold text-xs uppercase">
                                        {{ strtoupper(substr($category->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 text-sm">
                                            {{ $category->name }}
                                        </div>
                                        <div class="text-xs font-medium text-slate-400 mt-0.5 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                            </svg>
                                            <span>
                                                <strong class="text-slate-700 font-bold">{{ $category->students_count }}</strong> 
                                                student{{ $category->students_count === 1 ? '' : 's' }} assigned
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions & Inline Deletion Confirm -->
                                <div class="shrink-0">
                                    @if($deletingId === $category->id)
                                        <div class="flex items-center gap-2 bg-rose-50 border border-rose-200/80 p-1.5 rounded-xl">
                                            <span class="text-xs font-bold text-rose-800 pl-1">Delete category?</span>
                                            <button wire:click="delete({{ $category->id }})" 
                                                    class="text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white px-2.5 py-1 rounded-lg transition">
                                                Yes
                                            </button>
                                            <button wire:click="cancelDelete" 
                                                    class="text-xs font-bold bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 px-2.5 py-1 rounded-lg transition">
                                                Cancel
                                            </button>
                                        </div>
                                    @else
                                        <button wire:click="confirmDelete({{ $category->id }})" 
                                                title="Delete Category"
                                                class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

    </div>
</div>