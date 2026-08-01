<div class="space-y-6">
    <!-- Header Section -->
    <header class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-950 via-slate-900 to-slate-800 p-6 text-white shadow-sm sm:p-8">
        <div class="relative z-10 flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
            <div class="max-w-2xl">
                <h1 class="mt-2 text-2xl font-black tracking-tight text-amber-300 sm:text-3xl">Results & Publication</h1>
                <p class="mt-2 text-sm leading-relaxed text-slate-400">Review approved marks, subject grades and overall class rankings before publishing.</p>
            </div>

            @if ($term)
                <div class="w-fit rounded-xl border border-amber-400/20 bg-amber-400/10 px-4 py-3 backdrop-blur-sm">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-amber-300">Current academic term</p>
                    <div class="mt-1 flex items-center gap-2">
                        <strong class="text-sm text-white">{{ $term->name }}, {{ $term->year }}</strong>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-black uppercase {{ $term->isOpen() ? 'bg-emerald-400/15 text-emerald-300' : 'bg-slate-700 text-slate-300' }}">
                            {{ $term->isOpen() ? 'Open' : 'Closed' }}
                        </span>
                    </div>
                </div>
            @endif
        </div>
        <div class="pointer-events-none absolute -right-16 -bottom-20 h-72 w-72 rounded-full bg-amber-400/10 blur-3xl"></div>
    </header>

    <!-- Status Alert -->
    @if (session('status'))
        <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-sm font-semibold text-emerald-900 shadow-sm">
            <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Examination Selector Box -->
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <label for="exam" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-600">Select Examination</label>
        <div class="relative">
            <select id="exam" wire:model.live="examId" class="w-full rounded-xl border-slate-300 bg-slate-50/50 py-2.5 pl-3.5 pr-8 text-sm font-bold text-slate-800 transition focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-500/20">
                <option value="">Choose an examination...</option>
                @foreach ($exams as $item)
                    <option value="{{ $item->id }}">
                        {{ $item->name }} — {{ $item->schoolClass->name }}{{ $item->stream ? ' ('.$item->stream->name.')' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        @error('examId')
            <p class="mt-2 text-xs font-bold text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    @if ($exam)
        <!-- Exam Summary Stats Grid -->
        <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:col-span-2">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Examination Focus</p>
                <h3 class="mt-1 text-lg font-black text-slate-900">{{ $exam->name }}</h3>
                <p class="mt-0.5 text-xs font-semibold text-slate-500">
                    {{ $exam->schoolClass->name }}{{ $exam->stream ? ' · '.$exam->stream->name : ' · All Class Streams' }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Publication Status</p>
                <div class="mt-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold ring-1 ring-inset {{ $exam->isPublished() ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-slate-100 text-slate-600 ring-slate-500/10' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $exam->isPublished() ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                        {{ $exam->isPublished() ? 'Published to Parents' : 'Internal Only' }}
                    </span>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Learners Evaluated</p>
                <p class="mt-1 text-2xl font-black text-slate-900">{{ $results->count() }}</p>
                <p class="text-[11px] font-medium text-slate-400">Ranked by weighted average</p>
            </div>
        </div>

        <!-- Publication Readiness Bar -->
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-base font-black text-slate-900">Publication Checklist</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Ensure all academic criteria are satisfied before releasing scores.</p>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <!-- All Papers Approved -->
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold ring-1 ring-inset {{ $readiness['all_papers_approved'] ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-rose-50 text-rose-700 ring-rose-600/20' }}">
                            <span>{{ $readiness['all_papers_approved'] ? '✓' : '✕' }}</span>
                            {{ $readiness['all_papers_approved'] ? 'All papers approved' : 'Papers awaiting approval' }}
                        </span>

                        <!-- Grading Configured -->
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold ring-1 ring-inset {{ $readiness['grading_ready'] ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-rose-50 text-rose-700 ring-rose-600/20' }}">
                            <span>{{ $readiness['grading_ready'] ? '✓' : '✕' }}</span>
                            {{ $readiness['grading_ready'] ? 'Grading scale configured' : 'Grading incomplete' }}
                        </span>

                        <!-- Learners Available -->
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold ring-1 ring-inset {{ $readiness['has_students'] ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-rose-50 text-rose-700 ring-rose-600/20' }}">
                            <span>{{ $readiness['has_students'] ? '✓' : '✕' }}</span>
                            {{ $readiness['has_students'] ? 'Learners enrolled' : 'No active learners' }}
                        </span>

                        <!-- Missing Marks -->
                        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold ring-1 ring-inset {{ $readiness['missing_marks'] === 0 ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-rose-50 text-rose-700 ring-rose-600/20' }}">
                            <span>{{ $readiness['missing_marks'] === 0 ? '✓' : '✕' }}</span>
                            {{ $readiness['missing_marks'] === 0 ? 'Marks entry 100%' : $readiness['missing_marks'].' missing marks' }}
                        </span>
                    </div>
                </div>

                <!-- Action Controls -->
                <div class="flex flex-wrap items-center gap-2 pt-3 border-t border-slate-100 lg:pt-0 lg:border-t-0">
                    @if (! $readiness['grading_ready'])
                        <a href="{{ route('grading-scales.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-amber-500/20">
                            Configure Grading
                        </a>
                    @endif

                    @if ($canManage && $exam->term->isOpen())
                        @if ($exam->isPublished())
                            <button wire:click="unpublish"
                                    wire:confirm="Hide these results from learners and parents?"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-5 py-2.5 text-xs font-bold text-rose-700 shadow-sm transition hover:bg-rose-100 focus:outline-none focus:ring-4 focus:ring-rose-500/20 disabled:opacity-50">
                                <span wire:loading.remove wire:target="unpublish">Unpublish Results</span>
                                <span wire:loading wire:target="unpublish">Unpublishing...</span>
                            </button>
                        @else
                            <button wire:click="publish"
                                    @disabled(! $readiness['ready'])
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-2 rounded-xl bg-amber-400 px-6 py-2.5 text-xs font-black text-slate-950 shadow-sm transition hover:bg-amber-300 focus:outline-none focus:ring-4 focus:ring-amber-500/20 disabled:cursor-not-allowed disabled:opacity-50">
                                <span wire:loading.remove wire:target="publish">Publish Results</span>
                                <span wire:loading wire:target="publish">Publishing...</span>
                            </button>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <!-- Master Results Table -->
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full border-collapse text-left text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                            <th class="px-4 py-3.5 text-center w-16">Pos</th>
                            <th class="px-4 py-3.5">Learner</th>
                            @foreach ($papers as $paper)
                                <th class="px-4 py-3.5 text-center min-w-[100px]">{{ $paper->subject->name }}</th>
                            @endforeach
                            <th class="px-4 py-3.5 text-center w-24">Average</th>
                            <th class="px-4 py-3.5 text-center w-20">Grade</th>
                            <th class="px-4 py-3.5 text-right w-28">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($results as $result)
                            <tr class="transition hover:bg-slate-50/60">
                                <!-- Position Column -->
                                <td class="px-4 py-3.5 text-center">
                                    @if ($result['position'] == 1)
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-100 text-xs font-black text-amber-800 ring-1 ring-amber-400/40">1</span>
                                    @elseif ($result['position'] == 2)
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-200 text-xs font-black text-slate-700">2</span>
                                    @elseif ($result['position'] == 3)
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-amber-900/10 text-xs font-black text-amber-900">3</span>
                                    @else
                                        <span class="font-bold text-slate-600">{{ $result['position'] }}</span>
                                    @endif
                                </td>

                                <!-- Learner Identity -->
                                <td class="px-4 py-3.5">
                                    <div class="font-bold text-slate-900">{{ $result['student']->name }}</div>
                                    <div class="font-mono text-[11px] font-semibold text-slate-400">
                                        {{ $result['student']->admission_no ?? 'No Reg' }}
                                    </div>
                                </td>

                                <!-- Subject Scores -->
                                @foreach ($result['subjects'] as $subject)
                                    <td class="px-4 py-3.5 text-center">
                                        @if (! ($subject['applicable'] ?? true))
                                            <span class="text-xs font-medium text-slate-300">N/A</span>
                                        @else
                                            <div class="font-bold text-slate-800">
                                                {{ $subject['score'] === null ? '—' : number_format($subject['score'], 1) }}
                                                <span class="text-[10px] font-normal text-slate-400">/{{ number_format($subject['paper']->maximum_score, 0) }}</span>
                                            </div>
                                            <div class="text-[11px] font-black text-amber-600">{{ $subject['grade'] ?? '' }}</div>
                                        @endif
                                    </td>
                                @endforeach

                                <!-- Average Score -->
                                <td class="px-4 py-3.5 text-center">
                                    <span class="font-black text-slate-900">{{ number_format($result['average'], 2) }}%</span>
                                </td>

                                <!-- Overall Grade Badge -->
                                <td class="px-4 py-3.5 text-center">
                                    <span class="inline-block rounded-md bg-slate-900 px-2.5 py-1 text-xs font-black text-white shadow-xs">
                                        {{ $result['grade'] }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3.5 text-right">
                                    <a target="_blank"
                                       href="{{ route('exams.report-card', [$exam, $result['student']]) }}"
                                       class="inline-flex items-center gap-1 text-xs font-bold text-amber-700 transition hover:text-amber-900 hover:underline">
                                        <span>Report Card</span>
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                        </svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $papers->count() + 5 }}" class="px-5 py-12 text-center">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7 3h7l5 5v13H7a2 2 0 01-2-2V5a2 2 0 012-2z" />
                                        </svg>
                                    </div>
                                    <p class="mt-3 font-bold text-slate-700">No Approved Results Available</p>
                                    <p class="mt-1 text-xs text-slate-400">Select another examination or verify that subject marks have been submitted and approved.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
