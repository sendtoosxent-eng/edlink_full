@extends('layouts.platform',['title'=>'Platform Audit'])
@section('content')
<div class="space-y-4"><form><input name="search" value="{{ $search }}" placeholder="Filter audit events"></form>@foreach($logs as $log)<p>{{ $log->event }} · {{ $log->administrator?->name ?? 'System' }} · {{ $log->created_at }}</p>@endforeach{{ $logs->links() }}</div>
@endsection
