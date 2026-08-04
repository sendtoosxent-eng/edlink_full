<div class="space-y-6">
    <!-- Top Hero Banner -->
    <header class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-xs">
        <div class="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="mt-3 text-2xl font-black sm:text-3xl text-amber-300 tracking-tight">Homework & Assignments</h1>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-300">
                    {{ $isStudent ? 'View your course assignments, submit your completed work, and review teacher feedback.' : 'Create and publish class assignments, track submissions, and evaluate learner work.' }}
                </p>
            </div>
            
            <div class="rounded-xl border border-slate-700/80 bg-slate-800/50 px-4 py-3 text-xs backdrop-blur-md shrink-0">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Assignments</span>
                <b class="mt-0.5 block text-base font-black text-amber-300">
                    {{ $assignments->count() }} {{ Str::plural('task', $assignments->count()) }}
                </b>
            </div>
        </div>

        <!-- Ambient Glow Effects -->
        <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>
        <div class="absolute left-1/3 bottom-0 h-32 w-32 rounded-full bg-slate-700/20 blur-2xl pointer-events-none"></div>
    </header>

    <!-- Session Status Alert -->
    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-800 shadow-2xs">
            {{ session('status') }}
        </div>
    @endif

    <!-- Create Homework Section (Teachers / Staff) -->
    @if(!$isStudent && auth()->user()->role !== 'parent')
        <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
            <div class="border-b border-slate-100 pb-4">
                <h2 class="text-base font-bold text-slate-900">Create New Homework</h2>
                <p class="mt-0.5 text-xs font-medium text-slate-400">Fill in the assignment details below for your class.</p>
            </div>

            <form wire:submit="createAssignment" class="mt-5 grid gap-4 md:grid-cols-2">
                <!-- Class Selection -->
                <label class="block text-xs font-bold text-slate-700">
                    Class *
                    <select wire:model="classId" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm font-medium text-slate-800 focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20 transition">
                        <option value="">Select class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                    @error('classId')<span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span>@enderror
                </label>

                <!-- Subject Selection -->
                <label class="block text-xs font-bold text-slate-700">
                    Subject *
                    <select wire:model="subjectId" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm font-medium text-slate-800 focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20 transition">
                        <option value="">Select subject</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>
                    @error('subjectId')<span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span>@enderror
                </label>

                <!-- Assignment Title -->
                <label class="block text-xs font-bold text-slate-700 md:col-span-2">
                    Title *
                    <input wire:model="title" type="text" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20 transition" placeholder="e.g. Fractions Exercise 3">
                    @error('title')<span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span>@enderror
                </label>

                <!-- Instructions -->
                <label class="block text-xs font-bold text-slate-700 md:col-span-2">
                    Instructions *
                    <textarea wire:model="instructions" rows="4" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20 transition" placeholder="Explain what learners should complete and how to submit it."></textarea>
                    @error('instructions')<span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span>@enderror
                </label>

                <!-- Due Date & Time -->
                <label class="block text-xs font-bold text-slate-700">
                    Due Date & Time *
                    <input wire:model="dueAt" type="datetime-local" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm font-medium text-slate-800 focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20 transition">
                    @error('dueAt')<span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span>@enderror
                </label>

                <!-- Maximum Score -->
                <label class="block text-xs font-bold text-slate-700">
                    Maximum Score *
                    <input wire:model="maximumScore" type="number" min="1" max="1000" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm font-medium text-slate-800 focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20 transition">
                    @error('maximumScore')<span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span>@enderror
                </label>

                <!-- Teacher Attachment -->
                <label class="block text-xs font-bold text-slate-700 md:col-span-2">
                    Teacher Attachment <span class="font-normal text-slate-400">(Optional, max 10 MB)</span>
                    <input wire:model="attachment" type="file" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-xs font-medium text-slate-700 focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20 transition file:mr-3 file:rounded-lg file:border-0 file:bg-slate-200 file:px-3 file:py-1 file:text-xs file:font-bold file:text-slate-700 file:cursor-pointer hover:file:bg-slate-300">
                    @error('attachment')<span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span>@enderror
                </label>

                <!-- Submit Button -->
                <div class="pt-2 md:col-span-2">
                    <button wire:loading.attr="disabled" class="flex w-full items-center justify-center gap-2 rounded-xl bg-amber-400 hover:bg-amber-300 active:scale-[0.99] px-5 py-3 text-xs font-black text-slate-950 shadow-xs transition cursor-pointer disabled:cursor-not-allowed disabled:opacity-50">
                        <span wire:loading wire:target="createAssignment"><x-edlink-loader :size="18" /></span>
                        <span>Publish Homework</span>
                    </button>
                </div>
            </form>

            @if($classes->isEmpty())
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-xs font-medium text-amber-900">
                    No class/subject teaching assignments are available. Ask the academic administrator to assign your subjects first.
                </div>
            @endif
        </section>
    @endif

    <!-- Main Workspace Area -->
    <div class="grid items-start gap-6 lg:grid-cols-[360px_minmax(0,1fr)]">
        <!-- Sidebar: Homework List -->
        <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
            <div class="border-b border-slate-100 bg-slate-900 px-5 py-4 text-white">
                <h2 class="text-xs font-black uppercase tracking-wider text-amber-300">
                    {{ $isStudent ? 'My Assignments' : 'Published Assignments' }}
                </h2>
                <span class="text-[10px] font-medium text-slate-400">Select an item to view full details</span>
            </div>

            <div class="max-h-[700px] divide-y divide-slate-100 overflow-y-auto">
                @forelse($assignments as $assignment)
                    @php($ownSubmission = $student ? $assignment->submissions->firstWhere('student_id', $student->id) : null)
                    <button wire:click="selectAssignment({{ $assignment->id }})" 
                            class="group block w-full p-4 text-left transition hover:bg-slate-50 cursor-pointer {{ $selectedAssignment?->id === $assignment->id ? 'bg-amber-50/60 border-l-4 border-amber-400' : '' }}">
                        <div class="flex items-start justify-between gap-2">
                            <b class="text-xs font-bold text-slate-900 group-hover:text-slate-950">{{ $assignment->title }}</b>
                            <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold {{ $assignment->due_at->isPast() ? 'bg-rose-50 border border-rose-200/60 text-rose-700' : 'bg-emerald-50 border border-emerald-200/60 text-emerald-700' }}">
                                {{ $assignment->due_at->isPast() ? 'Past Due' : 'Open' }}
                            </span>
                        </div>
                        <p class="mt-1 text-[11px] font-medium text-slate-500">
                            {{ $assignment->subject->name }} · {{ $assignment->schoolClass->name }}
                        </p>
                        <p class="mt-1.5 text-[10px] text-slate-400">
                            Due {{ $assignment->due_at->format('d M Y, H:i') }}
                        </p>
                        @if($isStudent && $ownSubmission)
                            <div class="mt-2.5 inline-flex items-center gap-1.5 rounded-md bg-indigo-50 border border-indigo-100 px-2 py-0.5 text-[10px] font-bold text-indigo-700">
                                <span>{{ ucfirst($ownSubmission->status) }}</span>
                                @if($ownSubmission->score !== null)
                                    <span>· {{ $ownSubmission->score }}/{{ $assignment->maximum_score }}</span>
                                @endif
                            </div>
                        @elseif(!$isStudent)
                            <div class="mt-2.5 text-[10px] font-bold text-indigo-700">
                                {{ $assignment->submissions->count() }} {{ Str::plural('submission', $assignment->submissions->count()) }}
                            </div>
                        @endif
                    </button>
                @empty
                    <div class="p-10 text-center text-xs font-medium text-slate-400">
                        No homework assignments available.
                    </div>
                @endforelse
            </div>
        </section>

        <!-- Main Detail View Panel -->
        <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
            @if(!$selectedAssignment)
                <div class="flex flex-col items-center justify-center py-20 text-center">
                    <div class="rounded-full bg-slate-100 p-3 text-slate-400">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6203 12 14m0 0l-3-3m3 3l3-3m-12 8h14" />
                        </svg>
                    </div>
                    <p class="mt-3 text-xs font-bold text-slate-700">No Assignment Selected</p>
                    <p class="mt-0.5 text-xs text-slate-400">Select an assignment from the left menu to view instructions and submissions.</p>
                </div>
            @else
                <!-- Header Info -->
                <div class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-100 pb-5">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">{{ $selectedAssignment->title }}</h2>
                        <p class="mt-1 text-xs font-medium text-slate-500">
                            {{ $selectedAssignment->subject->name }} · {{ $selectedAssignment->schoolClass->name }} · {{ $selectedAssignment->teacher->name }}
                        </p>
                    </div>
                    <div class="text-right">
                        <span class="block text-xs font-bold text-slate-900">Due {{ $selectedAssignment->due_at->format('d M Y, H:i') }}</span>
                        <span class="mt-0.5 block text-xs font-medium text-slate-500">{{ $selectedAssignment->maximum_score }} marks max</span>
                    </div>
                </div>

                <!-- Assignment Instructions -->
                <div class="mt-5">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Instructions</h3>
                    <div class="mt-2 whitespace-pre-line rounded-xl bg-slate-50/80 border border-slate-100 p-4 text-xs leading-relaxed text-slate-700">
                        {{ $selectedAssignment->instructions }}
                    </div>
                </div>

                <!-- Teacher Attachment Download -->
                @if($selectedAssignment->attachment_path)
                    <div class="mt-4">
                        <a href="{{ route('homework.assignment.download', $selectedAssignment) }}" 
                           class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 px-3.5 py-2 text-xs font-bold text-indigo-700 transition">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Download Attachment: {{ $selectedAssignment->attachment_name }}
                        </a>
                    </div>
                @endif

                <!-- Student View: Homework Submission Form -->
                @if($isStudent)
                    @php($submission = $selectedAssignment->submissions->firstWhere('student_id', $student?->id))
                    <form wire:submit="submitHomework" class="mt-8 space-y-4 border-t border-slate-100 pt-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-slate-900">Your Submission</h3>
                            @if($submission)
                                <span class="rounded-full bg-slate-100 px-2.5 py-0.5 text-[10px] font-bold text-slate-600">
                                    Submitted {{ $submission->submitted_at->format('d M Y, H:i') }}
                                </span>
                            @endif
                        </div>

                        <!-- Written Answer -->
                        <label class="block text-xs font-bold text-slate-700">
                            Written Answer
                            <textarea wire:model="answer" rows="6" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm font-medium text-slate-800 focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20 transition"></textarea>
                            @error('answer')<span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span>@enderror
                        </label>

                        <!-- Attach Work File -->
                        <label class="block text-xs font-bold text-slate-700">
                            Attach Work <span class="font-normal text-slate-400">(Optional, max 10 MB)</span>
                            <input wire:model="submissionAttachment" type="file" class="mt-1.5 block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2.5 text-xs font-medium text-slate-700 focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20 transition file:mr-3 file:rounded-lg file:border-0 file:bg-slate-200 file:px-3 file:py-1 file:text-xs file:font-bold file:text-slate-700 file:cursor-pointer hover:file:bg-slate-300">
                            @error('submissionAttachment')<span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span>@enderror
                        </label>

                        @if($submission?->attachment_path)
                            <div class="pt-1">
                                <a href="{{ route('homework.submission.download', $submission) }}" class="text-xs font-bold text-indigo-700 hover:underline">
                                    Download Current Attached File
                                </a>
                            </div>
                        @endif

                        <button type="submit" class="w-full rounded-xl bg-amber-400 hover:bg-amber-300 active:scale-[0.99] px-5 py-3 text-xs font-black text-slate-950 shadow-xs transition cursor-pointer">
                            {{ $submission ? 'Resubmit Homework' : 'Submit Homework' }}
                        </button>

                        <!-- Teacher Feedback Box -->
                        @if($submission?->status === 'reviewed')
                            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50/60 p-4 text-xs">
                                <b class="block font-bold text-emerald-950">
                                    Teacher Feedback · {{ $submission->score }}/{{ $selectedAssignment->maximum_score }} marks
                                </b>
                                <p class="mt-1.5 text-emerald-900 leading-relaxed">
                                    {{ $submission->feedback ?: 'No written feedback provided.' }}
                                </p>
                            </div>
                        @endif
                    </form>
                @else
                    <!-- Teacher View: Review Learner Submissions -->
                    <div class="mt-8 border-t border-slate-100 pt-6">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-bold text-slate-900">Learner Submissions</h3>
                            <span class="text-xs font-medium text-slate-400">{{ $selectedAssignment->submissions->count() }} Received</span>
                        </div>

                        <div class="mt-4 space-y-4">
                            @forelse($selectedAssignment->submissions as $submission)
                                <article class="rounded-xl border border-slate-200/80 bg-slate-50/50 p-4 transition">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <b class="text-xs font-bold text-slate-900">{{ $submission->student->name }}</b>
                                            <p class="mt-0.5 text-[10px] font-medium text-slate-400">
                                                {{ ucfirst($submission->status) }} · {{ $submission->submitted_at->format('d M Y, H:i') }}
                                            </p>
                                        </div>
                                        @if($submission->attachment_path)
                                            <a href="{{ route('homework.submission.download', $submission) }}" class="rounded-lg bg-indigo-50 border border-indigo-100 px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100 transition">
                                                Download File
                                            </a>
                                        @endif
                                    </div>

                                    @if($submission->answer)
                                        <p class="mt-3 whitespace-pre-line rounded-lg border border-slate-200/60 bg-white p-3 text-xs leading-relaxed text-slate-700">
                                            {{ $submission->answer }}
                                        </p>
                                    @endif

                                    <!-- Grading Controls -->
                                    <div class="mt-4 grid gap-3 sm:grid-cols-[140px_1fr_auto]">
                                        <label class="block text-xs font-bold text-slate-700">
                                            Score / {{ $selectedAssignment->maximum_score }}
                                            <input wire:model="reviewScores.{{ $submission->id }}" type="number" min="0" max="{{ $selectedAssignment->maximum_score }}" step=".01" class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-800 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20 transition">
                                        </label>
                                        <label class="block text-xs font-bold text-slate-700">
                                            Feedback
                                            <input wire:model="reviewFeedback.{{ $submission->id }}" type="text" class="mt-1 block w-full rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-800 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20 transition" placeholder="Optional comments...">
                                        </label>
                                        <button wire:click="review({{ $submission->id }})" class="self-end rounded-lg bg-slate-900 hover:bg-slate-800 px-4 py-2 text-xs font-bold text-white transition cursor-pointer">
                                            Save Review
                                        </button>
                                    </div>
                                    @error('reviewScores.'.$submission->id)
                                        <span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span>
                                    @enderror
                                </article>
                            @empty
                                <div class="rounded-xl border border-slate-100 bg-slate-50/50 p-8 text-center text-xs font-medium text-slate-400">
                                    No learners have submitted work for this homework assignment yet.
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endif
            @endif
        </section>
    </div>
</div>