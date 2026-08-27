<div class="space-y-6">
    
    <!-- Top Banner / Header -->
    <header class="relative overflow-hidden rounded-3xl bg-slate-900 p-6 sm:p-8 text-white shadow-xl">
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                
                <h1 class="text-2xl sm:text-3xl font-extrabold text-amber-300 tracking-tight">
                    Parents &amp; Guardians
                </h1>
                <p class="mt-1 text-sm text-slate-500 max-w-xl">
                    Manage parent portal accounts, track contact info, and monitor linked learners across all classes.
                </p>
            </div>
            
            <a href="{{ route('parents.register') }}" 
               wire:navigate 
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-400 hover:bg-amber-500 text-slate-950 font-bold px-5 py-3 text-xs transition shadow-sm hover:shadow active:scale-[0.99] shrink-0">
                <svg class="h-4 w-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Register Parent</span>
            </a>
        </div>

        <!-- Ambient background glow -->
        <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-amber-400/10 blur-3xl pointer-events-none"></div>
    </header>

    <!-- Search & Filter Controls -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
        <div class="relative w-full sm:max-w-md">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input wire:model.live.debounce.300ms="search" 
                   type="text" 
                   placeholder="Search by name, email, or phone..." 
                   class="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-11 pr-4 text-sm font-medium text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-yellow-400 focus:outline-none focus:ring-4 focus:ring-yellow-400/20" />
        </div>
        
        @if(method_exists($parents, 'total'))
            <div class="inline-flex items-center gap-2 self-start sm:self-auto rounded-xl border border-slate-200/80 bg-white px-3.5 py-2 text-xs font-bold text-slate-600 shadow-xs">
                <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                Total Accounts: <span class="font-mono text-slate-900 font-extrabold text-sm">{{ $parents->total() }}</span>
            </div>
        @endif
    </div>

    <!-- Main Data Table Card -->
    <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-500 tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-6">Parent / Guardian</th>
                        <th class="py-3.5 px-6">Contact Info</th>
                        <th class="py-3.5 px-6">Linked Learners</th>
                        <th class="py-3.5 px-6 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($parents as $parent)
                        <tr class="hover:bg-slate-50/60 transition">
                            
                            <!-- Name & Avatar -->
                            <td class="py-4 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-2xl bg-amber-400/10 border border-amber-400/20 text-amber-800 flex items-center justify-center font-extrabold text-xs uppercase shrink-0">
                                        {{ substr($parent->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-900">{{ $parent->name }}</p>
                                        <p class="text-[11px] font-semibold text-slate-400 mt-0.5">ID: #{{ $parent->id }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Contact Info -->
                            <td class="py-4 px-6">
                                <div class="space-y-1 text-xs">
                                    <div class="text-slate-800 font-semibold flex items-center gap-2">
                                        <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                        <span>{{ $parent->email }}</span>
                                    </div>
                                    <div class="font-mono text-slate-500 flex items-center gap-2">
                                        <svg class="h-3.5 w-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        <span>{{ $parent->phone ?: 'No phone recorded' }}</span>
                                    </div>
                                </div>
                            </td>

                            <!-- Linked Learners -->
                            <td class="py-4 px-6">
                                <div class="flex flex-wrap gap-1.5 max-w-sm">
                                    @forelse($parent->portalStudents as $student)
                                        <span class="inline-flex items-center gap-1.5 bg-slate-100/80 border border-slate-200/80 text-slate-800 text-[11px] px-2.5 py-1 rounded-lg font-semibold">
                                            <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                            <span>{{ $student->name }}</span>
                                            <span class="text-slate-400 font-normal">• {{ $student->schoolClass?->name ?? 'N/A' }}</span>
                                        </span>
                                    @empty
                                        <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-rose-600 bg-rose-50 border border-rose-100 px-2.5 py-1 rounded-lg">
                                            <svg class="h-3 w-3 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            <span>No learner linked</span>
                                        </span>
                                    @endforelse
                                </div>
                            </td>

                            <!-- Status Badge -->
                            <td class="py-4 px-6 text-center whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 border border-emerald-200 text-emerald-800">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    <span>Active</span>
                                </span>
                            </td>
                        </tr>
                    @empty
                        <!-- Empty State -->
                        <tr>
                            <td colspan="4" class="py-12 px-6 text-center">
                                <div class="max-w-xs mx-auto text-center space-y-2">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 border border-slate-200 text-slate-400 flex items-center justify-center mx-auto">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    </div>
                                    <p class="text-sm font-extrabold text-slate-800">No Parent Accounts Found</p>
                                    <p class="text-xs text-slate-500">Try adjusting your search query or register a new parent account.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- Pagination -->
    @if(method_exists($parents, 'hasPages') && $parents->hasPages())
        <div class="pt-2">
            {{ $parents->links() }}
        </div>
    @endif

</div>
