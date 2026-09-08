<!DOCTYPE html>
<html lang="en">
<head><link rel="icon" type="image/png" href="{{ asset('img/fav.png') }}"><link rel="apple-touch-icon" href="{{ asset('img/fav.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ $title ?? 'Edlink' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                    colors: { darken: '#252641' }
                }
            }
        }
    </script>
    <style>
        .auth-shell { min-height: 100vh; min-height: 100svh; grid-template-columns: minmax(0, 1fr); }
        .auth-form-panel, .auth-form-content { min-width: 0; }
        .auth-form-panel { min-height: 100vh; min-height: 100svh; }
        .auth-form-content { overflow-wrap: anywhere; }
        .auth-form-content input:not([type="checkbox"]):not([type="radio"]),
        .auth-form-content select, .auth-form-content textarea { min-width: 0; max-width: 100%; }
        @media (max-width: 639px) {
            .auth-shell .auth-form-panel {
                align-items: flex-start;
                padding: max(24px, env(safe-area-inset-top)) max(20px, env(safe-area-inset-right)) max(24px, env(safe-area-inset-bottom)) max(20px, env(safe-area-inset-left));
            }
        }
        @media (min-width: 1024px) {
            .auth-shell { grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr); }
        }
    </style>
    @livewireStyles
</head>
<body class="min-h-screen bg-white font-sans text-slate-800">
@include('partials.global-loader')
    <main class="auth-shell grid">
        <section class="relative hidden min-h-screen overflow-hidden bg-[#161327] lg:flex lg:flex-col">
            <div class="absolute -left-24 -top-24 h-80 w-80 rounded-full bg-yellow-400/15 blur-3xl"></div>
            <div class="absolute -right-24 top-1/3 h-72 w-72 rounded-full bg-teal-400/10 blur-3xl"></div>

            <div class="relative z-10 flex flex-1 flex-col px-10 pb-0 pt-9 xl:px-14 xl:pt-12">
                <a href="{{ url('/') }}" class="inline-flex w-fit items-center">
                    <img src="{{ asset('img/logo.png') }}" alt="Edlink home" class="h-auto w-36 xl:w-40">
                </a>

                <div class="mt-12 max-w-lg xl:mt-16">
                    <span class="inline-flex rounded-full border border-yellow-300/20 bg-yellow-400/10 px-3 py-1.5 text-[11px] font-bold uppercase tracking-[0.18em] text-yellow-300">Your school, connected</span>
                    <h2 class="mt-5 text-4xl font-extrabold leading-[1.12] text-white xl:text-5xl">Everything your school needs, in one clear workspace.</h2>
                    <p class="mt-5 max-w-md text-sm leading-7 text-slate-300 xl:text-base">Manage learners, attendance, fees, results, staff, and communication without juggling notebooks and spreadsheets.</p>
                </div>

                <div class="mt-7 grid max-w-lg grid-cols-2 gap-3 text-xs font-semibold text-slate-200">
                    <span class="rounded-xl border border-white/10 bg-white/5 px-3 py-2.5"><b class="mr-2 text-yellow-400">✓</b>Secure role access</span>
                    <span class="rounded-xl border border-white/10 bg-white/5 px-3 py-2.5"><b class="mr-2 text-yellow-400">✓</b>Phone &amp; desktop</span>
                </div>

                <div class="relative mt-auto flex min-h-0 flex-1 items-end justify-center pt-5">
                    <img src="{{ asset('img/login_img.png') }}" alt="School administrator working in Edlink" class="max-h-[43vh] w-full max-w-[570px] object-contain object-bottom xl:max-h-[47vh]">
                </div>
            </div>
        </section>

        <section class="auth-form-panel flex items-center justify-center px-4 py-8 sm:px-8 lg:px-12 xl:px-20">
            <div class="auth-form-content w-full max-w-[480px]">
                <div class="mb-6 flex items-center justify-between gap-3 sm:mb-7 lg:hidden">
                    <a href="{{ url('/') }}" class="inline-flex items-center">
                        <img src="{{ asset('img/logoneg.png') }}" alt="Edlink home" class="h-auto w-28 sm:w-36">
                    </a>
                    <span class="shrink-0 rounded-full bg-yellow-50 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-yellow-700">School portal</span>
                </div>

                <div class="min-w-0 bg-white sm:rounded-3xl sm:border sm:border-slate-100 sm:p-9 sm:shadow-xl sm:shadow-slate-200/50 lg:border-0 lg:p-0 lg:shadow-none">
                    {{ $slot }}
                </div>

                <p class="mt-6 text-center text-xs leading-5 text-slate-400 sm:mt-7">
                    &copy; <span x-data x-text="new Date().getFullYear()"></span> Edlink. Built by Spotnet Technologies.
                </p>
            </div>
        </section>
    </main>

    @livewireScripts
</body>
</html>
