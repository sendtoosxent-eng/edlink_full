<div class="space-y-6">

    <!-- Top Hero Banner -->
    <header class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-xs">
        <div class="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                
                <h1 class="mt-3 text-2xl font-black sm:text-3xl text-amber-300 tracking-tight">Marks Entry</h1>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-300">
                    Enter, review, and submit learner exam scores for official academic approval.
                </p>
            </div>

            @if ($term)
                <div class="flex items-center gap-3 rounded-xl border border-slate-700/80 bg-slate-800/60 px-4 py-3 text-xs backdrop-blur-md shrink-0">
                    <div>
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Current Term</span>
                        <strong class="text-sm font-bold text-amber-300">{{ $term->name }}</strong>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold {{ $term->isOpen() ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-700 text-slate-300 border border-slate-600' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $term->isOpen() ? 'bg-emerald-400' : 'bg-slate-400' }}"></span>
                        {{ $term->isOpen() ? 'Open' : 'Closed' }}
                    </span>
                </div>
            @endif
        </div>

        <!-- Ambient Glow Effects -->
        <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>
        <div class="absolute left-1/3 bottom-0 h-32 w-32 rounded-full bg-slate-700/20 blur-2xl pointer-events-none"></div>
    </header>

    <!-- Flash Status Notification -->
    @if (session('status'))
        <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-800 shadow-2xs">
            <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if (! $term)
        <!-- Empty State: No Term -->
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center shadow-xs">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 text-amber-600 mb-3">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <h2 class="text-base font-bold text-slate-900">No Active School Term</h2>
            <p class="mt-1 text-xs text-slate-500 max-w-sm mx-auto">Please open or activate an academic school term before attempting to enter examination marks.</p>
        </div>
    @else
        <!-- Paper Selection Card -->
        <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <label for="paper" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-700">Select Examination Paper *</label>
            <div class="relative">
                <select id="paper" wire:model.live="paperId" class="block w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-3 pr-10 text-sm font-semibold text-slate-800 focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20 transition cursor-pointer">
                    <option value="">Choose an assigned paper...</option>
                    @foreach ($papers as $item)
                        <option value="{{ $item->id }}">
                            {{ $item->exam->name }} — {{ $item->exam->schoolClass->name }}{{ $item->exam->stream ? ' '.$item->exam->stream->name : '' }} — {{ $item->subject->name }}
                        </option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>

            @error('paperId')
                <p class="mt-2 text-xs font-bold text-rose-600 flex items-center gap-1">
                    <span>{{ $message }}</span>
                </p>
            @enderror

            @if ($papers->isEmpty())
                <p class="mt-3 text-xs font-medium text-slate-400 italic">No examination papers are currently assigned to you for this term.</p>
            @endif
        </section>
    @endif

    @if ($paper)
        @php
            $completed = collect($scores)->filter(fn ($score) => $score !== null && $score !== '')->count();
            $progress = $students->count() ? round(($completed / $students->count()) * 100) : 0;
            $statusStyle = match ($paperStatus) { 
                'approved' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 
                'submitted' => 'bg-sky-50 text-sky-700 border-sky-200', 
                default => 'bg-amber-50 text-amber-800 border-amber-200' 
            };
            $statusDot = match ($paperStatus) { 
                'approved' => 'bg-emerald-500', 
                'submitted' => 'bg-sky-500', 
                default => 'bg-amber-500' 
            };
        @endphp

        <!-- Metrics Overview Bar -->
        <div class="grid gap-4 md:grid-cols-4">
            <!-- Active Paper Details -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs md:col-span-2">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Active Subject Paper</span>
                <p class="mt-1 text-base font-black text-slate-900">{{ $paper->exam->name }} · <span class="text-indigo-600">{{ $paper->subject->name }}</span></p>
                <p class="mt-1 text-xs font-medium text-slate-500">
                    Target Class: <strong class="text-slate-700">{{ $paper->exam->schoolClass->name }}</strong> 
                    ({{ $paper->exam->stream ? $paper->exam->stream->name : 'All Streams' }})
                </p>
            </div>

            <!-- Approval Status -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Approval Status</span>
                <div class="mt-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-bold uppercase tracking-wider {{ $statusStyle }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                        {{ $paperStatus }}
                    </span>
                </div>
            </div>

            <!-- Completion Progress Bar -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Entry Progress</span>
                    <span class="text-xs font-black text-slate-800">{{ $progress }}%</span>
                </div>
                <p class="mt-1 text-lg font-black text-slate-900">{{ $completed }} <span class="text-xs font-medium text-slate-400">/ {{ $students->count() }} learners</span></p>
                <div class="mt-2 h-1.5 w-full rounded-full bg-slate-100 overflow-hidden">
                    <div class="h-full rounded-full bg-amber-400 transition-all duration-500" style="width: {{ $progress }}%"></div>
                </div>
            </div>
        </div>

        <!-- Marks Entry Form & Table Card -->
        <form wire:submit="saveDraft" class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
            
            <div class="border-b border-slate-100 bg-slate-900 px-6 py-4 text-white flex items-center justify-between">
                <div>
                    <h2 class="text-xs font-black uppercase tracking-wider text-amber-300">Learners Score Roster</h2>
                    <p class="text-[11px] font-medium text-slate-400 mt-0.5">Maximum score allowed for this paper: <strong>{{ number_format($paper->maximum_score, 0) }} Marks</strong></p>
                </div>
                <span class="rounded-md bg-slate-800 px-2.5 py-1 text-[11px] font-bold text-slate-300 border border-slate-700/80">
                    {{ $students->count() }} {{ Str::plural('Learner', $students->count()) }}
                </span>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/70 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="px-6 py-3.5">Learner Name</th>
                            <th class="px-6 py-3.5">Admission No.</th>
                            <th class="px-6 py-3.5 text-right">Score / {{ number_format($paper->maximum_score, 0) }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($students as $student)
                            <tr class="transition hover:bg-slate-50/50">
                                <td class="px-6 py-4 font-bold text-slate-900">
                                    {{ $student->name }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($student->admission_no)
                                        <span class="font-mono text-xs font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded border border-slate-200/60">
                                            {{ $student->admission_no }}
                                        </span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <div class="inline-flex flex-col items-end">
                                        <input 
                                            wire:model.blur="scores.{{ $student->id }}" 
                                            type="number" 
                                            min="0" 
                                            max="{{ $paper->maximum_score }}" 
                                            step="0.01" 
                                            placeholder="0.00"
                                            class="w-32 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-right font-black text-slate-900 placeholder:text-slate-300 focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20 transition disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed" 
                                            @disabled($paperStatus === 'approved' || ! $term->isOpen())
                                        >
                                        @error('scores.'.$student->id)
                                            <p class="mt-1 text-[11px] font-bold text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center">
                                    <p class="text-sm font-medium text-slate-500">No active learners are currently enrolled for this subject paper.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Form Action Footer Bar -->
            <div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-100 bg-slate-50/80 px-6 py-4">
                <p class="text-xs font-medium text-slate-500">
                    <span class="font-bold text-slate-700">Note:</span> Drafts can be saved partially. Official submission requires scores for every learner.
                </p>

                <div class="flex flex-wrap gap-2.5">
                    @if ($paperStatus !== 'approved' && $term->isOpen() && $students->isNotEmpty())
                        <button type="submit" wire:loading.attr="disabled" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-xs font-bold text-slate-800 shadow-2xs hover:bg-slate-50 active:scale-[0.99] transition cursor-pointer">
                            Save Draft
                        </button>
                        
                        <button type="button" wire:click="submitForApproval" wire:loading.attr="disabled" class="rounded-xl bg-amber-400 px-5 py-2.5 text-xs font-black text-slate-950 shadow-2xs hover:bg-amber-300 active:scale-[0.99] transition cursor-pointer">
                            Submit for Approval
                        </button>
                    @endif

                    @if ($canManage && $paperStatus === 'submitted' && $term->isOpen())
                        <button type="button" wire:click="approve" wire:loading.attr="disabled" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-xs font-bold text-white shadow-2xs hover:bg-emerald-500 active:scale-[0.99] transition cursor-pointer">
                            Approve Marks
                        </button>
                    @endif

                    @if ($canManage && $paperStatus === 'approved' && $term->isOpen())
                        <button type="button" wire:click="reopen" wire:confirm="Reopen this paper for editing?" wire:loading.attr="disabled" class="rounded-xl border border-rose-200 bg-white px-5 py-2.5 text-xs font-bold text-rose-600 shadow-2xs hover:bg-rose-50 active:scale-[0.99] transition cursor-pointer">
                            Reopen Paper
                        </button>
                    @endif
                </div>
            </div>

        </form>
    @endif

</div>