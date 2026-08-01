<x-app-layout>
    <div class="mx-auto max-w-6xl space-y-6 p-6">
        <div><h1 class="text-2xl font-black">Privacy requests</h1><p class="text-sm text-slate-500">Verified exports and controlled anonymisation. Accounting and audit evidence is retained.</p></div>
        @if(session('status'))<div class="rounded-xl bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div>@endif
        <form method="POST" action="{{ route('privacy.requests.store') }}" class="grid gap-3 rounded-2xl bg-white p-5 shadow md:grid-cols-2">@csrf
            <select name="type" class="rounded-xl border-slate-300"><option value="export">Data export</option><option value="deletion">Deletion/anonymisation</option></select>
            <select name="student_id" class="rounded-xl border-slate-300"><option value="">Entire school export</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->name }} — {{ $student->admission_no }}</option>@endforeach</select>
            <textarea name="reason" required placeholder="Reason and authority for this request" class="rounded-xl border-slate-300"></textarea>
            <input type="password" name="password" required placeholder="Confirm your password" class="rounded-xl border-slate-300">
            <button class="rounded-xl bg-slate-900 px-4 py-2 font-bold text-white md:col-span-2">Verify request</button>
        </form>
        <div class="space-y-3">@foreach($requests as $item)<div class="rounded-2xl bg-white p-4 shadow"><div class="flex justify-between"><strong>#{{ $item->id }} {{ ucfirst($item->request_type) }}</strong><span>{{ ucfirst($item->status) }}</span></div><p class="text-sm text-slate-500">{{ $item->reason }}</p>@if($item->status==='verified')<form method="POST" action="{{ route('privacy.requests.execute',$item) }}" class="mt-3 flex gap-2">@csrf<input name="confirmation_code" required placeholder="Confirmation code" class="rounded-xl border-slate-300"><button class="rounded-xl bg-red-700 px-4 text-white">Execute</button></form>@endif</div>@endforeach</div>{{ $requests->links() }}
    </div>
</x-app-layout>
