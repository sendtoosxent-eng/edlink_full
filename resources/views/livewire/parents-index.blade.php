<div>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8 space-y-6">
        
        <!-- HEADER SECTION -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900">
                    Parents & Guardians
                </h1>
                <p class="text-sm font-medium text-slate-500 mt-1">
                    Manage parent portal accounts and view their linked learners.
                </p>
            </div>
            
            <a href="{{ route('parents.register') }}" 
               wire:navigate 
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-yellow-400 hover:bg-yellow-300 text-slate-950 font-extrabold px-5 py-3 shadow-xs hover:shadow-md transition-all duration-200 active:scale-95 text-sm">
                <i class="fa fa-plus text-xs"></i>
                <span>Register Parent</span>
            </a>
        </div>

        <!-- SEARCH & FILTER BAR -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="relative w-full max-w-md">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fa fa-search text-sm"></i>
                </div>
                <input wire:model.live.debounce.300ms="search" 
                       type="text" 
                       placeholder="Search name, email, or phone..." 
                       class="w-full pl-10 pr-4 py-2.5 bg-white text-sm font-medium text-slate-800 placeholder-slate-400 border border-slate-200 rounded-xl shadow-2xs focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent transition" />
            </div>
            
            <!-- Optional Result Count Badge -->
            @if(method_exists($parents, 'total'))
                <div class="text-xs font-bold text-slate-500 bg-slate-200/60 px-3 py-1.5 rounded-lg self-end sm:self-auto">
                    Total Accounts: <span class="text-slate-900 font-mono">{{ $parents->total() }}</span>
                </div>
            @endif
        </div>

        <!-- PARENTS TABLE CARD -->
        <div class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="bg-slate-900 text-white font-bold text-xs uppercase tracking-wider">
                            <th class="py-3.5 px-5">Parent / Guardian</th>
                            <th class="py-3.5 px-5">Contact Info</th>
                            <th class="py-3.5 px-5">Linked Learners</th>
                            <th class="py-3.5 px-5 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @forelse($parents as $parent)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <!-- Parent Name & Avatar -->
                                <td class="py-4 px-5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-black text-slate-700 uppercase text-xs shrink-0">
                                            {{ substr($parent->name, 0, 2) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900">{{ $parent->name }}</div>
                                            <span class="text-[11px] font-semibold text-slate-400">ID: #{{ $parent->id }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Contact Details -->
                                <td class="py-4 px-5">
                                    <div class="flex flex-col space-y-0.5">
                                        <div class="text-slate-900 font-medium flex items-center gap-1.5">
                                            <i class="fa fa-envelope text-slate-400 text-xs"></i>
                                            <span>{{ $parent->email }}</span>
                                        </div>
                                        <div class="text-xs font-mono text-slate-500 flex items-center gap-1.5">
                                            <i class="fa fa-phone text-slate-400 text-xs"></i>
                                            <span>{{ $parent->phone ?: 'No phone recorded' }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Linked Learners -->
                                <td class="py-4 px-5">
                                    <div class="flex flex-wrap gap-1.5 max-w-xs">
                                        @forelse($parent->portalStudents as $student)
                                            <span class="inline-flex items-center gap-1.5 bg-slate-100 border border-slate-200 text-slate-800 text-xs px-2.5 py-1 rounded-lg font-semibold">
                                                <i class="fa fa-user text-[10px] text-slate-400"></i>
                                                <span>{{ $student->name }}</span>
                                                <span class="text-slate-400 text-[10px]">• {{ $student->schoolClass?->name ?? 'N/A' }}</span>
                                            </span>
                                        @empty
                                            <span class="inline-flex items-center gap-1.5 text-xs font-bold text-rose-600 bg-rose-50 border border-rose-100 px-2.5 py-1 rounded-lg">
                                                <i class="fa fa-exclamation-circle text-xs"></i>
                                                <span>No learner linked</span>
                                            </span>
                                        @endforelse
                                    </div>
                                </td>

                                <!-- Account Status -->
                                <td class="py-4 px-5 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 border border-emerald-200 text-emerald-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        <span>Active</span>
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <!-- Empty State -->
                            <tr>
                                <td colspan="4" class="py-12 px-4 text-center">
                                    <div class="max-w-xs mx-auto text-center space-y-2">
                                        <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto text-xl">
                                            <i class="fa fa-users"></i>
                                        </div>
                                        <p class="text-base font-bold text-slate-800">No Parent Accounts Found</p>
                                        <p class="text-xs text-slate-500">Try adjusting your search criteria or register a new parent.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PAGINATION LINK -->
        @if(method_exists($parents, 'hasPages') && $parents->hasPages())
            <div class="pt-2">
                {{ $parents->links() }}
            </div>
        @endif

    </div>
</div>