<div class="space-y-6">
    <!-- Header Block with Gradient Background -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-sm">
        <div class="relative z-10 max-w-3xl">
            
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-amber-300">Learner Portal Access</h1>
            <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                Connect parent and student user accounts directly to learner records. Portal links establish school-wide identity profiles that remain active across academic terms.
            </p>
        </div>
        
        <!-- Decorative Ambient Glow -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>
    

    <!-- Feedback Alerts -->
    @if (session('status'))
        <div class="flex items-center justify-between gap-3 bg-emerald-50/80 border border-emerald-200/60 text-emerald-900 text-sm rounded-xl p-4 shadow-sm backdrop-blur-sm transition-all">
            <div class="flex items-center gap-3">
                <div class="p-1 bg-emerald-100 rounded-lg shrink-0">
                    <svg class="w-5 h-5 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="font-medium">{{ session('status') }}</span>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="flex items-center justify-between gap-3 bg-rose-50/80 border border-rose-200/60 text-rose-900 text-sm rounded-xl p-4 shadow-sm backdrop-blur-sm transition-all">
            <div class="flex items-center gap-3">
                <div class="p-1 bg-rose-100 rounded-lg shrink-0">
                    <svg class="w-5 h-5 text-rose-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Split Workspace Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">

        <!-- Panel 1: Link Existing Login -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 transition-all hover:border-slate-300">
            <div class="mb-5 pb-3 border-b border-slate-100">
                <h2 class="font-bold text-slate-900 text-base">Link Existing Login</h2>
                <p class="text-xs text-slate-500 mt-0.5">Attach a pre-existing user account to a student file.</p>
            </div>

            <form wire:submit="linkExisting" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Target Learner</label>
                    <select wire:model="studentId" class="w-full text-sm bg-slate-50/50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all shadow-sm">
                        <option value="">Select learner</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}">{{ $student->name }} · {{ $student->admission_no }}</option>
                        @endforeach
                    </select>
                    @error('studentId')
                        <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                        </span>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">User Account</label>
                    <select wire:model="userId" class="w-full text-sm bg-slate-50/50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all shadow-sm">
                        <option value="">Select account</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account->id }}">{{ $account->name }} · {{ ucfirst($account->role) }} · {{ $account->email }}</option>
                        @endforeach
                    </select>
                    @error('userId')
                        <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                        </span>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Relationship / Designation</label>
                    <input type="text" wire:model="relationship" placeholder="e.g. Mother, Father, Guardian, Self"
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all font-medium text-slate-900 placeholder:text-slate-400">
                    @error('relationship')
                        <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                        </span>
                    @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled" wire:target="linkExisting"
                    class="w-full inline-flex items-center justify-center gap-2 bg-amber-400 hover:bg-amber-300 active:bg-amber-500 text-slate-950 font-bold text-sm px-5 py-2.5 rounded-xl transition-all shadow-sm hover:shadow focus:outline-none disabled:opacity-60 cursor-pointer">
                    <span wire:loading wire:target="linkExisting" class="animate-spin"><x-edlink-loader size="16" /></span>
                    <span>Link Account</span>
                </button>
            </form>
        </div>

        <!-- Panel 2: Create & Link New Login -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 transition-all hover:border-slate-300">
            <div class="mb-5 pb-3 border-b border-slate-100">
                <h2 class="font-bold text-slate-900 text-base">Create & Link New Account</h2>
                <p class="text-xs text-slate-500 mt-0.5">Generate a new portal credential and link it in one step.</p>
            </div>

            <form wire:submit="createAndLink" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Target Learner</label>
                    <select wire:model="studentId" class="w-full text-sm bg-slate-50/50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all shadow-sm">
                        <option value="">Select learner</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}">{{ $student->name }} · {{ $student->admission_no }}</option>
                        @endforeach
                    </select>
                    @error('studentId')
                        <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                        </span>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Account Type</label>
                        <select wire:model="role" class="w-full text-sm bg-slate-50/50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all shadow-sm">
                            <option value="parent">Parent / Guardian</option>
                            <option value="student">Student</option>
                        </select>
                        @error('role')
                            <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                            </span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Relationship</label>
                        <input type="text" wire:model="relationship" placeholder="e.g. Father, Self"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all font-medium text-slate-900 placeholder:text-slate-400">
                        @error('relationship')
                            <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                            </span>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Full Name</label>
                        <input type="text" wire:model="name" placeholder="John Doe"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all font-medium text-slate-900 placeholder:text-slate-400">
                        @error('name')
                            <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                            </span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Email Address</label>
                        <input type="email" wire:model="email" placeholder="user@example.com"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all font-medium text-slate-900 placeholder:text-slate-400">
                        @error('email')
                            <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                            </span>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Temporary Password</label>
                    <input type="password" wire:model="password" placeholder="••••••••"
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all font-medium text-slate-900 placeholder:text-slate-400">
                    <p class="text-[11px] text-slate-400 mt-1">Must be at least 8 characters. Provide this password to the user securely.</p>
                    @error('password')
                        <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                        </span>
                    @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled" wire:target="createAndLink"
                    class="w-full inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 active:bg-black text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-all shadow-sm hover:shadow focus:outline-none disabled:opacity-60 cursor-pointer">
                    <span wire:loading wire:target="createAndLink" class="animate-spin"><x-edlink-loader size="16" /></span>
                    <span>Create & Link Account</span>
                </button>
            </form>
        </div>

    </div>

    <!-- Directory Table Card: Linked Accounts -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-slate-900 text-base">Active Learner Access Links</h2>
                <p class="text-xs text-slate-500 mt-0.5">Overview of all active parent and student portal connections.</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                {{ count($links) }} Active {{ Str::plural('Link', count($links)) }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50/80 border-b border-slate-200/60 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="py-3 px-4 sm:px-6">Learner</th>
                        <th class="py-3 px-4 sm:px-6">User Account</th>
                        <th class="py-3 px-4 sm:px-6 text-center">Role</th>
                        <th class="py-3 px-4 sm:px-6 text-center">Relationship</th>
                        <th class="py-3 px-4 sm:px-6 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($links as $link)
                        <tr class="hover:bg-slate-50/60 transition-colors group">
                            <!-- Learner Column -->
                            <td class="py-3.5 px-4 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-amber-100 border border-amber-200/80 text-amber-800 font-bold text-xs flex items-center justify-center shrink-0">
                                        {{ strtoupper(substr($link->student_name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 truncate">{{ $link->student_name }}</div>
                                        <div class="text-xs font-mono text-slate-400">{{ $link->admission_no }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Account Column -->
                            <td class="py-3.5 px-4 sm:px-6">
                                <div class="font-medium text-slate-900">{{ $link->user_name }}</div>
                                <div class="text-xs text-slate-500">{{ $link->email }}</div>
                            </td>

                            <!-- Role Column -->
                            <td class="py-3.5 px-4 sm:px-6 text-center">
                                @if(strtolower($link->role) === 'parent')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200/60">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                        Parent
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-50 text-sky-700 border border-sky-200/60">
                                        <span class="w-1.5 h-1.5 rounded-full bg-sky-500"></span>
                                        Student
                                    </span>
                                @endif
                            </td>

                            <!-- Relationship Column -->
                            <td class="py-3.5 px-4 sm:px-6 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-700 text-xs font-medium border border-slate-200/60">
                                    {{ $link->relationship ?: 'Direct' }}
                                </span>
                            </td>

                            <!-- Actions Column -->
                            <td class="py-3.5 px-4 sm:px-6 text-right">
                                <button wire:click="unlink({{ $link->id }})" 
                                    wire:confirm="Are you sure you want to remove this portal access link?"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl border border-rose-200 bg-rose-50 text-rose-700 text-xs font-semibold hover:bg-rose-100 hover:border-rose-300 transition-all cursor-pointer">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span>Unlink</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 px-6 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 mb-3 shadow-xs">
                                        <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-700">No portal links found</p>
                                    <p class="text-xs text-slate-400 max-w-xs mt-1">Use the forms above to attach existing parent/student accounts or create new login credentials.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>