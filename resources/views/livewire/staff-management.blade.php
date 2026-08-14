<div class="space-y-6">
    <header class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-950 via-slate-900 to-slate-800 p-6 text-white shadow-sm sm:p-8">
        <div class="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-2xl">
                <h1 class="mt-2 text-2xl font-extrabold tracking-tight text-amber-300 sm:text-3xl">All Staff</h1>
                <p class="mt-2 text-sm leading-relaxed text-slate-300">Manage staff profiles, employment status, payroll details and designation-based access.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <div class="rounded-xl border border-amber-400/20 bg-amber-400/10 px-4 py-2.5">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-amber-300">Staff shown</p>
                    <p class="text-lg font-black text-white">{{ $staff->count() }}</p>
                </div>
                <button wire:click="$toggle('showInactive')" class="rounded-xl border border-white/15 bg-white/10 px-4 py-3 text-xs font-bold text-white transition hover:bg-white/15">
                    {{ $showInactive ? 'Show active only' : 'Show all staff' }}
                </button>
                <a wire:navigate href="{{ route('designations.index') }}" class="rounded-xl bg-amber-400 px-4 py-3 text-xs font-black text-slate-950 transition hover:bg-amber-300">Manage designations</a>
            </div>
        </div>
        <div class="pointer-events-none absolute -right-16 -bottom-20 h-72 w-72 rounded-full bg-amber-400/10 blur-3xl"></div>
    </header>

    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-700">{{ session('status') }}</div>
    @endif
    @if(session('error') || $errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            @if(session('error'))<div>{{ session('error') }}</div>@endif
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="bg-slate-900 px-5 py-4">
                <h2 class="font-black text-amber-300">Register staff member</h2>
                <p class="mt-0.5 text-xs text-slate-400">Use the guided workflow so salary, access, classes and subjects are captured together.</p>
            </div>
            <div class="space-y-4 p-5">
                <div class="rounded-xl bg-amber-50 p-4 text-sm leading-relaxed text-amber-900">Every new account must have its employment details, designation and teaching assignments reviewed before it is saved.</div>
                <a href="{{ route('staff.register') }}" wire:navigate class="block w-full rounded-xl bg-amber-400 py-3 text-center text-sm font-black text-slate-950 transition hover:bg-amber-300">Start guided registration</a>
                <a href="{{ route('designations.index') }}" wire:navigate class="block text-center text-xs font-bold text-amber-700 hover:underline">Manage designations first</a>
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-800 bg-slate-900 px-5 py-4">
                <div>
                    <h2 class="font-black text-amber-300">Staff Directory</h2>
                    <p class="mt-0.5 text-xs text-slate-400">Accounts and employment records in this school.</p>
                </div>
                <span class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1 text-xs font-bold text-slate-300">{{ $staff->count() }} records</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr><th class="px-5 py-3">Staff</th><th class="px-5 py-3">Role & designation</th><th class="px-5 py-3">Salary</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($staff as $member)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-5 py-3">
                                    <p class="font-bold text-slate-800">{{ $member->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $member->staff_number }} · {{ $member->email }}</p>
                                </td>
                                <td class="min-w-48 px-5 py-3 text-slate-600">
                                    {{ $member->job_title }}<p class="text-xs text-slate-400">{{ ucfirst($member->role) }}</p>
                                    <select wire:change="assignDesignation({{ $member->id }}, $event.target.value)" class="mt-1 w-full rounded-lg border-slate-200 text-xs focus:border-amber-400 focus:ring-amber-400">
                                        <option value="">Select designation</option>
                                        @foreach($designations as $designation)<option value="{{ $designation->id }}" @selected($member->designation_id === $designation->id)>{{ $designation->name }}</option>@endforeach
                                    </select>
                                </td>
                                <td class="whitespace-nowrap px-5 py-3 font-mono text-slate-700">UGX {{ number_format($member->base_salary) }}</td>
                                <td class="px-5 py-3"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $member->employment_status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ ucfirst($member->employment_status) }}</span></td>
                                <td class="whitespace-nowrap px-5 py-3 text-right">
                                    <button wire:click="edit({{ $member->id }})" class="mr-3 text-xs font-bold text-amber-700">Edit</button>
                                    @if(! $member->hasVerifiedEmail())
                                        <button wire:click="resendVerification({{ $member->id }})" wire:loading.attr="disabled" class="mr-3 text-xs font-bold text-indigo-700">Resend verification</button>
                                    @endif
                                    <button wire:click="toggleStatus({{ $member->id }})" class="text-xs font-bold text-slate-600 hover:text-amber-600">{{ $member->employment_status === 'active' ? 'Deactivate' : 'Activate' }}</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">No staff members found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    @if($editingId)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4" wire:click.self="cancelEdit">
            <form wire:submit="updateStaff" class="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between bg-slate-900 p-6 text-white">
                    <div><p class="text-xs font-black uppercase tracking-wider text-amber-300">Staff profile</p><h2 class="mt-1 text-xl font-bold">Edit staff member</h2><p class="text-sm text-slate-400">Update account, employment and access information.</p></div>
                    <button type="button" wire:click="cancelEdit" class="rounded-lg px-3 py-1 text-xl text-slate-300 hover:bg-white/10">&times;</button>
                </div>
                @if($errors->any())<div class="m-6 mb-0 rounded-xl bg-rose-50 p-3 text-sm text-rose-700">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
                <div class="grid gap-4 p-6 md:grid-cols-2">
                    <label class="text-sm font-semibold">Full name<input wire:model="editName" class="mt-1 w-full rounded-xl border-slate-200 focus:border-amber-400 focus:ring-amber-400"></label>
                    <label class="text-sm font-semibold">Email<input wire:model="editEmail" type="email" class="mt-1 w-full rounded-xl border-slate-200 focus:border-amber-400 focus:ring-amber-400"></label>
                    <label class="text-sm font-semibold">Phone<input wire:model="editPhone" class="mt-1 w-full rounded-xl border-slate-200 focus:border-amber-400 focus:ring-amber-400"></label>
                    <label class="text-sm font-semibold">Job title<input wire:model="editJobTitle" class="mt-1 w-full rounded-xl border-slate-200 focus:border-amber-400 focus:ring-amber-400"></label>
                    <label class="text-sm font-semibold">Account role<select wire:model.live="editRole" class="mt-1 w-full rounded-xl border-slate-200 focus:border-amber-400 focus:ring-amber-400"><option value="teacher">Teacher</option><option value="bursar">Bursar</option><option value="registrar">Registrar</option><option value="academic_admin">Academic administrator</option><option value="admin">Administrator</option></select></label>
                    <label class="text-sm font-semibold">Designation<select wire:model="editDesignationId" class="mt-1 w-full rounded-xl border-slate-200 focus:border-amber-400 focus:ring-amber-400"><option value="">No designation</option>@foreach($designations as $designation)<option value="{{ $designation->id }}">{{ $designation->name }}</option>@endforeach</select></label>
                    @if(in_array($editRole, ['teacher', 'academic_admin'], true))
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 md:col-span-2">
                            <p class="text-xs font-bold text-slate-800">Teaching assignments{{ $currentTerm ? ' · '.$currentTerm->name : '' }}</p>
                            <select wire:model="editClassTeacherClassId" class="mt-2 w-full rounded-lg border-slate-200 text-sm"><option value="">No class-teacher assignment</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select>
                            <div class="mt-2 grid max-h-36 gap-1 overflow-y-auto rounded-lg bg-white p-2 sm:grid-cols-2">@foreach($classes as $class)@foreach($subjects as $subject)<label class="flex items-center gap-2 text-[10px] text-slate-700"><input type="checkbox" wire:model="editSubjectAssignments" value="{{ $class->id }}:{{ $subject->id }}" class="rounded border-slate-300 text-yellow-500">{{ $class->name }} · {{ $subject->name }}</label>@endforeach @endforeach</div>
                            @error('editClassTeacherClassId')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror @error('editSubjectAssignments')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    @endif
                    <label class="text-sm font-semibold">Monthly salary (UGX)<input wire:model="editBaseSalary" type="number" min="0" class="mt-1 w-full rounded-xl border-slate-200 focus:border-amber-400 focus:ring-amber-400"></label>
                    <label class="text-sm font-semibold">Joined date<input wire:model="editJoinedAt" type="date" class="mt-1 w-full rounded-xl border-slate-200 focus:border-amber-400 focus:ring-amber-400"></label>
                    <label class="text-sm font-semibold">Employment status<select wire:model="editEmploymentStatus" class="mt-1 w-full rounded-xl border-slate-200 focus:border-amber-400 focus:ring-amber-400"><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
                    <label class="text-sm font-semibold">Replace profile photo<input wire:model="editPhoto" type="file" accept="image/png,image/jpeg,image/webp" class="mt-1 block w-full rounded-xl border border-slate-200 p-2 text-sm"></label>
                    <label class="text-sm font-semibold md:col-span-2">New password <span class="font-normal text-slate-400">(leave blank to keep it)</span><input wire:model="editPassword" type="password" class="mt-1 w-full rounded-xl border-slate-200 focus:border-amber-400 focus:ring-amber-400"></label>
                </div>
                <div class="flex justify-end gap-3 border-t bg-slate-50 px-6 py-4"><button type="button" wire:click="cancelEdit" class="rounded-xl border px-4 py-2 text-sm font-bold">Cancel</button><button class="rounded-xl bg-amber-400 px-5 py-2 text-sm font-black text-slate-950 hover:bg-amber-300">Save profile</button></div>
            </form>
        </div>
    @endif
</div>
