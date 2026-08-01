@extends('layouts.platform',['title'=>'System Settings'])
@section('content')
<div><h2>System health</h2>@foreach($health as $label=>$value)<p>{{ $label }}: <b>{{ $value }}</b></p>@endforeach<form method="POST" action="{{ route('platform.settings.update') }}">@csrf @method('PUT')<input name="support_email" value="{{ $settings['support_email'] }}"><input name="renewal_warning_days" value="{{ $settings['renewal_warning_days'] }}"><textarea name="maintenance_message">{{ $settings['maintenance_message'] }}</textarea><button>Save settings</button></form></div>
@endsection
