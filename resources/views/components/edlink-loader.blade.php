@props([
    'size' => 18,
    'fullscreen' => false,
    'title' => 'Edlink',
    'subtitle' => 'Loading...',
])

@if($fullscreen)
    @include('partials.page-skeleton')
@else
    <span {{ $attributes->class('edlink-inline-skeleton') }} style="width:{{ max(14, (int) $size) }}px;height:{{ max(14, (int) $size) }}px" role="status" aria-label="Loading">
        <span aria-hidden="true"></span><span aria-hidden="true"></span><span aria-hidden="true"></span>
    </span>
@endif
