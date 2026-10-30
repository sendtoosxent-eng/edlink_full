@once
<meta name="edlink-engagement-csrf" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('css/edlink-engagement.css') }}?v={{ filemtime(public_path('css/edlink-engagement.css')) }}">
<script src="{{ asset('js/edlink-engagement.js') }}?v={{ filemtime(public_path('js/edlink-engagement.js')) }}" defer></script>
@endonce
