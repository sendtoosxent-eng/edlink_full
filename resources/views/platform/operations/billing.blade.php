@extends('layouts.platform', ['title' => 'Billing & Renewals'])
@section('content')
<h1>Billing & renewal operations</h1>
@foreach($schools as $school)<p>{{ $school->name }}</p>@endforeach
@endsection
