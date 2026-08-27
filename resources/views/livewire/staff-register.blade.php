<div class="space-y-6">
    <!-- Header Banner -->
    <header class="relative overflow-hidden rounded-3xl bg-slate-950 p-6 text-white shadow-xl shadow-slate-950/10 sm:p-8">
        <div class="relative z-10 flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div class="max-w-3xl space-y-1.5">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-400/20 bg-amber-400/10 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-amber-400 backdrop-blur-sm">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                    Staff Onboarding
                </div>
                <h1 class="text-2xl font-black tracking-tight text-white sm:text-3xl">Create a staff account</h1>
                <p class="text-sm leading-relaxed text-slate-300">Set up login access, designation, payroll, and teaching responsibilities in one seamless flow.</p>
            </div>
            <a href="{{ route('staff.index') }}" wire:navigate class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-white/10 bg-white/10 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-white/20 active:scale-95">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Staff Directory
            </a>
        </div>
        <div class="pointer-events-none absolute -bottom-24 -right-16 h-72 w-72 rounded-full bg-amber-500/15 blur-3xl"></div>
        <div class="pointer-events-none absolute -top-24 -left-16 h-72 w-72 rounded-full bg-indigo-500/10 blur-3xl"></div>
    </header>

    <!-- Global Errors Alert -->
    @if ($errors->any())
        <div class="flex items-start gap-3 rounded-2xl border border-rose-200/80 bg-rose-50/90 p-4 text-xs text-rose-900 shadow-sm backdrop-blur-sm" role="alert">
            <div class="mt-0.5 rounded-lg bg-rose-100 p-1 text-rose-600">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="space-y-1">
                <p class="font-bold">Please correct the following errors before proceeding:</p>
                <ul class="list-disc space-y-0.5 pl-4 text-rose-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Stepper Navigation -->
    <nav aria-label="Registration progress" class="grid gap-2 rounded-2xl border border-slate-200/80 bg-white p-2 shadow-sm sm:grid-cols-3">
        @foreach ([1 => ['Account', 'Identity & login'], 2 => ['Employment', 'Designation & salary'], 3 => ['Responsibilities', 'Scope & final review']] as $number => [$label, $description])
            <div class="flex items-center gap-3 rounded-xl px-3.5 py-3 transition-all {{ $step === $number ? 'bg-amber-50/80 ring-1 ring-amber-300' : ($step > $number ? 'bg-emerald-50/60' : 'bg-slate-50/50') }}">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-black transition-colors {{ $step === $number ? 'bg-amber-400 text-slate-950 shadow-sm' : ($step > $number ? 'bg-emerald-600 text-white' : 'bg-slate-200/80 text-slate-500') }}">
                    @if ($step > $number)
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    @else
                        {{ $number }}
                    @endif
                </span>
                <div class="min-w-0">
                    <strong class="block truncate text-xs font-bold text-slate-900">{{ $label }}</strong>
                    <span class="block truncate text-[11px] text-slate-500">{{ $description }}</span>
                </div>
            </div>
        @endforeach
    </nav>

    <!-- Main Grid Content -->
    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        
        <!-- Form Container -->
        <form wire:submit="{{ $step === 3 ? 'register' : 'next' }}" class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-sm">
            
            <!-- STEP 1: Identity & Login -->
            @if ($step === 1)
                <section>
                    <header class="border-b border-slate-100 bg-slate-50/60 px-6 py-4">
                        <h2 class="text-sm font-bold text-slate-900">Identity & Login Credentials</h2>
                        <p class="mt-0.5 text-xs text-slate-500">The provided email and initial password will serve as primary system credentials.</p>
                    </header>
                    <div class="grid gap-5 p-6 sm:grid-cols-2">
                        <label class="sm:col-span-2">
                            <span class="mb-1.5 block text-xs font-semibold text-slate-700">Full Name <b class="text-rose-500">*</b></span>
                            <input wire:model="name" autocomplete="name" class="w-full rounded-xl border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-400/20" placeholder="e.g. Sarah Nakato">
                            @error('name') <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span> @enderror
                        </label>
                        
                        <label>
                            <span class="mb-1.5 block text-xs font-semibold text-slate-700">Email Address <b class="text-rose-500">*</b></span>
                            <input wire:model="email" type="email" autocomplete="email" class="w-full rounded-xl border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-400/20" placeholder="teacher@school.com">
                            @error('email') <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span> @enderror
                        </label>
                        
                        <label>
                            <span class="mb-1.5 block text-xs font-semibold text-slate-700">Phone Number</span>
                            <input wire:model="phone" type="tel" autocomplete="tel" class="w-full rounded-xl border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-400/20" placeholder="+256 700 000000">
                            @error('phone') <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span> @enderror
                        </label>
                        
                        <label>
                            <span class="mb-1.5 block text-xs font-semibold text-slate-700">Temporary Password <b class="text-rose-500">*</b></span>
                            <input wire:model="password" type="password" autocomplete="new-password" class="w-full rounded-xl border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-400/20">
                            <span class="mt-1 block text-[11px] text-slate-500">Minimum 8 characters. The staff member can update this later.</span>
                            @error('password') <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span> @enderror
                        </label>
                        
                        <label>
                            <span class="mb-1.5 block text-xs font-semibold text-slate-700">Confirm Password <b class="text-rose-500">*</b></span>
                            <input wire:model="password_confirmation" type="password" autocomplete="new-password" class="w-full rounded-xl border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-400/20">
                        </label>
                    </div>
                </section>

            <!-- STEP 2: Employment & Access -->
            @elseif ($step === 2)
                <section>
                    <header class="border-b border-slate-100 bg-slate-50/60 px-6 py-4">
                        <h2 class="text-sm font-bold text-slate-900">Employment & Administrative Settings</h2>
                        <p class="mt-0.5 text-xs text-slate-500">System designations drive module visibility, permissions, and salary structures.</p>
                    </header>
                    <div class="grid gap-5 p-6 sm:grid-cols-2">
                        <label>
                            <span class="mb-1.5 block text-xs font-semibold text-slate-700">Account Type <b class="text-rose-500">*</b></span>
                            <select wire:model.live="role" class="w-full rounded-xl border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm font-medium transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-400/20">
                                <option value="teacher">Teacher</option>
                                <option value="bursar">Finance / Bursar</option>
                                <option value="academic_admin">Director of Studies</option>
                                <option value="registrar">Registrar</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </label>
                        
                        <label>
                            <span class="mb-1.5 block text-xs font-semibold text-slate-700">Designation @if($role !== 'admin')<b class="text-rose-500">*</b>@endif</span>
                            <select wire:model.live="designation_id" @disabled($role === 'admin') class="w-full rounded-xl border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm font-medium transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-400/20 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:opacity-60">
                                <option value="">{{ $role === 'admin' ? 'Administrator uses unrestricted access' : 'Choose a designation' }}</option>
                                @foreach ($designations as $designation)
                                    <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                                @endforeach
                            </select>
                            @error('designation_id') <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span> @enderror
                        </label>
                        
                        <label>
                            <span class="mb-1.5 block text-xs font-semibold text-slate-700">Job Title <b class="text-rose-500">*</b></span>
                            <input wire:model="job_title" class="w-full rounded-xl border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-400/20" placeholder="e.g. Senior Mathematics Teacher">
                            @error('job_title') <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span> @enderror
                        </label>
                        
                        <label>
                            <span class="mb-1.5 block text-xs font-semibold text-slate-700">Joining Date <b class="text-rose-500">*</b></span>
                            <input wire:model="joined_at" type="date" class="w-full rounded-xl border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-400/20">
                            @error('joined_at') <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span> @enderror
                        </label>
                        
                        <label>
                            <span class="mb-1.5 block text-xs font-semibold text-slate-700">Monthly Base Salary <b class="text-rose-500">*</b></span>
                            <div class="relative">
                                <span class="absolute left-3.5 top-2.5 text-xs font-black text-slate-400">UGX</span>
                                <input wire:model="base_salary" type="number" min="0" step="0.01" class="w-full rounded-xl border-slate-200 bg-slate-50/50 pl-14 pr-3.5 py-2.5 text-sm font-bold text-slate-900 transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-400/20">
                            </div>
                            @error('base_salary') <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span> @enderror
                        </label>
                        
                        <label>
                            <span class="mb-1.5 block text-xs font-semibold text-slate-700">Contract Type</span>
                            <select wire:model="contract_type" class="w-full rounded-xl border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-400/20">
                                <option value="permanent">Permanent</option>
                                <option value="contract">Contract</option>
                                <option value="part_time">Part-time</option>
                                <option value="volunteer">Volunteer</option>
                            </select>
                        </label>
                        
                        <label>
                            <span class="mb-1.5 block text-xs font-semibold text-slate-700">Employment Status</span>
                            <select wire:model="employment_status" class="w-full rounded-xl border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-400/20">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </label>
                        
                        <label>
                            <span class="mb-1.5 block text-xs font-semibold text-slate-700">Probation End Date</span>
                            <input wire:model="probation_ends_at" type="date" class="w-full rounded-xl border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-400/20">
                            @error('probation_ends_at') <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span> @enderror
                        </label>

                        @if ($role !== 'teacher' && $role !== 'admin')
                            <label class="sm:col-span-2 flex cursor-pointer items-start gap-3 rounded-xl border border-indigo-200/80 bg-indigo-50/50 p-4 transition hover:bg-indigo-50">
                                <input wire:model.live="has_teaching_duties" type="checkbox" class="mt-0.5 rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500">
                                <div>
                                    <strong class="block text-xs font-bold text-indigo-950">This staff member also teaches</strong>
                                    <span class="mt-0.5 block text-[11px] text-indigo-700 leading-snug">Enable if administrative staff like a DOS or Bursar will be assigned to subject teaching duties.</span>
                                </div>
                            </label>
                        @endif

                        @if ($role === 'admin')
                            <label class="sm:col-span-2 flex cursor-pointer items-start gap-3 rounded-xl border border-amber-200/80 bg-amber-50/50 p-4 transition hover:bg-amber-50">
                                <input wire:model="admin_confirmation" type="checkbox" class="mt-0.5 rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                                <div>
                                    <strong class="block text-xs font-bold text-amber-950">Confirm unrestricted administrator access</strong>
                                    <span class="mt-0.5 block text-[11px] text-amber-800 leading-snug">Administrators hold system-wide permissions across all school management modules.</span>
                                </div>
                            </label>
                            @error('admin_confirmation') <span class="sm:col-span-2 text-xs font-medium text-rose-600">{{ $message }}</span> @enderror
                        @endif
                    </div>
                </section>

            <!-- STEP 3: Responsibilities & HR -->
            @else
                <section>
                    <header class="border-b border-slate-100 bg-slate-50/60 px-6 py-4">
                        <h2 class="text-sm font-bold text-slate-900">Teaching Scope & Profile Details</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Map specific classes and subjects, and record optional personal details.</p>
                    </header>
                    
                    <div class="space-y-6 p-6">
                        @if ($has_teaching_duties)
                            @if (! $currentTerm)
                                <div class="rounded-xl border border-rose-200/80 bg-rose-50/80 p-4 text-xs font-medium text-rose-900">
                                    <strong>No active academic term found.</strong> Please establish or activate an academic term before assigning teaching duties.
                                </div>
                            @else
                                <div class="flex flex-col gap-3 rounded-xl border border-emerald-200/80 bg-emerald-50/60 p-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <strong class="block text-xs font-bold text-emerald-950">Active Term Scope: {{ $currentTerm->name }}, {{ $currentTerm->year }}</strong>
                                        <span class="mt-0.5 block text-[11px] text-emerald-800">Assignments automatically dictate teacher dashboard filters, grade entry, and attendance rosters.</span>
                                    </div>
                                    <span class="w-fit shrink-0 rounded-full bg-emerald-600 px-3 py-1 text-[10px] font-black tracking-wider text-white uppercase">Current Term</span>
                                </div>
                            @endif

                            <!-- Class Teacher Assignment Card -->
                            <div class="rounded-2xl border border-slate-200/80 bg-slate-50/30 p-4 space-y-3">
                                <label class="flex cursor-pointer items-start gap-3">
                                    <input wire:model.live="is_class_teacher" type="checkbox" class="mt-0.5 rounded border-slate-300 text-amber-500 focus:ring-amber-400">
                                    <div>
                                        <strong class="block text-xs font-bold text-slate-900">Assign as Primary Class Teacher</strong>
                                        <span class="mt-0.5 block text-[11px] text-slate-500">Grants full supervisory authority for attendance and report card processing for a single class.</span>
                                    </div>
                                </label>
                                
                                @if ($is_class_teacher)
                                    <label class="mt-3 block pl-7">
                                        <span class="mb-1.5 block text-xs font-semibold text-slate-700">Select Target Class <b class="text-rose-500">*</b></span>
                                        <select wire:model="class_teacher_class_id" class="w-full rounded-xl border-slate-200 bg-white px-3.5 py-2.5 text-sm transition focus:border-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-400/20">
                                            <option value="">Choose an available class</option>
                                            @foreach ($classes as $class)
                                                <option value="{{ $class->id }}" @disabled($class->class_teacher_user_id)>
                                                    {{ $class->name }}{{ $class->classTeacher ? ' — Assigned to '.$class->classTeacher->name : '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('class_teacher_class_id') <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span> @enderror
                                    </label>
                                @endif
                            </div>

                            <!-- Subject Mapping Builder -->
                            <div class="rounded-2xl border border-slate-200/80 bg-slate-50/30 p-4 space-y-4">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <h3 class="text-xs font-bold text-slate-900">Class & Subject Allocations</h3>
                                        <p class="text-[11px] text-slate-500">Map specific subjects taught across different class levels.</p>
                                    </div>
                                    <button type="button" wire:click="addTeachingAssignment" class="inline-flex w-fit items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-[11px] font-bold text-white shadow-sm transition hover:bg-slate-800 active:scale-95">
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                        Add Allocation
                                    </button>
                                </div>

                                <div class="space-y-3">
                                    @foreach ($teaching_assignments as $index => $assignment)
                                        <div wire:key="teaching-assignment-{{ $index }}" class="grid gap-3 rounded-xl border border-slate-200/60 bg-white p-3.5 sm:grid-cols-[1fr_1.5fr_auto] sm:items-start shadow-xs">
                                            <label>
                                                <span class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Class</span>
                                                <select wire:model.live="teaching_assignments.{{ $index }}.class_id" class="w-full rounded-lg border-slate-200 bg-slate-50/50 text-xs transition focus:border-amber-400 focus:bg-white focus:ring-2 focus:ring-amber-400/20">
                                                    <option value="">Select class</option>
                                                    @foreach ($classes as $class)
                                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            
                                            <label>
                                                <span class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-400">Subjects (Hold Ctrl / Cmd for multiple)</span>
                                                <select wire:model="teaching_assignments.{{ $index }}.subject_ids" multiple size="{{ min(4, max(2, $subjects->count())) }}" class="w-full rounded-lg border-slate-200 bg-slate-50/50 p-1 text-xs transition focus:border-amber-400 focus:bg-white focus:ring-2 focus:ring-amber-400/20">
                                                    @foreach ($subjects as $subject)
                                                        <option value="{{ $subject->id }}" class="rounded px-2 py-1">{{ $subject->name }}{{ $subject->code ? ' ('.$subject->code.')' : '' }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            
                                            <div class="pt-5">
                                                <button type="button" wire:click="removeTeachingAssignment({{ $index }})" class="rounded-lg border border-rose-200 bg-rose-50/50 p-2 text-rose-600 transition hover:bg-rose-100 hover:text-rose-700" title="Remove row">
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if ($classes->isEmpty() || $subjects->isEmpty())
                                    <p class="rounded-xl border border-amber-200/80 bg-amber-50/60 p-3 text-xs font-medium text-amber-900">
                                        ⚠️ Configure at least one active class and subject in settings before mapping teaching duties.
                                    </p>
                                @endif
                                @error('teaching_assignments') <span class="mt-1 block text-xs font-medium text-rose-600">{{ $message }}</span> @enderror
                            </div>
                        @else
                            <div class="rounded-2xl border border-slate-200/80 bg-slate-50/50 p-5 text-center text-xs text-slate-500">
                                Teaching assignments disabled. Access permissions will be limited to designated non-teaching administrative modules.
                            </div>
                        @endif

                        <!-- HR & Emergency Info Section -->
                        <div class="rounded-2xl border border-slate-200/80 bg-slate-50/30 p-4 space-y-4">
                            <div>
                                <h3 class="text-xs font-bold text-slate-900">Optional HR & Payroll Particulars</h3>
                                <p class="text-[11px] text-slate-500">Can be entered now or updated later from the employee profile page.</p>
                            </div>
                            
                            <div class="grid gap-4 sm:grid-cols-2">
                                <label><span class="mb-1 block text-[11px] font-semibold text-slate-600">Emergency Contact Name</span><input wire:model="emergency_contact_name" class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20"></label>
                                <label><span class="mb-1 block text-[11px] font-semibold text-slate-600">Emergency Phone</span><input wire:model="emergency_contact_phone" class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20"></label>
                                <label><span class="mb-1 block text-[11px] font-semibold text-slate-600">National Identification (NIN)</span><input wire:model="national_id" class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20"></label>
                                <label><span class="mb-1 block text-[11px] font-semibold text-slate-600">Bank Name</span><input wire:model="bank_name" class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20"></label>
                                <label><span class="mb-1 block text-[11px] font-semibold text-slate-600">Bank Account Name</span><input wire:model="bank_account_name" class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20"></label>
                                <label><span class="mb-1 block text-[11px] font-semibold text-slate-600">Bank Account Number</span><input wire:model="bank_account_number" class="w-full rounded-xl border-slate-200 bg-white px-3 py-2 text-xs transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20"></label>
                            </div>
                        </div>
                    </div>
                </section>
            @endif

            <!-- Form Actions Footer -->
            <footer class="flex items-center justify-between border-t border-slate-100 bg-slate-50/60 px-6 py-4">
                @if ($step > 1)
                    <button type="button" wire:click="back" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-2xs transition hover:bg-slate-100 active:scale-95">
                        Back
                    </button>
                @else
                    <div></div>
                @endif
                
                <button type="submit" wire:loading.attr="disabled" wire:target="next,register" class="inline-flex items-center gap-2 rounded-xl bg-amber-400 px-6 py-2.5 text-xs font-black text-slate-950 shadow-sm transition hover:bg-amber-300 active:scale-95 disabled:cursor-wait disabled:opacity-60">
                    <span wire:loading.remove wire:target="next,register">{{ $step === 3 ? 'Create Staff Account' : 'Continue' }}</span>
                    <span wire:loading wire:target="next,register" class="inline-flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Saving...
                    </span>
                    <svg wire:loading.remove wire:target="next,register" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </footer>
        </form>

        <!-- Sidebar Summary & Help -->
        <aside class="space-y-4 lg:sticky lg:top-4">
            <!-- Summary Card -->
            <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Account Summary</span>
                
                <div class="mt-4 flex items-center gap-3.5">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-slate-950 text-base font-black text-amber-300 shadow-md">
                        {{ strtoupper(substr($name ?: '?', 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <h3 class="truncate text-sm font-bold text-slate-900">{{ $name ?: 'New Staff Member' }}</h3>
                        <p class="truncate text-xs text-slate-500">{{ $email ?: 'Email pending' }}</p>
                    </div>
                </div>

                <dl class="mt-5 space-y-3 border-t border-slate-100 pt-4 text-xs">
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-500">Job Title</dt>
                        <dd class="truncate font-semibold text-slate-900">{{ $job_title ?: '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-500">Role Type</dt>
                        <dd class="font-semibold capitalize text-slate-900">{{ str_replace('_', ' ', $role) }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-500">Base Salary</dt>
                        <dd class="font-mono font-bold text-slate-900">UGX {{ number_format((float) ($base_salary ?: 0)) }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-500">Teaching Scope</dt>
                        <dd class="font-semibold {{ $has_teaching_duties ? 'text-emerald-600' : 'text-slate-400' }}">
                            {{ $has_teaching_duties ? 'Active' : 'Disabled' }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-500">Class Lead</dt>
                        <dd class="font-semibold {{ $is_class_teacher ? 'text-emerald-600' : 'text-slate-400' }}">
                            {{ $is_class_teacher ? 'Yes' : 'No' }}
                        </dd>
                    </div>
                </dl>
            </section>

            <!-- Onboarding Checklist Box -->
            <section class="rounded-2xl border border-indigo-200/80 bg-indigo-50/60 p-5 text-xs text-indigo-950">
                <h3 class="flex items-center gap-1.5 font-bold">
                    <svg class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Post-Registration Execution
                </h3>
                <ol class="mt-3 list-decimal space-y-2 pl-4 text-[11px] leading-relaxed text-indigo-900/90">
                    <li>Database commit stores system privileges and credentials atomically.</li>
                    <li>Account activation & verification link dispatched to provided email.</li>
                    <li>Dashboard controls dynamically map according to selected permissions.</li>
                    <li>Salary entries automatically register for active payroll processing.</li>
                </ol>
            </section>
        </aside>
    </div>
</div>