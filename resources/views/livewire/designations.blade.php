<div class="space-y-6">
    
    <!-- Top Banner / Header -->
    <header class="relative overflow-hidden rounded-3xl bg-slate-900 p-6 sm:p-8 text-white shadow-xl">
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
               
                <h1 class="text-2xl sm:text-3xl font-extrabold text-amber-300 tracking-tight">
                    Staff Designations
                </h1>
                <p class="mt-1 text-sm text-slate-500 max-w-xl">
                    Define role titles, set module permissions, and standardize access levels across all assigned staff.
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

    @if (session('error'))
        <div class="flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-sm text-rose-900 shadow-sm backdrop-blur-sm">
            <svg class="h-5 w-5 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <p class="font-medium">{{ session('error') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-900 shadow-sm">
            <div class="flex items-center gap-2 font-bold text-rose-800">
                <svg class="h-5 w-5 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                Please resolve the following errors:
            </div>
            <ul class="mt-2 list-disc pl-9 space-y-1 text-rose-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Main Content Layout -->
    <div class="grid gap-8 lg:grid-cols-3 items-start">
        
        <!-- Left Column: Designation Form -->
        <form wire:submit="save" class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-2">
                <h2 class="text-lg font-extrabold text-slate-900">
                    {{ $editingId ? 'Edit Designation' : 'New Designation' }}
                </h2>
                <span class="h-2 w-2 rounded-full bg-amber-400"></span>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Designation Name</label>
                <input wire:model="name" type="text" class="w-full rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-2.5 text-sm text-slate-800 transition-all placeholder:text-slate-400 focus:border-yellow-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30" placeholder="e.g. Senior Teacher, Bursar">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                    Description <span class="font-normal text-slate-400 uppercase">(Optional)</span>
                </label>
                <textarea wire:model="description" rows="3" class="w-full rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-2.5 text-sm text-slate-800 transition-all placeholder:text-slate-400 focus:border-yellow-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30" placeholder="Brief summary of duties or responsibilities..."></textarea>
            </div>

            <div class="pt-2">
                <div class="flex items-center justify-between mb-3">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600">Access Rights</label>
                    <span class="text-[11px] font-semibold text-slate-400">Select modules</span>
                </div>

                <div class="space-y-3">
                    @foreach ($accessGroups as $module => $group)
                        <div class="rounded-2xl border border-slate-200/80 bg-slate-50/50 p-3.5 transition hover:border-slate-300">
                            <p class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 mb-2">
                                {{ $group['label'] }}
                            </p>
                            <div class="grid gap-2">
                                @foreach ($group['rights'] as $key => $label)
                                    <label class="flex items-center gap-2.5 text-xs font-medium text-slate-700 cursor-pointer select-none hover:text-slate-900">
                                        <input wire:model="permissions" type="checkbox" value="{{ $key }}" class="h-4 w-4 shrink-0 rounded border-slate-300 text-yellow-500 transition focus:ring-4 focus:ring-yellow-400/30 focus:ring-offset-0">
                                        <span>{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="pt-2 flex gap-2">
                <button type="submit" wire:loading.attr="disabled" class="flex-1 rounded-xl bg-amber-400 hover:bg-amber-500 text-slate-950 font-bold py-3 text-xs transition shadow-sm hover:shadow active:scale-[0.99] flex items-center justify-center gap-2">
                    <span wire:loading.remove>{{ $editingId ? 'Save Changes' : 'Create Designation' }}</span>
                    <span wire:loading>Saving...</span>
                </button>
                @if ($editingId)
                    <button type="button" wire:click="resetForm" class="rounded-xl border border-slate-200 px-4 py-3 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                        Cancel
                    </button>
                @endif
            </div>
        </form>

        <!-- Right Column: Designations Overview Table -->
        <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm lg:col-span-2">
            <div class="border-b border-slate-100 px-6 py-5 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900">Active Designations</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Existing staff roles and granted system permissions</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-500 tracking-wider">
                        <tr>
                            <th class="px-6 py-3.5">Designation</th>
                            <th class="px-6 py-3.5">Access Rights</th>
                            <th class="px-6 py-3.5">Staff</th>
                            <th class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @forelse ($designations as $designation)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-6 py-4">
                                    <p class="font-bold text-slate-900">{{ $designation->name }}</p>
                                    @if ($designation->description)
                                        <p class="text-xs text-slate-400 font-normal mt-0.5 max-w-xs line-clamp-1" title="{{ $designation->description }}">
                                            {{ $designation->description }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $permissionLabels = collect($designation->permissions)->map(function($item) use ($accessGroups) {
                                            foreach($accessGroups as $group) {
                                                if(isset($group['rights'][$item])) {
                                                    return $group['rights'][$item];
                                                }
                                            }
                                            return $item;
                                        });
                                    @endphp

                                    @if ($permissionLabels->isNotEmpty())
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($permissionLabels as $label)
                                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700">
                                                    {{ $label }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-xs text-slate-400 font-normal italic">No access rights assigned</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 border border-slate-200 px-3 py-1">
                                        <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                        <span class="text-xs font-bold text-slate-800">{{ $designation->users_count }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-3">
                                        <button wire:click="edit({{ $designation->id }})" class="text-xs font-bold text-slate-700 hover:text-amber-600 transition">
                                            Edit
                                        </button>
                                        <button wire:click="delete({{ $designation->id }})" wire:confirm="Remove this designation?" class="text-xs font-bold text-rose-600 hover:text-rose-800 transition">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-400">
                                    <svg class="mx-auto h-8 w-8 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                    No staff designations created yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</div>
