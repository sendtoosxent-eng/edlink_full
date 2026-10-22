<x-app-layout>
@php
    $input = 'mt-2 w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm font-medium text-slate-800 focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-400/20';
    $tabs = ['details' => 'Personal details', 'learners' => 'Linked learners', 'account' => 'Portal & account'];
    if ($canViewFinance) $tabs['finance'] = 'Learner fee summary';
    $initialTab = $errors->has('studentIds') || $errors->has('studentIds.*') ? 'learners' : ($errors->has('password') ? 'account' : (($canViewFinance && request('section') === 'finance') ? 'finance' : 'details'));
    $selectedIds = collect(old('sync_learners') ? old('studentIds', []) : $learners->pluck('id')->all())->map(fn ($id) => (string) $id);
    $photoUrl = $parent->avatarUrl();
@endphp
<div class="w-full space-y-6 pb-10" x-data="{ tab: @js($initialTab), photoPreview: null, learnerSearch: '' }">
    <header class="rounded-3xl bg-slate-900 p-6 text-white shadow-lg sm:p-8">
        <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
            <div class="flex h-40 w-40 shrink-0 items-center justify-center overflow-hidden rounded-full border-4 border-amber-300 bg-slate-800 text-6xl font-black text-amber-300 shadow-xl">
                <img x-show="photoPreview" x-cloak :src="photoPreview" alt="Parent photo preview" class="h-full w-full object-cover">
                <span x-show="!photoPreview" class="flex h-full w-full items-center justify-center">@if($photoUrl)<img src="{{ $photoUrl }}" alt="{{ $parent->name }} parent photograph" class="h-full w-full object-cover">@else{{ str($parent->name)->substr(0, 1)->upper() }}@endif</span>
            </div>
            <div class="flex-1"><p class="text-xs font-bold uppercase tracking-widest text-amber-300">Parent Profile</p><h1 class="mt-2 text-3xl font-black text-slate-100">{{ $parent->name }}</h1><p class="mt-2 text-sm text-slate-300">{{ $parent->email }}</p><p class="mt-1 text-sm text-slate-300">{{ $parent->phone ?: 'No phone recorded' }}</p><span class="mt-3 inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-bold text-amber-200">{{ $portalAvailable ? 'Portal access available' : 'Portal access unavailable' }}</span></div>
            <a href="{{ route('parents.index') }}" wire:navigate class="self-start rounded-xl border border-white/20 px-4 py-2.5 text-sm font-bold hover:bg-white/10">Back to parents</a>
        </div>
    </header>
    @if(session('status'))<div role="status" class="rounded-xl bg-emerald-50 p-4 text-emerald-800">{{ session('status') }}</div>@endif
    @if($errors->any())<div role="alert" class="rounded-xl bg-rose-50 p-4 text-rose-700">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border bg-white p-5"><p class="text-xs text-slate-500">Linked learners</p><p class="mt-2 text-2xl font-black">{{ $learners->count() }}</p></div>
        <div class="rounded-2xl border bg-white p-5"><p class="text-xs text-slate-500">Active linked learners</p><p class="mt-2 text-2xl font-black">{{ $learners->where('status', 'active')->count() }}</p></div>
        <div class="rounded-2xl border bg-white p-5"><p class="text-xs text-slate-500">Account created</p><p class="mt-2 text-lg font-bold">{{ $parent->created_at?->format('d M Y') ?: 'Not recorded' }}</p></div>
    </div>
    <nav aria-label="Parent profile sections" class="flex flex-wrap gap-2 rounded-2xl border border-slate-200 bg-white p-3">
        @foreach($tabs as $key => $label)<button type="button" @click="tab = @js($key)" :aria-selected="tab === @js($key)" :class="tab === @js($key) ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-50'" class="rounded-xl px-4 py-3 text-sm font-bold">{{ $label }}</button>@endforeach
    </nav>
    <form method="POST" action="{{ route('parents.profile.update', $parent) }}" enctype="multipart/form-data">
        @csrf @method('PATCH')
        <input type="hidden" name="sync_learners" value="1">
        <section x-show="tab === 'details'" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-black text-slate-900">Personal details</h2><p class="mt-1 text-xs text-slate-500">Contact information and the photograph used on this parent's account.</p>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <label class="text-xs font-bold text-slate-600">Full name<input name="name" required maxlength="255" value="{{ old('name', $parent->name) }}" class="{{ $input }}"></label>
                <label class="text-xs font-bold text-slate-600">Email address<input name="email" type="email" required value="{{ old('email', $parent->email) }}" class="{{ $input }}"></label>
                <label class="text-xs font-bold text-slate-600">Phone number<input name="phone" type="tel" maxlength="30" value="{{ old('phone', $parent->phone) }}" class="{{ $input }}"></label>
                <label class="text-xs font-bold text-slate-600">Parent photograph<input type="file" name="photo" accept="image/jpeg,image/png,image/webp" @change="if (photoPreview) URL.revokeObjectURL(photoPreview); photoPreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null" class="mt-2 block w-full rounded-xl border border-dashed border-slate-300 bg-slate-50 p-3 text-sm"><span class="mt-2 block text-xs font-normal text-slate-500">JPG, PNG or WebP, up to 4 MB.</span></label>
            </div>
        </section>
        <section x-show="tab === 'learners'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-black text-slate-900">Linked learners</h2><p class="mt-1 text-xs text-slate-500">These links control the learners available to this parent's account.</p>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                @forelse($learners as $student)<article class="flex items-center gap-4 rounded-2xl border border-slate-200 p-4">
                    <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100 font-black text-slate-500">@if($student->photoUrl())<img src="{{ $student->photoUrl() }}" alt="{{ $student->name }}" class="h-full w-full object-cover">@else{{ str($student->name)->substr(0,1)->upper() }}@endif</div>
                    <div><h3 class="font-bold text-slate-900">{{ $student->name }}</h3><p class="text-xs text-slate-500">{{ $student->admission_no }} · {{ $student->schoolClass?->name ?: 'No class' }}{{ $student->stream ? ' · '.$student->stream->name : '' }}</p><p class="mt-1 text-xs text-slate-500">{{ $student->pivot->relationship ?: 'Parent' }} · {{ ucfirst($student->status) }}</p>@if($profileStudentIds->contains($student->id))<a href="{{ route('students.profile', $student) }}" wire:navigate class="mt-2 inline-block text-xs font-bold text-amber-700">View learner profile</a>@endif</div>
                </article>@empty<p class="text-sm text-slate-500">No learners are linked to this parent yet.</p>@endforelse
            </div>
            <h3 class="mt-6 font-bold text-slate-900">Manage learner links</h3><p class="mt-1 text-xs text-slate-500">Select learners and save. Unchecking a learner removes that learner from the parent's portal access.</p>
            <input type="search" x-model="learnerSearch" placeholder="Search learners by name or admission number" aria-label="Search learners" class="{{ $input }}">
            <div class="mt-4 grid max-h-80 gap-3 overflow-y-auto sm:grid-cols-2">
                @forelse($availableStudents as $student)<label x-show="!learnerSearch || @js(strtolower($student->name.' '.$student->admission_no)).includes(learnerSearch.toLowerCase())" class="flex items-start gap-3 rounded-xl border border-slate-200 p-3 text-sm"><input type="checkbox" name="studentIds[]" value="{{ $student->id }}" @checked($selectedIds->contains((string) $student->id)) class="mt-1 rounded border-slate-300 text-amber-500"><span><strong>{{ $student->name }}</strong><span class="block text-xs text-slate-500">{{ $student->admission_no }} · {{ $student->schoolClass?->name ?: 'No class' }} · {{ ucfirst($student->status) }}</span></span></label>@empty<p class="text-sm text-slate-500">No learners have been registered in this school.</p>@endforelse
            </div>
        </section>
        <section x-show="tab === 'account'" x-cloak class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-black text-slate-900">Portal & account</h2>
            <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2"><div><dt class="text-xs text-slate-500">Login email</dt><dd class="mt-1 font-bold">{{ $parent->email }}</dd></div><div><dt class="text-xs text-slate-500">Portal access</dt><dd class="mt-1 font-bold">{{ $portalAvailable ? 'Available' : 'Unavailable' }}</dd></div></dl>
            <p class="mt-4 rounded-xl bg-amber-50 p-3 text-xs text-amber-900">Portal login requires an active account with at least one active linked learner. School access and verification rules still apply.</p>
            <label class="mt-5 block text-xs font-bold text-slate-600">New password (optional)<input type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Leave blank to keep the current password" class="{{ $input }}"></label>
        </section>
        <div x-show="tab !== 'finance'" class="mt-4 flex justify-end"><button type="submit" class="rounded-xl bg-amber-400 px-6 py-3 text-sm font-bold text-slate-950 hover:bg-amber-300">Save parent profile</button></div>
    </form>
    @if($canViewFinance)
    <section x-show="tab === 'finance'" x-cloak class="space-y-5 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <h2 class="text-lg font-black text-slate-900">Learner fee summary</h2><p class="text-xs text-slate-500">Balances belong to the linked learners. Payments below are recorded against those learners and may have been made by any payer.</p>
        <form method="GET" action="{{ route('parents.profile', $parent) }}" class="flex flex-wrap items-end gap-3"><input type="hidden" name="section" value="finance"><label class="text-xs font-bold text-slate-600">Academic term<select name="term" class="{{ $input }}">@foreach($terms as $option)<option value="{{ $option->id }}" @selected($term?->id === $option->id)>{{ $option->name }} · {{ $option->year }}</option>@endforeach</select></label><button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white">Apply</button></form>
        @if($term)
        <div class="grid gap-3 sm:grid-cols-3">@foreach(['due'=>'Total due','paid'=>'Posted payments','balance'=>'Combined balance'] as $key=>$label)<div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">{{ $label }}</p><p class="mt-2 font-mono text-lg font-bold">{{ $currency }} {{ number_format($balances->sum($key),2) }}</p></div>@endforeach</div>
        <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs text-slate-500"><tr><th class="p-3">Learner</th><th class="p-3">Due</th><th class="p-3">Paid</th><th class="p-3">Balance</th></tr></thead><tbody>@forelse($balances as $row)<tr class="border-t"><td class="p-3 font-bold">{{ $row['student']->name }}</td>@foreach(['due','paid','balance'] as $key)<td class="p-3 font-mono">{{ $currency }} {{ number_format($row[$key],2) }}</td>@endforeach</tr>@empty<tr><td colspan="4" class="p-4 text-slate-500">No linked learners.</td></tr>@endforelse</tbody></table></div>
        <h3 class="font-bold text-slate-900">Recent posted payments</h3><div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs text-slate-500"><tr><th class="p-3">Paid on</th><th class="p-3">Learner</th><th class="p-3">Method</th><th class="p-3">Amount</th></tr></thead><tbody>@forelse($payments as $payment)<tr class="border-t"><td class="p-3">{{ $payment->paid_at?->format('d M Y') }}</td><td class="p-3">{{ $payment->student?->name }}</td><td class="p-3">{{ ucfirst($payment->method) }}</td><td class="p-3 font-mono">{{ $currency }} {{ number_format($payment->amount,2) }}</td></tr>@empty<tr><td colspan="4" class="p-4 text-slate-500">No posted payments in this term.</td></tr>@endforelse</tbody></table></div>
        @else<p class="text-sm text-slate-500">Create an academic term to view learner balances.</p>@endif
    </section>
    @endif
</div>
</x-app-layout>
