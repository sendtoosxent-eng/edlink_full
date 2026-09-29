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
<body class="font-sans bg-gray-50 min-h-screen flex items-center justify-center px-4 py-10">
@include('partials.global-loader')
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div>
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2 ">
            <img src="{{ asset('img/logo.png') }}" alt="Edlink logo" class="w-[180px] h-auto">
            </a>
        </div>
        </div>

        <div class="bg-white shadow-xl rounded-2xl p-8">
            {{ $slot }}
        </div>

        <p class="text-center text-gray-400 text-sm mt-6">
            &copy; <span x-data x-text="new Date().getFullYear()"></span> Edlink. Built by Spotnet Technologies.
        </p>
    </div>

    @livewireScripts
</body>
</html>
