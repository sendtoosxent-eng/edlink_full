@extends('layouts.platform',['title'=>'System Settings'])
@section('content')
<div class='space-y-6'>
    <header><h1 class='text-2xl font-black text-slate-900'>Operations & system health</h1><p class='mt-1 text-sm text-slate-500'>Live delivery, storage, queue, and recovery signals.</p></header>
    <section class='grid gap-4 sm:grid-cols-2 xl:grid-cols-4'>
        @foreach($health as $label=>$value)
            @php($problem=in_array($label,['Failed jobs','Pending jobs'])?(int)$value>0:in_array($value,['Unavailable','Never','Not configured'],true))
            <article class='rounded-2xl border bg-white p-5 shadow-sm {{ $problem ? 'border-rose-200' : 'border-slate-200' }}'>
                <p class='text-xs font-bold uppercase tracking-wide text-slate-400'>{{ $label }}</p>
                <p class='mt-2 font-black {{ $problem ? 'text-rose-700' : 'text-slate-900' }}'>{{ $value }}</p>
            </article>
        @endforeach
    </section>
    @if($lastBackup)
        <section class='rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-600'><b class='text-slate-900'>Latest backup:</b> {{ basename($lastBackup->path) }} · {{ number_format($lastBackup->size/1048576,2) }} MB · {{ ucfirst($lastBackup->status) }}</section>
    @endif
    <form method='POST' action='{{ route('platform.settings.update') }}' class='max-w-2xl space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm'>
        @csrf @method('PUT')
        <h2 class='font-bold text-slate-900'>Operational settings</h2>
        <div><label class='text-sm font-semibold'>Support email</label><input type='email' name='support_email' value='{{ old('support_email',$settings['support_email']) }}' class='mt-1 w-full rounded-xl border-slate-300'>@error('support_email')<p class='text-xs text-rose-600'>{{ $message }}</p>@enderror</div>
        <div><label class='text-sm font-semibold'>Renewal warning days</label><input type='number' min='1' max='180' name='renewal_warning_days' value='{{ old('renewal_warning_days',$settings['renewal_warning_days']) }}' class='mt-1 w-full rounded-xl border-slate-300'></div>
        <div><label class='text-sm font-semibold'>Maintenance message</label><textarea name='maintenance_message' class='mt-1 w-full rounded-xl border-slate-300'>{{ old('maintenance_message',$settings['maintenance_message']) }}</textarea></div>
        <button class='rounded-xl bg-amber-400 px-5 py-3 text-sm font-bold text-slate-950'>Save settings</button>
    </form>
</div>
@endsection
