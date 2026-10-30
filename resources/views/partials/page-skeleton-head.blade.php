@once
<link rel="stylesheet" href="{{ asset('css/edlink-skeleton.css') }}?v={{ filemtime(public_path('css/edlink-skeleton.css')) }}">
<script>
    document.documentElement.classList.add('edlink-page-loading');
    window.setTimeout(function () { document.documentElement.classList.remove('edlink-page-loading'); }, 10000);
</script>
<script src="{{ asset('js/edlink-skeleton.js') }}?v={{ filemtime(public_path('js/edlink-skeleton.js')) }}" defer></script>
@endonce
