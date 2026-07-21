<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="text-sm font-semibold text-yellow-600">Academics</p><h1 class="text-2xl font-bold text-slate-900">Grading scales</h1><p class="mt-1 text-sm text-slate-500">Define the grade assigned to every examination percentage.</p></div>
        <div class="rounded-xl border bg-white px-4 py-3 text-sm"><span class="text-slate-500">Coverage</span><strong class="ml-2 {{ $coverageComplete ? 'text-emerald-600' : 'text-amber-600' }}">{{ $coverageComplete ? '0-100% complete' : 'Needs review' }}</strong></div>
    </div>

    @if (session('status'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">{{ session('status') }}</div>@endif
    @if (! $coverageComplete && $scales->isNotEmpty())<div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"><strong>Incomplete coverage:</strong> some scores between 0 and 100 may display without a grade. Adjust the bands until coverage is complete.</div>@endif

    <div class="grid gap-6 lg:grid-cols-3">
        <form wire:submit="save" class="space-y-5 rounded-2xl border bg-white p-6 shadow-sm">
            <div><h2 class="font-bold text-slate-900">{{ $editingId ? 'Edit grade band' : 'Add grade band' }}</h2><p class="mt-1 text-xs text-slate-500">Bands cannot overlap and must remain between 0 and 100.</p></div>
            <div><label class="mb-1.5 block text-sm font-semibold">Grade</label><input wire:model="grade" maxlength="10" placeholder="e.g. A" class="w-full rounded-xl border-slate-300 uppercase focus:border-yellow-400 focus:ring-yellow-400">@error('grade')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="mb-1.5 block text-sm font-semibold">Minimum %</label><input wire:model="minimum" type="number" min="0" max="100" step="0.01" placeholder="80" class="w-full rounded-xl border-slate-300 focus:border-yellow-400 focus:ring-yellow-400">@error('minimum')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
                <div><label class="mb-1.5 block text-sm font-semibold">Maximum %</label><input wire:model="maximum" type="number" min="0" max="100" step="0.01" placeholder="100" class="w-full rounded-xl border-slate-300 focus:border-yellow-400 focus:ring-yellow-400">@error('maximum')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
            </div>
            <div><label class="mb-1.5 block text-sm font-semibold">Remark</label><input wire:model="remark" maxlength="255" placeholder="e.g. Excellent" class="w-full rounded-xl border-slate-300 focus:border-yellow-400 focus:ring-yellow-400">@error('remark')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
            <div class="flex flex-wrap gap-2"><button class="rounded-xl bg-yellow-400 px-5 py-2.5 text-sm font-bold">{{ $editingId ? 'Update band' : 'Add band' }}</button>@if ($editingId)<button type="button" wire:click="cancelEditing" class="rounded-xl border bg-white px-5 py-2.5 text-sm font-bold">Cancel</button>@endif</div>
        </form>

        <div class="overflow-hidden rounded-2xl border bg-white shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between gap-3 border-b px-5 py-4"><div><h2 class="font-bold">Grade bands</h2><p class="text-xs text-slate-500">Highest percentages are evaluated first.</p></div>@if ($scales->isEmpty())<button wire:click="installDefaults" class="rounded-xl border border-yellow-300 bg-yellow-50 px-4 py-2 text-sm font-bold text-yellow-800">Install default A-F scale</button>@endif</div>
            <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-4">Grade</th><th class="px-5 py-4">Percentage range</th><th class="px-5 py-4">Remark</th><th class="px-5 py-4 text-right">Actions</th></tr></thead><tbody>
                @forelse ($scales as $scale)
                    <tr class="border-t"><td class="px-5 py-4"><span class="inline-flex min-w-10 justify-center rounded-lg bg-slate-900 px-2.5 py-1.5 font-bold text-white">{{ $scale->grade }}</span></td><td class="px-5 py-4 font-semibold">{{ number_format($scale->minimum_percentage, 2) }} - {{ number_format($scale->maximum_percentage, 2) }}%</td><td class="px-5 py-4 text-slate-500">{{ $scale->remark ?: '—' }}</td><td class="px-5 py-4 text-right"><div class="flex justify-end gap-3"><button wire:click="edit({{ $scale->id }})" class="font-semibold text-slate-700">Edit</button><button wire:click="delete({{ $scale->id }})" wire:confirm="Delete grade {{ $scale->grade }}?" class="font-semibold text-rose-600">Delete</button></div></td></tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-12 text-center"><p class="font-semibold text-slate-700">No grade bands configured</p><p class="mt-1 text-sm text-slate-500">Add your own bands or install the editable default scale.</p></td></tr>
                @endforelse
            </tbody></table></div>
        </div>
    </div>
</div>
