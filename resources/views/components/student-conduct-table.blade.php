@props(['records', 'editable' => false, 'preview' => false])
<div x-data="{ showAllConduct: false }">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-[11px] font-bold uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Date</th><th class="px-5 py-3">What happened</th><th class="px-5 py-3">Reward / penalty</th><th class="px-5 py-3">Recorded by</th><th class="px-5 py-3">Conduct</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($records as $record)
                    <tr wire:key="conduct-{{ $record->id }}" @if($preview) x-show="showAllConduct || {{ $loop->index }} < 5" @endif>
                        <td class="whitespace-nowrap px-5 py-4 align-top text-slate-500">{{ $record->occurred_on->format('d M Y') }}</td>
                        <td class="min-w-56 px-5 py-4 align-top"><p class="whitespace-pre-line break-words text-slate-800">{{ $record->description }}</p>@if($editable)<button type="button" wire:click="edit({{ $record->id }})" class="mt-2 text-xs font-bold text-amber-700 hover:underline">Edit entry</button>@endif</td>
                        <td class="min-w-40 px-5 py-4 align-top"><span class="block text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ $record->kind === 'good' ? 'Reward' : 'Penalty' }}</span><p class="mt-1 whitespace-pre-line break-words text-slate-600">{{ $record->action ?: 'None recorded' }}</p></td>
                        <td class="px-5 py-4 align-top text-xs text-slate-500">{{ $record->recordedBy?->name ?? 'Not recorded' }}@if($record->updated_at?->gt($record->created_at))<span class="mt-1 block text-[10px] text-slate-400">Updated {{ $record->updated_at->format('d M Y') }}</span>@endif</td>
                        <td class="px-5 py-4 align-top"><span class="inline-flex whitespace-nowrap rounded-full px-3 py-1 text-xs font-bold {{ $record->kind === 'good' ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">{{ $record->kind === 'good' ? 'Good conduct' : 'Bad conduct' }}</span></td>
                    </tr>
                @empty<tr><td colspan="5" class="px-5 py-6 text-sm text-slate-400">No conduct entries recorded.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
    @if($preview && $records->count() > 5)<div class="border-t px-5 py-3"><button type="button" @click="showAllConduct = !showAllConduct" class="text-xs font-bold text-amber-700" x-text="showAllConduct ? 'Show recent entries' : 'View full conduct log ({{ $records->count() }})'"></button></div>@endif
</div>
