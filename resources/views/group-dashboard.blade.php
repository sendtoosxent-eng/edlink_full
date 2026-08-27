@extends('layouts.app', ['title' => 'Group Dashboard'])

@section('content')
@php
    $group = auth()->user()->school->group;
    $totalStudents = (int) $studentCounts->sum();
    $totalStaff = (int) $staffCounts->sum();
    $totalFees = (float) $payments->sum();
    $totalExpenses = (float) $expenses->sum();
    $netPosition = $totalFees - $totalExpenses;
    $todayAttendanceTotal = (int) $attendance->sum('total');
    $todayPresent = (int) $attendance->sum('present');
    $groupAttendanceRate = $todayAttendanceTotal ? round(($todayPresent / $todayAttendanceTotal) * 100, 1) : 0;
@endphp

<div class="space-y-8 pb-16">
    <section class="relative overflow-hidden rounded-3xl bg-[#252641] p-6 text-white shadow-xl ring-2 ring-yellow-400/20 sm:p-8">
        <div class="relative z-10 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="mt-4 text-2xl font-black sm:text-3xl text-amber-300">{{ $group->name }}</h1>
                <p class="mt-2 max-w-2xl text-sm text-slate-300">A consolidated view of every branch you are authorised to manage. Select a single branch before entering or changing operational data.</p>
                <p class="mt-4 text-xs font-bold uppercase tracking-widest text-yellow-300">{{ now()->format('l, d F Y') }}</p>
            </div>
            <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Group code</p>
                <p class="mt-1 text-xl font-black text-yellow-300">{{ $group->code }}</p>
            </div>
        </div>
        <div class="absolute -bottom-16 -right-10 h-52 w-52 rounded-full bg-yellow-400/10"></div>
        <div class="absolute -top-12 right-44 h-28 w-28 rounded-full bg-white/5"></div>
    </section>

    <section class="grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-2 ring-[#252641]/10"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Branches</p><p class="mt-3 text-3xl font-black text-[#252641]">{{ $schools->count() }}</p><p class="mt-1 text-xs text-slate-400">Authorised schools</p></div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-2 ring-[#252641]/10"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Active learners</p><p class="mt-3 text-3xl font-black text-[#252641]">{{ number_format($totalStudents) }}</p><p class="mt-1 text-xs text-slate-400">Across all branches</p></div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-2 ring-[#252641]/10"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Staff</p><p class="mt-3 text-3xl font-black text-[#252641]">{{ number_format($totalStaff) }}</p><p class="mt-1 text-xs text-slate-400">Across all branches</p></div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-2 ring-yellow-400/30"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Attendance today</p><p class="mt-3 text-3xl font-black text-[#252641]">{{ $groupAttendanceRate }}%</p><p class="mt-1 text-xs text-slate-400">{{ number_format($todayPresent) }} present of {{ number_format($todayAttendanceTotal) }} marked</p></div>
    </section>

    <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-2 ring-emerald-500/10"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Fees received</p><p class="mt-2 text-2xl font-black text-emerald-600">UGX {{ number_format($totalFees) }}</p><p class="mt-1 text-xs text-slate-400">All recorded payments</p></div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-2 ring-rose-500/10"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Expenses</p><p class="mt-2 text-2xl font-black text-rose-600">UGX {{ number_format($totalExpenses) }}</p><p class="mt-1 text-xs text-slate-400">All recorded expenses</p></div>
        <div class="rounded-2xl bg-slate-800 p-5 text-white shadow-sm"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Net position</p><p class="mt-2 text-2xl font-black {{ $netPosition >= 0 ? 'text-yellow-300' : 'text-rose-300' }}">UGX {{ number_format($netPosition) }}</p><p class="mt-1 text-xs text-slate-300">Fees received less expenses</p></div>
    </section>

    <section class="grid gap-6 xl:grid-cols-3">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-2 ring-[#252641]/10 xl:col-span-2">
            <div class="mb-5"><h2 class="font-black text-slate-900">Financial trend</h2><p class="text-xs text-slate-400">Fees and expenses across all branches for the last six months</p></div>
            <div class="h-80"><canvas id="groupFinanceChart"></canvas></div>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-2 ring-[#252641]/10">
            <div class="mb-5"><h2 class="font-black text-slate-900">Learner demographics</h2><p class="text-xs text-slate-400">Active learners across the group</p></div>
            <div class="h-80"><canvas id="groupGenderChart"></canvas></div>
        </div>
    </section>

    <section class="rounded-2xl bg-white p-5 shadow-sm ring-2 ring-yellow-400/20">
        <div class="mb-5"><h2 class="font-black text-slate-900">Attendance by branch</h2><p class="text-xs text-slate-400">Today’s present and late rate for each authorised branch</p></div>
        <div class="h-80"><canvas id="groupAttendanceChart"></canvas></div>
    </section>

    <section class="overflow-hidden rounded-2xl bg-white shadow-sm ring-2 ring-[#252641]/10">
        <div class="flex flex-col gap-2 border-b px-5 py-5 sm:flex-row sm:items-center sm:justify-between">
            <div><h2 class="font-black text-slate-900">Branch comparison</h2><p class="text-xs text-slate-400">Reporting is group-wide; operations open inside one selected branch.</p></div>
            <span class="w-fit rounded-full bg-yellow-100 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-yellow-800">Read only</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[880px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-5 py-3">Branch</th><th class="px-5 py-3">Learners</th><th class="px-5 py-3">Staff</th><th class="px-5 py-3">Fees</th><th class="px-5 py-3">Expenses</th><th class="px-5 py-3">Attendance</th><th class="px-5 py-3"></th></tr></thead>
                <tbody class="divide-y">
                    @foreach($schools as $school)
                        @php($daily = $attendance->get($school->id))
                        <tr class="hover:bg-slate-50/70">
                            <td class="px-5 py-4"><b class="text-slate-900">{{ $school->branch_name ?: $school->name }}</b><span class="block text-xs text-slate-400">{{ $school->school_number }}</span></td>
                            <td class="px-5 py-4">{{ number_format($studentCounts[$school->id] ?? 0) }}</td>
                            <td class="px-5 py-4">{{ number_format($staffCounts[$school->id] ?? 0) }}</td>
                            <td class="px-5 py-4 text-emerald-700">UGX {{ number_format($payments[$school->id] ?? 0) }}</td>
                            <td class="px-5 py-4 text-rose-700">UGX {{ number_format($expenses[$school->id] ?? 0) }}</td>
                            <td class="px-5 py-4">{{ $daily && $daily->total ? round(($daily->present / $daily->total) * 100, 1) : 0 }}%</td>
                            <td class="px-5 py-4">
                                <form method="POST" action="{{ route('branch-context.update') }}">@csrf @method('PUT')
                                    <input type="hidden" name="school_id" value="{{ $school->id }}">
                                    <button class="rounded-xl bg-[#252641] px-4 py-2 text-xs font-bold text-white transition hover:bg-yellow-500 hover:text-[#252641]">Manage branch</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>

<script src="{{ asset('js/chart.umd.js') }}"></script>
<script>
(() => {
    const money = value => 'UGX ' + Number(value).toLocaleString();
    const common = { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { usePointStyle: true, boxWidth: 8 } } } };

    new Chart(document.getElementById('groupFinanceChart'), {
        type: 'line',
        data: { labels: @json($monthlyLabels), datasets: [
            { label: 'Fees received', data: @json($monthlyFeeSeries), borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.12)', fill: true, tension: .35 },
            { label: 'Expenses', data: @json($monthlyExpenseSeries), borderColor: '#f43f5e', backgroundColor: 'rgba(244,63,94,.08)', fill: true, tension: .35 }
        ]},
        options: { ...common, scales: { y: { beginAtZero: true, ticks: { callback: money } }, x: { grid: { display: false } } } }
    });

    new Chart(document.getElementById('groupAttendanceChart'), {
        type: 'bar',
        data: { labels: @json($branchLabels), datasets: [{ label: 'Attendance %', data: @json($branchAttendanceRates), backgroundColor: '#facc15', borderRadius: 10 }] },
        options: { ...common, scales: { y: { beginAtZero: true, max: 100, ticks: { callback: value => value + '%' } }, x: { grid: { display: false } } } }
    });

    new Chart(document.getElementById('groupGenderChart'), {
        type: 'doughnut',
        data: { labels: ['Male', 'Female', 'Other / unspecified'], datasets: [{ data: @json($genderSeries), backgroundColor: ['#252641', '#facc15', '#cbd5e1'], borderWidth: 0 }] },
        options: { ...common, cutout: '68%' }
    });
})();
</script>
@endsection
