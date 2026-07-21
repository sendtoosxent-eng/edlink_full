<!-- REMOVED max-w-5xl TO LET IT ACCEPTE MORE HORIZONTAL SPACE -->
<div class="mx-auto w-full space-y-4">
    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
            <p class="font-bold">The staff record has not been saved yet.</p>
            <ul class="mt-1 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif
    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
    @endif
    <!-- TOP NAVIGATION & DIRECTORY LINK -->
    <div>
        <a href="{{ route('staff.index') }}" wire:navigate class="inline-flex items-center gap-1 text-[11px] font-bold text-yellow-600 hover:text-yellow-700 transition">
            <i class="fa fa-arrow-left text-[9px]"></i> 
            <span>Staff directory</span>
        </a>
        <h1 class="mt-1 text-lg font-bold tracking-tight text-slate-900">Register staff member</h1>
    </div>
 
    <!-- PROGRESSIVE TRACKING STEP TIMELINE -->
    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 bg-slate-100/60 border border-slate-200/40 p-2.5 rounded-xl text-[11px] font-bold shadow-xs">
        <div class="flex items-center gap-1.5 {{ $step >= 1 ? 'text-yellow-600' : 'text-slate-400' }}">
            <span class="w-4 h-4 rounded-full flex items-center justify-center text-[9px] {{ $step >= 1 ? 'bg-yellow-400 text-slate-950 shadow-xs' : 'bg-slate-200 text-slate-500' }}">1</span>
            <span>Personal account</span>
        </div>
        <div class="hidden sm:block h-px w-4 bg-slate-300"></div>
        <div class="flex items-center gap-1.5 {{ $step >= 2 ? 'text-yellow-600' : 'text-slate-400' }}">
            <span class="w-4 h-4 rounded-full flex items-center justify-center text-[9px] {{ $step >= 2 ? 'bg-yellow-400 text-slate-950 shadow-xs' : 'bg-slate-200 text-slate-500' }}">2</span>
            <span>Employment role</span>
        </div>
        <div class="hidden sm:block h-px w-4 bg-slate-300"></div>
        <div class="flex items-center gap-1.5 {{ $step >= 3 ? 'text-yellow-600' : 'text-slate-400' }}">
            <span class="w-4 h-4 rounded-full flex items-center justify-center text-[9px] {{ $step >= 3 ? 'bg-yellow-400 text-slate-950 shadow-xs' : 'bg-slate-200 text-slate-500' }}">3</span>
            <span>Payroll & review</span>
        </div>
    </div>
 
    <!-- EXPANDED 5-COLUMN ASYNC GRID (60% Form / 40% Preview) -->
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-5 items-start">
        
        <!-- COLUMN 1: FORM MULTI-STEP BLOCK (TAKES 3/5 OF THE SPACE) -->
        <form wire:submit="{{ $step === 3 ? 'register' : 'next' }}" class="lg:col-span-3 relative rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden before:absolute before:top-0 before:left-0 before:right-0 before:h-1 before:bg-yellow-400 W-FULL">
            
            <!-- STEP 1: PERSONAL & ACCOUNT SCHEMATICS -->
            @if($step === 1)
            <div class="px-4 py-3 bg-slate-50/50 border-b border-slate-100 flex items-center gap-2">
                <div class="w-6 h-6 rounded-md bg-yellow-400 text-slate-950 font-bold flex items-center justify-center shadow-xs shrink-0">
                    <i class="fa fa-user text-[10px]"></i>
                </div>
                <div>
                    <h2 class="text-xs font-bold text-slate-900">Personal and account details</h2>
                    <p class="text-[9px] text-slate-400">Capture legal identity records and login vectors.</p>
                </div>
            </div>

            <div class="p-4 space-y-3.5">
                <!-- CIRCULAR PHOTO UPLOAD CONTAINER -->
                <div class="flex items-center gap-4 bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <div class="relative w-12 h-12 rounded-full border border-slate-200 bg-slate-200 flex-shrink-0 overflow-hidden shadow-2xs">
                        @if ($photo)
                            <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-slate-400 bg-slate-100">
                                <i class="fa fa-user text-base"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-0.5">Staff profile photo <span class="text-slate-400 font-normal">(optional)</span></label>
                        <input wire:model="photo" type="file" accept="image/*" class="text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[9px] file:font-bold file:bg-slate-900 file:text-white hover:file:bg-slate-800 cursor-pointer transition">
                    </div>
                </div>

                <div class="grid gap-3.5 grid-cols-1 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Full name</label>
                        <input wire:model="name" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="e.g. Mukasa Ronald">
                    </div>
                    
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Email address</label>
                        <input wire:model="email" type="email" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="name@school.com">
                    </div>
                    
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Phone line</label>
                        <input wire:model="phone" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="e.g. +256...">
                    </div>
                    
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Temporary password</label>
                        <input wire:model="password" type="password" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900">
                    </div>
                    
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Confirm password</label>
                        <input wire:model="password_confirmation" type="password" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900">
                    </div>
                </div>
            </div>

            <!-- STEP 2: EMPLOYMENT CATEGORIZATION -->
            @elseif($step === 2)
            <div class="px-4 py-3 bg-slate-50/50 border-b border-slate-100 flex items-center gap-2">
                <div class="w-6 h-6 rounded-md bg-slate-900 text-white font-bold flex items-center justify-center shadow-xs shrink-0">
                    <i class="fa fa-briefcase text-[10px]"></i>
                </div>
                <div>
                    <h2 class="text-xs font-bold text-slate-900">Employment role</h2>
                    <p class="text-[9px] text-slate-400">Assign permissions and designation tracking.</p>
                </div>
            </div>

            <div class="p-4 grid gap-3.5 grid-cols-1 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Job title</label>
                    <input wire:model="job_title" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-400" placeholder="e.g. Senior Physics Teacher">
                </div>
                
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">System role</label>
                    <select wire:model="role" class="w-full text-xs px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 text-slate-800 font-semibold transition shadow-sm">
                        <option value="teacher">Teacher</option>
                        <option value="bursar">Bursar</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Designation</label>
                    <select wire:model="designation_id" class="w-full text-xs px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 text-slate-800 font-semibold transition shadow-sm">
                        <option value="">No designation / existing role access</option>
                        @foreach($designations as $designation)<option value="{{ $designation->id }}">{{ $designation->name }}</option>@endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Joining date</label>
                    <input wire:model="joined_at" type="date" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-medium text-slate-900">
                </div>
            </div>

            <!-- STEP 3: PAYROLL MATRIX & AUDIT REVIEW -->
            @else
            <div class="px-4 py-3 bg-slate-50/50 border-b border-slate-100 flex items-center gap-2">
                <div class="w-6 h-6 rounded-md bg-emerald-500 text-white font-bold flex items-center justify-center shadow-xs shrink-0">
                    <i class="fa fa-money text-[10px]"></i>
                </div>
                <div>
                    <h2 class="text-xs font-bold text-slate-900">Payroll parameters</h2>
                    <p class="text-[9px] text-slate-400">Determine compensation metrics and review credentials.</p>
                </div>
            </div>

            <div class="p-4">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-700 mb-1">Monthly salary</label>
                    <div class="relative w-full">
                        <span class="absolute left-3 top-2 text-xs font-bold text-slate-400 font-mono">UGX</span>
                        <input wire:model="base_salary" type="number" min="0" class="w-full text-xs font-mono pl-12 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 transition font-bold text-slate-900" placeholder="0">
                    </div>
                </div>
            </div>
            @endif

            <!-- DYNAMIC ACTION TOGGLE BASEMENT -->
            <div class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                @if($step > 1)
                    <button type="button" wire:click="back" class="inline-flex items-center justify-center bg-white hover:bg-slate-100 border border-slate-200 text-slate-800 font-bold text-[11px] px-3.5 py-2 rounded-lg transition shadow-2xs focus:outline-none">
                        Back
                    </button>
                @else
                    <div></div>
                @endif

                <button type="submit" class="inline-flex items-center justify-center gap-1 {{ $step === 3 ? 'bg-slate-900 hover:bg-slate-800 text-white' : 'bg-yellow-500 hover:bg-yellow-400 text-slate-950' }} font-bold text-[11px] px-4.5 py-2 rounded-lg transition shadow-sm focus:outline-none">
                    <span>{{ $step === 3 ? 'Register staff' : 'Continue' }}</span>
                    <i class="fa {{ $step === 3 ? 'fa-check' : 'fa-arrow-right' }} text-[9px]"></i>
                </button>
            </div>
        </form>

        <!-- COLUMN 2: LIVE CONTEXTUAL DATA PREVIEW (TAKES 2/5 OF THE SPACE) -->
        <div class="lg:col-span-2 relative rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden p-4 before:absolute before:top-0 before:left-0 before:right-0 before:h-1 before:bg-yellow-400 lg:sticky lg:top-4 w-full">
            <span class="block text-[9px] font-bold uppercase tracking-wider text-slate-400 mb-3">Live Staff Account Profile Preview</span>
            
            <div class="space-y-4">
                <div class="flex items-start gap-3.5">
                    <!-- CIRCULAR PREVIEW IMAGE -->
                    <div class="w-14 h-14 rounded-full border border-slate-200 bg-slate-50 flex-shrink-0 overflow-hidden shadow-2xs">
                        @if ($photo)
                            <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-slate-300 bg-slate-50">
                                <i class="fa fa-user text-lg"></i>
                            </div>
                        @endif
                    </div>

                    <!-- IDENTITY INFO METRICS -->
                    <div class="space-y-1 flex-1 min-w-0">
                        <h3 class="text-xs font-bold text-slate-900 truncate">{{ $name ?: 'Unnamed Individual' }}</h3>
                        
                        <div class="space-y-0.5">
                            <p class="text-[11px] font-semibold text-slate-700 truncate"><?php echo $job_title ?: 'No Title Designated'; ?></p>
                            <div>
                                <span class="inline-flex items-center bg-sky-50 text-sky-700 font-bold px-1.5 py-0.5 rounded text-[9px] border border-sky-100/60 uppercase tracking-wide">{{ $role ?: 'Teacher' }}</span>
                            </div>
                        </div>

                        @if($email)
                            <p class="text-[10px] text-slate-400 font-mono truncate pt-0.5"><i class="fa fa-envelope-o mr-1"></i>{{ $email }}</p>
                        @endif
                    </div>
                </div>

                <!-- STATUS MANAGER INTERACTIVE BLOCK -->
                <div class="flex items-center justify-between gap-3 bg-slate-50 border border-slate-100 p-3 rounded-lg">
                    <div>
                        <span class="block text-[8px] font-bold uppercase tracking-wider text-slate-400">System Access Status</span>
                        <span class="inline-flex items-center gap-1 mt-0.5">
                            <span class="w-1 h-1 rounded-full {{ $employment_status === 'active' ? 'bg-emerald-500 shadow-emerald-500/50' : 'bg-rose-400 shadow-rose-400/50' }} animate-pulse"></span>
                            <span class="text-[10px] font-bold {{ $employment_status === 'active' ? 'text-emerald-700' : 'text-rose-700' }} uppercase tracking-wider">{{ $employment_status ?: 'Active' }}</span>
                        </span>
                    </div>

                    <div>
                        @if($employment_status === 'active')
                            <button type="button" wire:click="$set('employment_status', 'inactive')" class="inline-flex items-center justify-center bg-white border border-rose-200 hover:bg-rose-50 text-rose-700 font-bold text-[9px] px-2.5 py-1.5 rounded-lg transition shadow-2xs focus:outline-none>
                                Deactivate Account
                            </button>
                        @else
                            <button type="button" wire:click="$set('employment_status', 'active')" class="inline-flex items-center justify-center bg-slate-900 hover:bg-slate-800 text-white font-bold text-[9px] px-2.5 py-1.5 rounded-lg transition shadow-2xs focus:outline-none">
                                Activate Account
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
