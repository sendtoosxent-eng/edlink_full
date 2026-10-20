@extends('layouts.platform', ['title' => 'Platform notifications'])

@section('content')
<div class="space-y-6">
    <section class="rounded-3xl bg-slate-900 p-6 text-white"><h2 class="text-2xl font-black text-amber-300">Platform notifications</h2><p class="mt-2 text-sm text-slate-300">Technical health alerts for platform owners only. Unchanged warnings are grouped once per day.</p></section>
    <div class="space-y-3">
        @forelse($notifications as $notification)
            <article class="rounded-2xl border {{ $notification->read_at ? 'border-slate-200' : 'border-amber-300' }} bg-white p-5">
                <div class="flex flex-wrap items-start justify-between gap-3"><h3 class="font-bold text-slate-900">{{ $notification->title }}</h3><span class="text-xs text-slate-500">{{ $notification->created_at->format('d M Y · H:i') }}</span></div>
                <p class="mt-3 text-sm text-slate-600">{{ $notification->message }}</p>
                @if(!$notification->read_at)<form method="POST" action="{{ route('platform.notifications.read', $notification) }}" class="mt-4">@csrf @method('PATCH')<button class="rounded-xl bg-amber-400 px-4 py-2 text-xs font-bold text-slate-950">Mark as read</button></form>@else<p class="mt-4 text-xs text-slate-400">Read</p>@endif
            </article>
        @empty<p class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500">No platform notifications yet.</p>@endforelse
    </div>
    {{ $notifications->links() }}
</div>
@endsection
