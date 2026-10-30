@once
@php
    $skeletonAuth = request()->routeIs('login', 'register', 'password.*', 'otp.*', 'verification.*', 'platform.login', 'platform.challenge', 'platform.setup');
    $skeletonLanding = request()->routeIs('home');
@endphp
<div class="edlink-page-skeleton {{ $skeletonAuth ? 'skeleton-auth' : ($skeletonLanding ? 'skeleton-landing' : 'skeleton-dashboard') }}" aria-hidden="true">
    @if($skeletonAuth)
        <div class="sk-cover sk-block"></div>
        <div class="sk-auth-panel"><div class="sk-auth-content">
            <div class="sk-block sk-logo"></div><div class="sk-block sk-line sk-short"></div><div class="sk-block sk-heading"></div><div class="sk-block sk-line"></div>
            @foreach(range(1, request()->routeIs('otp.*') ? 1 : (request()->routeIs('password.*') ? 2 : 3)) as $row)
                <div class="sk-field"><div class="sk-block sk-label"></div><div class="sk-block sk-input"></div></div>
            @endforeach
            <div class="sk-block sk-button"></div><div class="sk-block sk-line sk-footer-line"></div>
        </div></div>
    @elseif($skeletonLanding)
        <div class="sk-nav"><div class="sk-block sk-logo"></div><div class="sk-block sk-nav-links"></div><div class="sk-block sk-nav-button"></div></div>
        <div class="sk-hero"><div class="sk-block sk-hero-person"></div><div class="sk-hero-copy"><div class="sk-block sk-line sk-short"></div><div class="sk-block sk-heading"></div><div class="sk-block sk-heading"></div><div class="sk-block sk-line"></div><div class="sk-block sk-line"></div><div class="sk-block sk-button"></div></div><div class="sk-block sk-hero-person"></div></div>
        <div class="sk-benefits">@foreach(range(1, 4) as $card)<div class="sk-block sk-benefit"></div>@endforeach</div>
    @else
        <div class="sk-sidebar"><div class="sk-block sk-logo"></div>@foreach(range(1, 9) as $row)<div class="sk-block sk-line"></div>@endforeach</div>
        <div class="sk-workspace"><div class="sk-toolbar"><div class="sk-block sk-heading"></div><div class="sk-block sk-avatar"></div></div><div class="sk-dashboard-content"><div class="sk-block sk-heading"></div><div class="sk-block sk-line sk-short"></div><div class="sk-metrics">@foreach(range(1, 4) as $card)<div class="sk-metric"><div class="sk-block sk-label"></div><div class="sk-block sk-heading"></div></div>@endforeach</div><div class="sk-table">@foreach(range(1, 7) as $row)<div class="sk-block sk-table-row"></div>@endforeach</div></div></div>
    @endif
</div>
<div class="edlink-loading-status" role="status" aria-live="polite"><span class="edlink-skeleton-sr">Loading Edlink…</span></div>
@endonce
