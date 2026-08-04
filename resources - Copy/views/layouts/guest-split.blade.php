<!DOCTYPE html>
<html lang="en">
<head><link rel="icon" type="image/png" href="{{ asset('img/fav.png') }}"><link rel="apple-touch-icon" href="{{ asset('img/fav.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    @livewireStyles
</head>
<body class="font-sans bg-gray-50 min-h-screen">
@include('partials.global-loader')
    <div class="min-h-screen flex">

        <!-- Side image panel -->
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden" style="background-color:#252641;">
            
            <div class="absolute inset-0 flex flex-col justify-between p-12 z-10">
        <div>
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2">
            <img src="{{ asset('img/logo.png') }}" alt="Edlink logo" class="w-[180px] h-auto">
            </a>
        </div>

                <div class="max-w-md">
                    <h2 class="text-white text-3xl font-semibold leading-tight mb-4">Run your whole school from one dashboard.</h2>
                    <p class="text-gray-300">Admissions, attendance, fees, and report cards  all in one place, built for how schools actually run.</p>
                </div>

                <div class="flex items-center justify-center">
    <img src="{{ asset('img/login_img.png') }}" alt="" class="max-w-xl w-full">
</div>
            </div>
        </div>

        <!-- Form panel -->
        <div class="w-full lg:w-1/2 flex items-center justify-center px-6 py-10">
            <div class="w-full max-w-md">
                <div class="text-center mb-8 lg:hidden">
                    <a href="{{ url('/') }}" class="inline-flex items-center space-x-2">
                        <span class="w-9 h-9 rounded-lg bg-darken text-yellow-400 font-bold flex items-center justify-center">E</span>
                        <span class="text-darken font-semibold text-xl">Edlink</span>
                    </a>
                </div>

                <div class="bg-white lg:shadow-none shadow-xl rounded-2xl p-8 lg:p-0">
                    {{ $slot }}
                </div>

                <p class="text-center text-gray-400 text-sm mt-6">
                    &copy; <span x-data x-text="new Date().getFullYear()"></span> Edlink. Built by Spotnet Technologies.
                </p>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
