<div>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        @page {
            size: A4 portrait;
            margin: 0;
        }
        @media print {
            body * {
                visibility: hidden !important;
            }
            .report-container,
            .report-container * {
                visibility: visible !important;
            }
            body {
                background: white !important;
                padding: 0 !important;
            }
            .report-container {
                position: absolute !important;
                inset: 0 auto auto 0 !important;
                box-shadow: none !important;
                border: none !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>

    @unless(in_array(auth()->user()->role, ['parent', 'student'], true))
    <section class="no-print mx-auto mb-5 max-w-[210mm] rounded-2xl border bg-white p-5 shadow-sm">
        <div class="mb-4"><h2 class="font-bold">Build student report</h2><p class="text-xs text-slate-500">Choose the term, learner and examination.</p></div>
        <div class="grid gap-4 md:grid-cols-3">
            <label class="text-sm font-semibold">Term<select wire:model.live="termId" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm"><option value="">Choose term</option>@foreach($terms as $item)<option value="{{ $item->id }}">{{ $item->name }}, {{ $item->year }}</option>@endforeach</select></label>
            <label class="text-sm font-semibold">Student<select wire:model.live="studentId" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm"><option value="">Choose student</option>@foreach($students as $item)<option value="{{ $item->id }}">{{ $item->name }}{{ $item->admission_no ? ' · '.$item->admission_no : '' }}</option>@endforeach</select></label>
            <label class="text-sm font-semibold">Examination<select wire:model.live="examId" class="mt-1.5 w-full rounded-xl border-slate-300 text-sm"><option value="">Choose examination</option>@foreach($exams as $item)<option value="{{ $item->id }}">{{ $item->name }}{{ $item->stream ? ' · '.$item->stream->name : '' }}</option>@endforeach</select></label>
        </div>
        @if($termId && $studentId && $exams->isEmpty())<p class="mt-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-800">No examination is available for this learner in the selected term.</p>@endif
    </section>
    @endunless
    @if($student && $term && $exam)

    <!-- PRINT CONTROL BUTTONS -->
    <div class="max-w-[210mm] mx-auto mb-4 flex items-center justify-between no-print pt-4">
        <button onclick="window.history.back()" class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-slate-900 bg-white border border-slate-200 px-3.5 py-2 rounded-xl shadow-2xs transition">
            <i class="fa fa-arrow-left"></i>
            <span>Back</span>
        </button>
        <button onclick="window.print()" class="inline-flex items-center gap-2 text-xs font-bold text-slate-950 bg-yellow-400 hover:bg-yellow-300 px-4 py-2 rounded-xl shadow-xs transition">
            <i class="fa fa-print"></i>
            <span>Print Report Card</span>
        </button>
    </div>

    <!-- MAIN REPORT CONTAINER -->
    <main class="report-container max-w-[210mm] mx-auto bg-white p-4 sm:p-6 shadow-xl relative min-h-[297mm]">

        <!-- DOUBLE BORDER WRAPPER -->
        <div class="border-4 border-slate-900 p-1.5 h-full">
            <div class="relative border-2 border-slate-900 p-6 sm:p-8 h-full flex flex-col justify-between overflow-hidden">

                <div class="pointer-events-none absolute inset-0 z-0 flex items-center justify-center overflow-hidden" aria-hidden="true">
                    @if($school->logo_url ?? false)
                        <img src="{{ asset($school->logo_url) }}" class="h-72 w-72 object-contain opacity-[0.06] grayscale">
                    @else
                        <span class="-rotate-45 text-6xl font-black uppercase tracking-widest text-slate-900 opacity-[0.04]">{{ $school->name }}</span>
                    @endif
                </div>

                <div class="relative z-10">
                    <!-- 1. HEADER SECTION -->
                    <header class="relative flex items-center justify-between gap-4 mb-6">
                        <!-- School Logo -->
                        <div class="w-20 h-20 rounded-full border-2 border-yellow-400 p-1 flex items-center justify-center flex-shrink-0 bg-white">
                            @if($school->logo_url ?? false)
                                <img src="{{ asset($school->logo_url) }}" class="w-full h-full object-cover rounded-full">
                            @else
                                <i class="fa fa-graduation-cap text-slate-800 text-3xl"></i>
                            @endif
                        </div>

                        <!-- School Info & Title -->
                        <div class="text-center flex-1">
                            <h1 class="text-2xl sm:text-3xl font-black uppercase text-slate-900 tracking-tight leading-none">
                                {{ $school->name ?? 'SAMEED HIGH SCHOOL' }}
                            </h1>
                            <p class="text-[11px] font-semibold text-slate-600 uppercase tracking-wider mt-1">
                                {{ $school->address ?? 'CAMPUS ADDRESS, CITY, COUNTRY' }}
                            </p>
                            <p class="mt-1 text-[10px] font-semibold text-slate-600">
                                {{ collect([$school->phone, $school->email, $school->website])->filter()->join(' · ') }}
                            </p>
                            <p class="text-xs font-bold text-slate-900 uppercase tracking-widest mt-2">
                                {{ $exam->name }} · {{ $term->name }}, {{ $term->year }} · {{ str($settings['stage'])->replace('_', ' ')->title() }}
                            </p>

                            <!-- MARKSHEET Banner -->
                            <div class="inline-block bg-yellow-400 text-slate-950 font-black uppercase tracking-widest px-8 py-1 mt-2 text-sm shadow-2xs">
                                {{ $exam->name }} MARKSHEET
                            </div>
                        </div>

                        <!-- Student Passport Photo Box -->
                        <div class="w-20 h-24 border-2 border-slate-900 flex items-center justify-center flex-shrink-0 bg-slate-50 text-slate-300">
                            @if($report->student->photo_url ?? $student->photo_url ?? false)
                                <img src="{{ asset($report->student->photo_url ?? $student->photo_url) }}" class="w-full h-full object-cover">
                            @else
                                <span class="text-[10px] font-bold text-center text-slate-400 uppercase leading-tight px-1">Affix Photo</span>
                            @endif
                        </div>
                    </header>

                    <!-- 2. STUDENT DETAILS META -->
                    <section class="mt-8 mb-6 text-xs font-semibold text-slate-900 space-y-4">
                        <div class="flex items-end gap-2 border-b border-slate-900 pb-1">
                            <span class="font-extrabold text-slate-900 uppercase">Student Name:</span>
                            <span class="font-bold text-slate-900 uppercase flex-1">{{ $student->name ?? ($report->student->name ?? 'N/A') }}</span>
                        </div>

                        <div class="grid grid-cols-12 gap-4">
                            <div class="col-span-4 flex items-end gap-2 border-b border-slate-900 pb-1">
                                <span class="font-extrabold text-slate-900 uppercase">Class:</span>
                                <span class="font-bold text-slate-900 uppercase flex-1">{{ $student->schoolClass->name ?? ($report->student->schoolClass->name ?? 'N/A') }}</span>
                            </div>
                            <div class="col-span-4 flex items-end gap-2 border-b border-slate-900 pb-1">
                                <span class="font-extrabold text-slate-900 uppercase">Roll No:</span>
                                <span class="font-mono font-bold text-slate-900 flex-1">{{ $student->admission_no ?? ($report->student->admission_no ?? 'N/A') }}</span>
                            </div>
                            <div class="col-span-4 flex items-end gap-2 border-b border-slate-900 pb-1">
                                <span class="font-extrabold text-slate-900 uppercase">Section:</span>
                                <span class="font-bold text-slate-900 uppercase flex-1">{{ $student->section ?? ($report->student->section ?? 'A') }}</span>
                            </div>
                        </div>
                    </section>

                    <!-- 3. MARKS & MARKSHEET TABLE -->
                    <div class="mt-6">
                        <table class="w-full text-left text-xs border-2 border-slate-900 border-collapse">
                            <thead>
                                <tr class="bg-slate-900 text-white font-extrabold uppercase border-b-2 border-slate-900 text-[11px]">
                                    <th class="p-2.5 border-r-2 border-slate-900 w-12 text-center">S.NO</th>
                                    <th class="p-2.5 border-r-2 border-slate-900">SUBJECT</th>
                                    @if($settings['show_marks'])<th class="p-2.5 border-r-2 border-slate-900 text-center w-24">MARKS SCORED</th>@endif
                                    @if($settings['show_maximum'])<th class="p-2.5 border-r-2 border-slate-900 text-center w-24">MAXIMUM MARKS</th>@endif
                                    @if($settings['show_percentage'])<th class="p-2.5 border-r-2 border-slate-900 text-center w-20">%</th>@endif
                                    @if($settings['show_grade'])<th class="p-2.5 border-r-2 border-slate-900 text-center w-28">GRADE</th>@endif
                                    @if($settings['show_points'])<th class="p-2.5 border-r-2 border-slate-900 text-center w-24">POINTS</th>@endif
                                    @if($settings['show_remarks'])<th class="p-2.5 text-center w-32">REMARKS</th>@endif
                                </tr>
                            </thead>
                            <tbody class="divide-y-2 divide-slate-900 font-semibold text-slate-900 uppercase">
                                @forelse($grades ?? ($report->grades ?? []) as $index => $grade)
                                <tr>
                                    <td class="p-2.5 border-r-2 border-slate-900 text-center font-bold">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</td>
                                    <td class="p-2.5 border-r-2 border-slate-900 font-bold">{{ $grade->subject->name ?? ($grade['subject_name'] ?? 'SUBJECT') }}</td>
                                    @if($settings['show_marks'])<td class="p-2.5 border-r-2 border-slate-900 text-center font-mono font-bold">{{ rtrim(rtrim(number_format((float) ($grade->score ?? ($grade['score'] ?? 0)), 2, '.', ''), '0'), '.') }}</td>@endif
                                    @if($settings['show_maximum'])<td class="p-2.5 border-r-2 border-slate-900 text-center font-mono font-bold">{{ rtrim(rtrim(number_format((float) ($grade->maximum_score ?? ($grade['maximum_score'] ?? 0)), 2, '.', ''), '0'), '.') }}</td>@endif
                                    @if($settings['show_percentage'])<td class="p-2.5 border-r-2 border-slate-900 text-center font-mono font-bold">{{ number_format((float) $grade->percentage, 1) }}%</td>@endif
                                    @if($settings['show_grade'])<td class="p-2.5 border-r-2 border-slate-900 text-center font-black text-slate-900">{{ $grade->grade_name ?? ($grade['grade_name'] ?? '—') }}</td>@endif
                                    @if($settings['show_points'])<td class="p-2.5 border-r-2 border-slate-900 text-center font-mono font-bold">{{ $grade->aggregate_points ?? '—' }}</td>@endif
                                    @if($settings['show_remarks'])<td class="p-2.5 text-center font-medium text-slate-700">{{ $grade->remarks ?? ($grade['remarks'] ?? 'Grade not configured') }}</td>@endif
                                </tr>
                                @empty
                                <tr>
                                    <td class="p-2.5 border-r-2 border-slate-900 text-center font-bold">01</td>
                                    <td class="p-2.5 border-r-2 border-slate-900 font-bold">NO GRADES RECORDED</td>
                                    @if($settings['show_marks'])<td class="p-2.5 border-r-2 border-slate-900 text-center">—</td>@endif
                                    @if($settings['show_maximum'])<td class="p-2.5 border-r-2 border-slate-900 text-center">—</td>@endif
                                    @if($settings['show_percentage'])<td class="p-2.5 border-r-2 border-slate-900 text-center">—</td>@endif
                                    @if($settings['show_grade'])<td class="p-2.5 border-r-2 border-slate-900 text-center">—</td>@endif
                                    @if($settings['show_points'])<td class="p-2.5 border-r-2 border-slate-900 text-center">—</td>@endif
                                    @if($settings['show_remarks'])<td class="p-2.5 text-center">—</td>@endif
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- 4. ATTENDANCE & GPA SUMMARY BLOCK -->
                    <table class="mt-4 w-full border-2 border-slate-900 border-collapse text-center">
                        <thead class="bg-slate-900 text-[10px] font-black uppercase tracking-wider text-white">
                            <tr>
                                @if($settings['show_points'])<th class="border-r border-slate-600 p-2">Aggregate</th>@endif
                                <th class="border-r border-slate-600 p-2">Average</th>
                                <th class="border-r border-slate-600 p-2">Result</th>
                                @if($settings['show_position'])<th class="border-r border-slate-600 p-2">Position</th>@endif
                                @if($settings['show_attendance'])<th class="p-2">Attendance</th>@endif
                            </tr>
                        </thead>
                        <tbody><tr class="bg-yellow-50 font-mono text-lg font-black text-slate-950">
                            @if($settings['show_points'])<td class="border-r-2 border-slate-900 p-2">{{ $aggregate }}</td>@endif
                            <td class="border-r-2 border-slate-900 p-2">{{ number_format((float) $average, 1) }}%</td>
                            <td class="border-r-2 border-slate-900 p-2 text-sm">{{ $grades->isEmpty() ? 'Pending' : ((float) $average >= $settings['pass'] ? 'Pass' : 'Below pass mark') }}</td>
                            @if($settings['show_position'])<td class="border-r-2 border-slate-900 p-2">{{ $position ?? '—' }}</td>@endif
                            @if($settings['show_attendance'])<td class="p-2">{{ $attendance_present }}/{{ $attendance_total }}</td>@endif
                        </tr></tbody>
                    </table>
                    <p class="mt-1 text-center text-[10px] text-slate-500">Average and aggregate use the best {{ $settings['best'] }} subject{{ $settings['best'] === 1 ? '' : 's' }} · Pass mark {{ number_format($settings['pass'], 0) }}%</p>

                    @if($settings['show_fees'] || $settings['show_promotion'])
                    <div class="mt-3 grid gap-2 text-center text-xs sm:grid-cols-3">
                        @if($settings['show_fees'])<div class="border-2 border-slate-900 p-2"><b class="block uppercase">Fees balance</b><span class="font-mono text-base font-black">{{ number_format($fees['balance'], 0) }}</span><small class="block text-slate-500">Paid {{ number_format($fees['paid'], 0) }} / Due {{ number_format($fees['due'], 0) }}</small></div>@endif
                        @if($settings['show_promotion'])<div class="border-2 border-slate-900 p-2"><b class="block uppercase">Promotion</b><span class="text-base font-black uppercase">{{ $promotion ?: 'Pending' }}</span></div>@endif
                    </div>
                    @endif

                    @if($settings['next_term_starts'])
                    <div class="mt-3 border-2 border-slate-900 p-2 text-center text-xs">
                        <b class="uppercase">Next term starts:</b>
                        <span class="font-black">{{ \Carbon\Carbon::parse($settings['next_term_starts'])->format('l, d F Y') }}</span>
                    </div>
                    @endif

                    <!-- 5. REMARKS BOX -->
                    <div class="mt-5 border-2 border-slate-900">
                        <div class="bg-slate-900 text-white font-extrabold text-xs uppercase p-2">
                            REMARKS:
                        </div>
                        <div class="p-3 text-xs font-semibold text-slate-800 min-h-[50px]">
                            {{ $teacher_remarks ?? ($report->teacher_remarks ?? 'An outstanding performance throughout the academic term. Demonstrates great focus and high aptitude in all subject areas.') }}
                        </div>
                    </div>
                </div>

                <!-- 6. BOTTOM SIGNATURES & GRADING SCALE -->
                <div class="relative z-10 mt-8">
                    <!-- Issue Date & Signatures -->
                    <div class="grid grid-cols-4 gap-4 text-center text-xs font-bold text-slate-900 mb-6">
                        <div class="text-left">
                            <span class="block font-black">ISSUE ON:</span>
                            <span class="font-mono text-slate-700">
                                @if(isset($report) && is_object($report) && !empty($report->issue_date))
                                    {{ \Carbon\Carbon::parse($report->issue_date)->format('d/m/Y') }}
                                @elseif(isset($issue_date))
                                    {{ \Carbon\Carbon::parse($issue_date)->format('d/m/Y') }}
                                @else
                                    {{ now()->format('d/m/Y') }}
                                @endif
                            </span>
                        </div>
                        <div>
                            <div class="border-b-2 border-slate-900 mb-1 h-6"></div>
                            <span>Teacher Sign</span>
                        </div>
                        <div>
                            <div class="border-b-2 border-slate-900 mb-1 h-6"></div>
                            <span>Parents Sign</span>
                        </div>
                        <div>
                            <div class="border-b-2 border-slate-900 mb-1 h-6"></div>
                            <span>Principal Sign</span>
                        </div>
                    </div>

                    <!-- Grading Key Legend -->
                    <div class="border-2 border-slate-900 p-3 bg-slate-50 text-[11px] font-semibold text-slate-800 italic">
                        <div class="grid grid-cols-2 gap-x-8 gap-y-1">
                            @forelse($gradingScales as $scale)
                                <div><strong class="not-italic text-slate-900">{{ number_format($scale->minimum_percentage, 0) }}-{{ number_format($scale->maximum_percentage, 0) }}%:</strong> {{ $scale->grade }} · {{ $scale->remark }}</div>
                            @empty
                                <div class="col-span-2">No grading scale has been configured.</div>
                            @endforelse
                        </div>
                        @if($settings['footer'])<p class="mt-3 border-t border-slate-300 pt-2 text-center not-italic">{{ $settings['footer'] }}</p>@endif
                    </div>
                </div>

            </div>
        </div>
    </main>
    @else
        <div class="no-print mx-auto max-w-[210mm] rounded-2xl border border-dashed bg-white p-12 text-center text-sm text-slate-500">Select a term, student and examination to generate the report.</div>
    @endif
</div>
