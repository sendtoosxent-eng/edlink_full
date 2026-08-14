<div class="mx-auto w-full max-w-7xl space-y-6">
    <header class="relative overflow-hidden rounded-3xl bg-slate-950 p-6 text-white shadow-lg sm:p-8">
        <div class="relative z-10 flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
            <div class="max-w-3xl">
                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-amber-400">Staff onboarding</p>
                <h1 class="mt-2 text-2xl font-black tracking-tight text-white sm:text-3xl">Create a complete staff account</h1>
                <p class="mt-2 text-sm leading-relaxed text-slate-300">Set login access, designation, salary and exact teaching responsibilities in one dependable workflow.</p>
            </div>
            <a href="{{ route('staff.index') }}" wire:navigate class="inline-flex w-fit items-center gap-2 rounded-xl border border-white/15 bg-white/10 px-4 py-2.5 text-xs font-bold text-white transition hover:bg-white/15">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Staff directory
            </a>
        </div>
        <div class="pointer-events-none absolute -bottom-24 -right-16 h-72 w-72 rounded-full bg-amber-400/10 blur-3xl"></div>
    </header>

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-900" role="alert">
            <p class="font-black">The staff account has not been saved yet.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <nav aria-label="Registration progress" class="grid gap-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm sm:grid-cols-3">
        @foreach ([1 => ['Account', 'Identity and secure login'], 2 => ['Employment', 'Designation and salary'], 3 => ['Responsibilities', 'Teaching scope and review']] as $number => [$label, $description])
            <div class="flex items-center gap-3 rounded-xl px-3 py-2.5 {{ $step === $number ? 'bg-amber-50 ring-1 ring-amber-300' : ($step > $number ? 'bg-emerald-50' : 'bg-slate-50') }}">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-black {{ $step === $number ? 'bg-amber-400 text-slate-950' : ($step > $number ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-500') }}">{{ $step > $number ? '✓' : $number }}</span>
                <span><strong class="block text-xs text-slate-900">{{ $label }}</strong><small class="text-[10px] text-slate-500">{{ $description }}</small></span>
            </div>
        @endforeach
    </nav>

    <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        <form wire:submit="{{ $step === 3 ? 'register' : 'next' }}" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if ($step === 1)
                <section>
                    <header class="border-b border-slate-100 bg-slate-50 px-5 py-4">
                        <h2 class="font-black text-slate-900">Identity and login</h2>
                        <p class="mt-1 text-xs text-slate-500">This email and temporary password become the staff member's login credentials.</p>
                    </header>
                    <div class="grid gap-4 p-5 sm:grid-cols-2">
                        <label class="sm:col-span-2">
                            <span class="mb-1.5 block text-xs font-bold text-slate-700">Full name <b class="text-rose-500">*</b></span>
                            <input wire:model="name" autocomplete="name" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-amber-400 focus:bg-white focus:ring-amber-400" placeholder="e.g. Sarah Nakato">
                            @error('name') <small class="mt-1 block font-semibold text-rose-600">{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span class="mb-1.5 block text-xs font-bold text-slate-700">Email address <b class="text-rose-500">*</b></span>
                            <input wire:model="email" type="email" autocomplete="email" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-amber-400 focus:bg-white focus:ring-amber-400" placeholder="teacher@school.com">
                            @error('email') <small class="mt-1 block font-semibold text-rose-600">{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span class="mb-1.5 block text-xs font-bold text-slate-700">Phone number</span>
                            <input wire:model="phone" type="tel" autocomplete="tel" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-amber-400 focus:bg-white focus:ring-amber-400" placeholder="+256 700 000000">
                            @error('phone') <small class="mt-1 block font-semibold text-rose-600">{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span class="mb-1.5 block text-xs font-bold text-slate-700">Temporary password <b class="text-rose-500">*</b></span>
                            <input wire:model="password" type="password" autocomplete="new-password" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-amber-400 focus:bg-white focus:ring-amber-400">
                            <small class="mt-1 block text-[10px] text-slate-500">At least 8 characters. The staff member can change it later.</small>
                            @error('password') <small class="mt-1 block font-semibold text-rose-600">{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span class="mb-1.5 block text-xs font-bold text-slate-700">Confirm password <b class="text-rose-500">*</b></span>
                            <input wire:model="password_confirmation" type="password" autocomplete="new-password" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-amber-400 focus:bg-white focus:ring-amber-400">
                        </label>
                    </div>
                </section>
            @elseif ($step === 2)
                <section>
                    <header class="border-b border-slate-100 bg-slate-50 px-5 py-4">
                        <h2 class="font-black text-slate-900">Employment, access and payroll</h2>
                        <p class="mt-1 text-xs text-slate-500">The designation controls general modules; teaching mappings control the learners and academic records visible to this person.</p>
                    </header>
                    <div class="grid gap-4 p-5 sm:grid-cols-2">
                        <label>
                            <span class="mb-1.5 block text-xs font-bold text-slate-700">Account type <b class="text-rose-500">*</b></span>
                            <select wire:model.live="role" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold focus:border-amber-400 focus:ring-amber-400">
                                <option value="teacher">Teacher</option>
                                <option value="bursar">Finance / Bursar</option>
                                <option value="academic_admin">Director of Studies</option>
                                <option value="registrar">Registrar</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </label>
                        <label>
                            <span class="mb-1.5 block text-xs font-bold text-slate-700">Designation @if($role !== 'admin')<b class="text-rose-500">*</b>@endif</span>
                            <select wire:model.live="designation_id" @disabled($role === 'admin') class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm font-semibold focus:border-amber-400 focus:ring-amber-400 disabled:opacity-50">
                                <option value="">{{ $role === 'admin' ? 'Administrator uses unrestricted access' : 'Choose a designation' }}</option>
                                @foreach ($designations as $designation)
                                    <option value="{{ $designation->id }}">{{ $designation->name }}</option>
                                @endforeach
                            </select>
                            <small class="mt-1 block text-[10px] text-slate-500">Standard Bursar, DOS, Subject Teacher and Class Teacher designations are created automatically.</small>
                            @error('designation_id') <small class="mt-1 block font-semibold text-rose-600">{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span class="mb-1.5 block text-xs font-bold text-slate-700">Job title <b class="text-rose-500">*</b></span>
                            <input wire:model="job_title" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-amber-400 focus:ring-amber-400" placeholder="e.g. Senior Mathematics Teacher">
                            @error('job_title') <small class="mt-1 block font-semibold text-rose-600">{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span class="mb-1.5 block text-xs font-bold text-slate-700">Joining date <b class="text-rose-500">*</b></span>
                            <input wire:model="joined_at" type="date" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-amber-400 focus:ring-amber-400">
                            @error('joined_at') <small class="mt-1 block font-semibold text-rose-600">{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span class="mb-1.5 block text-xs font-bold text-slate-700">Monthly base salary <b class="text-rose-500">*</b></span>
                            <div class="relative"><span class="absolute left-3 top-3 text-xs font-black text-slate-400">UGX</span><input wire:model="base_salary" type="number" min="0" step="0.01" class="w-full rounded-xl border-slate-200 bg-slate-50 pl-12 text-sm font-bold focus:border-amber-400 focus:ring-amber-400"></div>
                            @error('base_salary') <small class="mt-1 block font-semibold text-rose-600">{{ $message }}</small> @enderror
                        </label>
                        <label>
                            <span class="mb-1.5 block text-xs font-bold text-slate-700">Contract type</span>
                            <select wire:model="contract_type" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-amber-400 focus:ring-amber-400">
                                <option value="permanent">Permanent</option><option value="contract">Contract</option><option value="part_time">Part-time</option><option value="volunteer">Volunteer</option>
                            </select>
                        </label>
                        <label>
                            <span class="mb-1.5 block text-xs font-bold text-slate-700">Employment status</span>
                            <select wire:model="employment_status" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-amber-400 focus:ring-amber-400"><option value="active">Active</option><option value="inactive">Inactive</option></select>
                        </label>
                        <label>
                            <span class="mb-1.5 block text-xs font-bold text-slate-700">Probation ends</span>
                            <input wire:model="probation_ends_at" type="date" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-amber-400 focus:ring-amber-400">
                            @error('probation_ends_at') <small class="mt-1 block font-semibold text-rose-600">{{ $message }}</small> @enderror
                        </label>

                        @if ($role !== 'teacher' && $role !== 'admin')
                            <label class="sm:col-span-2 flex cursor-pointer items-start gap-3 rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                                <input wire:model.live="has_teaching_duties" type="checkbox" class="mt-0.5 rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500">
                                <span><strong class="block text-xs text-indigo-950">This staff member also teaches</strong><small class="mt-1 block text-[10px] text-indigo-800">Useful when a DOS, bursar or registrar also teaches particular subjects.</small></span>
                            </label>
                        @endif

                        @if ($role === 'admin')
                            <label class="sm:col-span-2 flex cursor-pointer items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4">
                                <input wire:model="admin_confirmation" type="checkbox" class="mt-0.5 rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                                <span><strong class="block text-xs text-amber-950">Confirm unrestricted administrator access</strong><small class="mt-1 block text-[10px] text-amber-800">Administrators can access every school module and manage other users.</small></span>
                            </label>
                            @error('admin_confirmation') <small class="sm:col-span-2 font-semibold text-rose-600">{{ $message }}</small> @enderror
                        @endif
                    </div>
                </section>
            @else
                <section>
                    <header class="border-b border-slate-100 bg-slate-50 px-5 py-4">
                        <h2 class="font-black text-slate-900">Responsibilities and final review</h2>
                        <p class="mt-1 text-xs text-slate-500">Class ownership and subject teaching are separate, so one teacher can own one class and teach in several others.</p>
                    </header>
                    <div class="space-y-5 p-5">
                        @if ($has_teaching_duties)
                            @if (! $currentTerm)
                                <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-900"><strong>No current term is open.</strong> Create or select the current term before registering teaching staff.</div>
                            @else
                                <div class="flex flex-col gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div><strong class="block text-xs text-emerald-950">Assignments for {{ $currentTerm->name }}, {{ $currentTerm->year }}</strong><small class="text-[10px] text-emerald-800">Mappings are term-specific and immediately control the teacher dashboard, marks, homework and attendance.</small></div>
                                    <span class="w-fit rounded-full bg-emerald-600 px-3 py-1 text-[10px] font-black text-white">CURRENT TERM</span>
                                </div>
                            @endif

                            <div class="rounded-2xl border border-slate-200 p-4">
                                <label class="flex cursor-pointer items-start gap-3">
                                    <input wire:model.live="is_class_teacher" type="checkbox" class="mt-0.5 rounded border-slate-300 text-amber-500 focus:ring-amber-400">
                                    <span><strong class="block text-xs text-slate-900">Also assign as a class teacher</strong><small class="mt-1 block text-[10px] text-slate-500">This grants daily attendance and a student directory limited to that class.</small></span>
                                </label>
                                @if ($is_class_teacher)
                                    <label class="mt-4 block">
                                        <span class="mb-1.5 block text-xs font-bold text-slate-700">Class teacher for <b class="text-rose-500">*</b></span>
                                        <select wire:model="class_teacher_class_id" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-amber-400 focus:ring-amber-400">
                                            <option value="">Choose an available class</option>
                                            @foreach ($classes as $class)
                                                <option value="{{ $class->id }}" @disabled($class->class_teacher_user_id)>{{ $class->name }}{{ $class->classTeacher ? ' — already assigned to '.$class->classTeacher->name : '' }}</option>
                                            @endforeach
                                        </select>
                                        @error('class_teacher_class_id') <small class="mt-1 block font-semibold text-rose-600">{{ $message }}</small> @enderror
                                    </label>
                                @endif
                            </div>

                            <div class="rounded-2xl border border-slate-200 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div><h3 class="text-xs font-black text-slate-900">Subjects taught by class</h3><p class="mt-1 text-[10px] text-slate-500">Add a row for each class, then choose one or more subjects from the dropdown.</p></div>
                                    <button type="button" wire:click="addTeachingAssignment" class="w-fit rounded-lg bg-slate-900 px-3 py-2 text-[10px] font-bold text-white hover:bg-slate-800">+ Add another class</button>
                                </div>
                                <div class="mt-4 space-y-3">
                                    @foreach ($teaching_assignments as $index => $assignment)
                                        <div wire:key="teaching-assignment-{{ $index }}" class="grid gap-3 rounded-xl bg-slate-50 p-3 sm:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)_auto] sm:items-end">
                                            <label>
                                                <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Class</span>
                                                <select wire:model.live="teaching_assignments.{{ $index }}.class_id" class="w-full rounded-lg border-slate-200 bg-white text-xs focus:border-amber-400 focus:ring-amber-400">
                                                    <option value="">Choose class</option>
                                                    @foreach ($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach
                                                </select>
                                            </label>
                                            <label>
                                                <span class="mb-1 block text-[10px] font-bold uppercase tracking-wide text-slate-500">Subjects (choose one or more)</span>
                                                <select wire:model="teaching_assignments.{{ $index }}.subject_ids" multiple size="{{ min(4, max(2, $subjects->count())) }}" class="w-full rounded-lg border-slate-200 bg-white text-xs focus:border-amber-400 focus:ring-amber-400">
                                                    @foreach ($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->name }}{{ $subject->code ? ' ('.$subject->code.')' : '' }}</option>@endforeach
                                                </select>
                                            </label>
                                            <button type="button" wire:click="removeTeachingAssignment({{ $index }})" class="rounded-lg border border-rose-200 bg-white px-3 py-2 text-[10px] font-bold text-rose-700 hover:bg-rose-50">Remove</button>
                                        </div>
                                    @endforeach
                                </div>
                                @if ($classes->isEmpty() || $subjects->isEmpty())
                                    <p class="mt-3 rounded-lg bg-amber-50 p-3 text-[10px] font-semibold text-amber-900">Create at least one class and one subject before registering teaching staff.</p>
                                @endif
                                @error('teaching_assignments') <small class="mt-2 block font-semibold text-rose-600">{{ $message }}</small> @enderror
                            </div>
                        @else
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-700">No teaching duties selected. This staff member will see only the modules granted by the chosen designation.</div>
                        @endif

                        <div class="grid gap-4 rounded-2xl border border-slate-200 p-4 sm:grid-cols-2">
                            <div class="sm:col-span-2"><h3 class="text-xs font-black text-slate-900">Optional HR and payment details</h3><p class="mt-1 text-[10px] text-slate-500">These details can also be completed later from the staff directory.</p></div>
                            <label><span class="mb-1 block text-[10px] font-bold text-slate-600">Emergency contact name</span><input wire:model="emergency_contact_name" class="w-full rounded-lg border-slate-200 bg-slate-50 text-xs"></label>
                            <label><span class="mb-1 block text-[10px] font-bold text-slate-600">Emergency contact phone</span><input wire:model="emergency_contact_phone" class="w-full rounded-lg border-slate-200 bg-slate-50 text-xs"></label>
                            <label><span class="mb-1 block text-[10px] font-bold text-slate-600">National ID</span><input wire:model="national_id" class="w-full rounded-lg border-slate-200 bg-slate-50 text-xs"></label>
                            <label><span class="mb-1 block text-[10px] font-bold text-slate-600">Bank name</span><input wire:model="bank_name" class="w-full rounded-lg border-slate-200 bg-slate-50 text-xs"></label>
                            <label><span class="mb-1 block text-[10px] font-bold text-slate-600">Bank account name</span><input wire:model="bank_account_name" class="w-full rounded-lg border-slate-200 bg-slate-50 text-xs"></label>
                            <label><span class="mb-1 block text-[10px] font-bold text-slate-600">Bank account number</span><input wire:model="bank_account_number" class="w-full rounded-lg border-slate-200 bg-slate-50 text-xs"></label>
                        </div>
                    </div>
                </section>
            @endif

            <footer class="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-5 py-4">
                @if ($step > 1)
                    <button type="button" wire:click="back" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-100">Back</button>
                @else
                    <span></span>
                @endif
                <button type="submit" wire:loading.attr="disabled" wire:target="next,register" class="inline-flex items-center gap-2 rounded-xl bg-amber-400 px-5 py-2.5 text-xs font-black text-slate-950 shadow-sm hover:bg-amber-300 disabled:cursor-wait disabled:opacity-60">
                    <span wire:loading.remove wire:target="next,register">{{ $step === 3 ? 'Create staff account' : 'Continue' }}</span>
                    <span wire:loading wire:target="next,register">Saving...</span>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </footer>
        </form>

        <aside class="space-y-4 lg:sticky lg:top-4">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-400">Account preview</p>
                <div class="mt-4 flex items-center gap-3">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-900 text-lg font-black text-amber-300">{{ strtoupper(substr($name ?: '?', 0, 1)) }}</span>
                    <div class="min-w-0"><h3 class="truncate text-sm font-black text-slate-900">{{ $name ?: 'New staff member' }}</h3><p class="truncate text-xs text-slate-500">{{ $email ?: 'Email not entered' }}</p></div>
                </div>
                <dl class="mt-5 space-y-3 border-t border-slate-100 pt-4 text-xs">
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Job title</dt><dd class="text-right font-bold text-slate-900">{{ $job_title ?: '—' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Account</dt><dd class="font-bold capitalize text-slate-900">{{ str_replace('_', ' ', $role) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Salary</dt><dd class="font-mono font-bold text-slate-900">UGX {{ number_format((float) ($base_salary ?: 0)) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Teaching duties</dt><dd class="font-bold {{ $has_teaching_duties ? 'text-emerald-700' : 'text-slate-500' }}">{{ $has_teaching_duties ? 'Yes' : 'No' }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-slate-500">Class teacher</dt><dd class="font-bold {{ $is_class_teacher ? 'text-emerald-700' : 'text-slate-500' }}">{{ $is_class_teacher ? 'Yes' : 'No' }}</dd></div>
                </dl>
            </section>
            <section class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5 text-xs text-indigo-950">
                <h3 class="font-black">What happens after saving?</h3>
                <ol class="mt-3 list-decimal space-y-2 pl-4 text-[11px] leading-relaxed text-indigo-900">
                    <li>The account and all mappings are committed together.</li>
                    <li>A verification link is sent immediately by email.</li>
                    <li>The teacher dashboard shows only mapped classes and subjects.</li>
                    <li>Salary is available to the payroll module.</li>
                </ol>
            </section>
        </aside>
    </div>
</div>
