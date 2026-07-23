<div class="space-y-6">
    <!-- Header Block with Dark Gradient Background & Ambient Glow -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-sm">
        <div class="relative z-10 flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
            <div class="max-w-2xl">
                
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-amber-300">
                    Grading Scales
                </h1>
                <p class="text-sm font-medium text-slate-400 mt-1.5 leading-relaxed">
                    Define the grade assigned to every examination percentage. Configure score bands, aggregate points, and remarks by education stage.
                </p>
            </div>

            <!-- Education Stage Selector & Coverage Badge Header Controls -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 shrink-0">
                <div class="relative w-full sm:w-auto">
                    <select wire:model.live="stage" 
                            class="w-full text-xs font-bold bg-slate-800/90 border border-slate-700 rounded-xl pl-3.5 pr-9 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs appearance-none cursor-pointer">
                        @foreach($stages as $option)
                            <option value="{{$option}}" class="bg-slate-900 text-white">{{ str($option)->replace('_',' ')->title() }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>

                <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-800/80 border border-slate-700/80 text-xs font-bold shadow-2xs">
                    <span class="text-slate-400">Coverage:</span>
                    <span class="{{ $coverageComplete ? 'text-emerald-400' : 'text-amber-400' }}">
                        {{ $coverageComplete ? '0-100% Complete' : 'Needs Review' }}
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Decorative Ambient Glow -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Feedback Alerts -->
    @if (session('status'))
        <div class="flex items-center justify-between gap-3 bg-emerald-50 border border-emerald-200/80 text-emerald-900 text-sm rounded-2xl p-4 shadow-xs">
            <div class="flex items-center gap-3">
                <div class="p-1 bg-emerald-100 rounded-lg shrink-0">
                    <svg class="w-5 h-5 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="font-semibold">{{ session('status') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    @endif

    @if (! $coverageComplete && $scales->isNotEmpty())
        <div class="flex items-center justify-between gap-3 bg-amber-50 border border-amber-200/80 text-amber-900 text-sm rounded-2xl p-4 shadow-xs">
            <div class="flex items-center gap-3">
                <div class="p-1 bg-amber-100 rounded-lg shrink-0">
                    <svg class="w-5 h-5 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="font-semibold"><strong>Incomplete Coverage:</strong> Some scores between 0% and 100% may display without a grade band. Adjust boundaries until scale coverage is unbroken.</span>
            </div>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-3 items-start">
        <!-- Form Section -->
        <form wire:submit="save" class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="font-bold text-slate-900 text-base">{{ $editingId ? 'Edit Grade Band' : 'Add Grade Band' }}</h2>
                <p class="text-xs font-medium text-slate-500 mt-0.5">Bands cannot overlap and must remain between 0 and 100.</p>
            </div>

            <!-- Grade Code Input -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Grade Symbol</label>
                <input wire:model="grade" maxlength="10" placeholder="e.g. A" 
                       class="w-full text-sm font-bold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 uppercase focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs placeholder:text-slate-400 placeholder:font-normal">
                @error('grade')
                    <span class="text-rose-600 text-xs font-medium mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                    </span>
                @enderror
            </div>

            <!-- Min/Max Ranges -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Minimum %</label>
                    <input wire:model="minimum" type="number" min="0" max="100" step="0.01" placeholder="80" 
                           class="w-full text-sm font-semibold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs placeholder:text-slate-400 placeholder:font-normal">
                    @error('minimum')
                        <span class="text-rose-600 text-xs font-medium mt-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                        </span>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Maximum %</label>
                    <input wire:model="maximum" type="number" min="0" max="100" step="0.01" placeholder="100" 
                           class="w-full text-sm font-semibold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs placeholder:text-slate-400 placeholder:font-normal">
                    @error('maximum')
                        <span class="text-rose-600 text-xs font-medium mt-1 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                        </span>
                    @enderror
                </div>
            </div>

            <!-- Remark Input -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Descriptor / Remark</label>
                <input wire:model="remark" maxlength="255" placeholder="e.g. Excellent" 
                       class="w-full text-sm font-medium bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs placeholder:text-slate-400 placeholder:font-normal">
                @error('remark')
                    <span class="text-rose-600 text-xs font-medium mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                    </span>
                @enderror
            </div>

            <!-- Aggregate Points -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Aggregate Points</label>
                <input wire:model="points" type="number" min="0" max="20" placeholder="1"
                       class="w-full text-sm font-semibold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs placeholder:text-slate-400 placeholder:font-normal">
                <p class="text-[11px] font-medium text-slate-400 mt-1">Optional numerical weight for stages using aggregates.</p>
                @error('points')
                    <span class="text-rose-600 text-xs font-medium mt-1 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                    </span>
                @enderror
            </div>

            <!-- Form Action Buttons -->
            <div class="pt-2 flex flex-wrap gap-2">
                <button type="submit" 
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-400 hover:bg-amber-300 active:scale-95 text-slate-950 font-extrabold px-5 py-2.5 text-sm shadow-md hover:shadow-lg transition cursor-pointer">
                    <span>{{ $editingId ? 'Update Band' : 'Save Band' }}</span>
                </button>

                @if ($editingId)
                    <button type="button" wire:click="cancelEditing" 
                            class="inline-flex items-center justify-center rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold px-4 py-2.5 text-sm shadow-2xs transition cursor-pointer">
                        Cancel
                    </button>
                @endif
            </div>
        </form>

        <!-- Grade Bands Table Matrix -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden lg:col-span-2">
            <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <h2 class="font-bold text-slate-900 text-base">Grade Band Structure</h2>
                    <p class="text-xs font-medium text-slate-500 mt-0.5">Highest percentages are evaluated sequentially first.</p>
                </div>
                
                @if ($scales->isEmpty())
                    <button wire:click="installDefaults" 
                            class="inline-flex items-center gap-1.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 hover:bg-amber-100 px-3.5 py-2 text-xs font-bold transition shadow-2xs cursor-pointer shrink-0">
                        <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        <span>Install Default Scale</span>
                    </button>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-900 text-white font-bold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="px-5 py-3.5">Grade</th>
                            <th class="px-5 py-3.5">Percentage Range</th>
                            <th class="px-5 py-3.5">Remark</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @forelse ($scales as $scale)
                            <tr class="hover:bg-slate-50/80 transition-colors group">
                                <!-- Grade Badge -->
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center justify-center min-w-9 h-9 px-2.5 rounded-xl bg-slate-900 text-white font-black text-xs shadow-2xs">
                                        {{ $scale->grade }}
                                    </span>
                                </td>

                                <!-- Range Display -->
                                <td class="px-5 py-3.5">
                                    <span class="font-mono font-extrabold text-slate-900">
                                        {{ number_format($scale->minimum_percentage, 2) }}% – {{ number_format($scale->maximum_percentage, 2) }}%
                                    </span>
                                </td>

                                <!-- Remark & Points -->
                                <td class="px-5 py-3.5">
                                    <div class="font-semibold text-slate-800">{{ $scale->remark ?: '—' }}</div>
                                    @if(isset($scale->points))
                                        <div class="text-[11px] font-bold text-slate-400">Pts: {{ $scale->points }}</div>
                                    @endif
                                </td>

                                <!-- Action Buttons -->
                                <td class="px-5 py-3.5 text-right">
                                    <div class="inline-flex items-center justify-end gap-2">
                                        <button wire:click="edit({{ $scale->id }})" 
                                                title="Edit Band" 
                                                class="p-2 rounded-xl text-slate-400 hover:text-slate-800 hover:bg-slate-100 transition-colors cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>

                                        <button wire:click="delete({{ $scale->id }})" 
                                                wire:confirm="Delete grade {{ $scale->grade }}?" 
                                                title="Delete Band" 
                                                class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-12 px-6 text-center">
                                    <div class="flex flex-col items-center justify-center max-w-xs mx-auto space-y-2">
                                        <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-xl shadow-2xs">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 1312 3 9 21l8 8-4-4-6 6" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 012-2h2a2 2 0 012 2v6" />
                                            </svg>
                                        </div>
                                        <p class="text-sm font-bold text-slate-800">No Grade Bands Configured</p>
                                        <p class="text-xs font-medium text-slate-400">Add your own customized score bands or install the default scale to populate this stage.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>