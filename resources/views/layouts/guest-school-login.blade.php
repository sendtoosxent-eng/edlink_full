<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#252641">
    <title>{{ match (true) { request()->routeIs('register') => 'Create your school workspace · Edlink', request()->routeIs('password.request') => 'Reset your password · Edlink', request()->routeIs('otp.verify') => 'Verify your account · Edlink', default => 'Sign in to Edlink' } }}</title>
    <link rel="icon" type="image/png" href="{{ asset('img/fav.png') }}">
    <link rel="preload" href="{{ asset('fonts/poppins/Poppins-Regular.ttf') }}" as="font" type="font/ttf" crossorigin>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config={theme:{extend:{fontFamily:{sans:['Poppins','sans-serif']},colors:{darken:'#252641'}}}}</script>
    @livewireStyles
    <link rel="stylesheet" href="{{ asset('css/edlink-login.css') }}?v={{ filemtime(public_path('css/edlink-login.css')) }}">
@include('partials.page-skeleton-head')
    <link rel="stylesheet" href="{{ asset('css/edlink-avatars.css') }}?v={{ filemtime(public_path('css/edlink-avatars.css')) }}">
@if(request()->routeIs('register','login'))
@include('partials.public-engagement-head')
@endif
</head>
<body class="school-auth-page {{ request()->routeIs('register') ? 'school-registration-page' : '' }}">
@include('partials.page-skeleton')

    <main class="school-auth-frame">
        <section class="school-auth-cover" aria-label="Edlink school community">
            @if(request()->routeIs('register'))
                <img src="{{ asset('img/edlink-registration-community.png') }}" alt="A joyful classroom community at a Ugandan school" width="1672" height="941" fetchpriority="high">
            @elseif(request()->routeIs('password.request', 'otp.verify'))
                <img src="{{ asset('img/edlink-account-support.png') }}" alt="A parent and teacher supporting a learner at a Ugandan school" width="1672" height="941" fetchpriority="high">
            @else
                <img src="{{ asset('img/edlink-login-community.png') }}" alt="Ugandan learners, teachers and parents celebrating learning together" width="1672" height="941" fetchpriority="high">
            @endif

        </section>
        <section class="school-auth-panel" aria-label="{{ match (true) { request()->routeIs('register') => 'School registration', request()->routeIs('password.request') => 'Account recovery', request()->routeIs('otp.verify') => 'Account verification', default => 'School sign in' } }}">
            <a href="{{ route('home') }}" class="school-auth-home" aria-label="Back to Edlink home"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m14 6-6 6 6 6"/></svg>Back to home</a>
            <div class="school-auth-content">
                <a href="{{ route('home') }}" class="school-auth-logo"><img src="{{ asset('img/logoneg.png') }}" alt="Edlink home" width="145" height="47"></a>
                @unless(request()->routeIs('register'))
                <div class="school-auth-intro"><span>One school. One community.</span><h2>Great school days start together.</h2><p>Learners, teachers and families. Connected by Edlink.</p></div>
                @endunless
                {{ $slot }}
                <div class="school-auth-legal"><a href="{{ route('terms') }}">Terms of service</a><span>·</span><a href="{{ route('privacy') }}">Privacy policy</a></div>
            </div>
            <p class="school-auth-copyright">&copy; {{ date('Y') }} Edlink · Spotnet Technologies</p>
        </section>
    </main>
    @livewireScripts
@if(request()->routeIs('register','login'))
@include('partials.public-consent')
@endif
</body>
</html>
