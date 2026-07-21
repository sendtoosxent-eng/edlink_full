<!DOCTYPE html>
<html lang="en">
<head>
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

    <header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
        <a href="{{ url('/') }}" class="inline-flex items-center space-x-2">
            <span class="w-9 h-9 rounded-lg bg-darken text-yellow-400 font-bold flex items-center justify-center">E</span>
            <span class="text-darken font-semibold text-lg">Edlink</span>
        </a>
        <a href="{{ route('dashboard') }}" wire:navigate class="text-sm text-gray-500 hover:text-darken flex items-center space-x-1">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            <span>Back to dashboard</span>
        </a>
    </header>

    <main class="max-w-2xl mx-auto px-6 py-10">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
