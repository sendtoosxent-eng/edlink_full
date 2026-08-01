<div class="w-full max-w-none mx-auto">

    <!-- Top Banner / Header -->
    <header class="relative overflow-hidden rounded-3xl bg-slate-900 p-6 sm:p-8 text-white shadow-xl">
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                
                <h1 class="text-2xl sm:text-3xl font-extrabold text-amber-300 tracking-tight">
                    Register Guardian
                </h1>
                <p class="mt-1 text-sm text-slate-500 max-w-xl">
                    Create a parent account credential and link it to one or more enrolled learners.
                </p>
            </div>
        </div>

        <!-- Ambient background glow -->
        <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-amber-400/10 blur-3xl pointer-events-none"></div>
    </header>

    <!-- Alert Messages -->
    @if (session('status'))
        <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 text-sm text-emerald-900 shadow-sm backdrop-blur-sm">
            <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <p class="font-medium">{{ session('status') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900 shadow-sm">
            <div class="flex items-center gap-2 font-bold text-rose-800">
                <svg class="h-5 w-5 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                Please correct the following errors:
            </div>
            <ul class="mt-2 list-disc pl-9 space-y-1 text-rose-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Main Registration Form -->
    <form wire:submit="save" class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-sm space-y-6">
        
        <div class="border-b border-slate-100 pb-4">
            <h2 class="text-lg font-extrabold text-slate-900">Personal Information</h2>
            <p class="text-xs text-slate-500 mt-0.5">Enter contact details for the parent or primary guardian</p>
        </div>

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Full Name</label>
                <input wire:model="name" type="text" placeholder="e.g. Jane Doe" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:bg-white focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400 focus:outline-none transition">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Relationship</label>
                <select wire:model="relationship" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:bg-white focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400 focus:outline-none transition">
                    <option value="">Select relationship</option>
                    <option value="Parent">Parent</option>
                    <option value="Mother">Mother</option>
                    <option value="Father">Father</option>
                    <option value="Guardian">Guardian</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Email / Login Username</label>
                <input wire:model="email" type="email" placeholder="parent@example.com" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:bg-white focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400 focus:outline-none transition">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Phone Number</label>
                <input wire:model="phone" type="text" placeholder="e.g. +256 700 000 000" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:bg-white focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400 focus:outline-none transition">
            </div>

            <div class="md:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Temporary Password</label>
                <input wire:model="password" type="password" placeholder="••••••••" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:bg-white focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400 focus:outline-none transition">
                <p class="mt-1 text-xs text-slate-400 font-medium">Must be at least 8 characters. Share securely with the parent.</p>
            </div>
        </div>

        <!-- Student Link Section -->
        <fieldset class="pt-4 border-t border-slate-100">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <legend class="text-sm font-extrabold text-slate-900">Link Learners</legend>
                    <p class="text-xs text-slate-500 mt-0.5">Select all students associated with this guardian</p>
                </div>
                <span class="text-xs font-bold text-amber-800 bg-amber-100/80 px-2.5 py-0.5 rounded-full">
                    {{ count($studentIds ?? []) }} selected
                </span>
            </div>

            <div class="grid max-h-72 gap-2.5 overflow-y-auto p-1 md:grid-cols-2">
                @forelse ($students as $student)
                    <label class="flex items-start gap-3 rounded-2xl border border-slate-200/80 bg-slate-50/50 p-3.5 transition hover:bg-slate-100/60 hover:border-slate-300 cursor-pointer select-none">
                        <input wire:model="studentIds" type="checkbox" value="{{ $student->id }}" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-yellow-500 focus:ring-2 focus:ring-yellow-400 focus:ring-offset-0 transition">
                        <div class="text-xs">
                            <span class="font-bold text-slate-900 block text-sm">{{ $student->name }}</span>
                            <span class="text-slate-500 font-medium mt-0.5 block">
                                {{ $student->admission_no }} · <span class="text-slate-700 font-semibold">{{ $student->schoolClass?->name ?? 'N/A' }}</span>
                            </span>
                        </div>
                    </label>
                @empty
                    <div class="md:col-span-2 rounded-2xl border border-dashed border-slate-200 p-6 text-center text-xs text-slate-400">
                        No active students found in the system.
                    </div>
                @endforelse
            </div>
        </fieldset>

        <div class="pt-2 flex justify-end">
            <button type="submit" wire:loading.attr="disabled" class="w-full sm:w-auto rounded-xl bg-amber-400 hover:bg-amber-500 text-slate-950 font-bold px-8 py-3 text-sm transition shadow-sm hover:shadow active:scale-[0.99] flex items-center justify-center gap-2">
                <span wire:loading.remove>Register Parent</span>
                <span wire:loading class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-slate-950" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Registering...
                </span>
            </button>
        </div>
    </form>

</div>