<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title') · Edlink Platform</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script><script>tailwind.config={theme:{extend:{fontFamily:{sans:['Poppins','sans-serif']},colors:{edlink:'#facc15',ink:'#0f172a'}}}}</script>
<style>body{font-family:Poppins,sans-serif}.grid-pattern{background-image:linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.035) 1px,transparent 1px);background-size:34px 34px}</style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<div class="grid min-h-screen lg:grid-cols-[1.05fr_.95fr]">
<aside class="grid-pattern relative hidden overflow-hidden bg-slate-950 p-12 text-white lg:flex lg:flex-col lg:justify-between">
<div class="absolute -left-32 top-20 h-80 w-80 rounded-full bg-yellow-400/10 blur-3xl"></div><div class="absolute -bottom-36 right-0 h-96 w-96 rounded-full bg-blue-500/10 blur-3xl"></div>
<a href="{{ url('/') }}" class="relative inline-flex items-center gap-3"><span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-yellow-400 font-black text-slate-950 shadow-lg shadow-yellow-400/20">E</span><span><b class="block text-xl">Edlink</b><small class="text-slate-400">Platform administration</small></span></a>
<div class="relative max-w-xl"><span class="inline-flex items-center gap-2 rounded-full border border-yellow-400/20 bg-yellow-400/10 px-3 py-1 text-xs font-bold text-yellow-300"><span class="h-1.5 w-1.5 rounded-full bg-yellow-300"></span>Restricted operations portal</span><h1 class="mt-6 text-4xl font-extrabold leading-tight xl:text-5xl">Secure control for the entire <span class="text-yellow-400">Edlink platform.</span></h1><p class="mt-5 max-w-lg text-sm leading-7 text-slate-400">Manage schools, licensing and public content through a separate, strongly authenticated administration boundary.</p>
<div class="mt-8 grid gap-3 sm:grid-cols-2"><div class="rounded-2xl border border-white/10 bg-white/5 p-4"><b class="text-sm">Authenticator protected</b><p class="mt-1 text-xs leading-5 text-slate-400">Time-based codes are generated locally on your registered device.</p></div><div class="rounded-2xl border border-white/10 bg-white/5 p-4"><b class="text-sm">Fully audited</b><p class="mt-1 text-xs leading-5 text-slate-400">Every login, recovery and sensitive platform action is recorded.</p></div></div></div>
<p class="relative text-xs text-slate-500">© {{ now()->year }} Edlink · Authorized administrators only</p>
</aside>
<main class="flex min-h-screen items-center justify-center p-5 sm:p-10"><div class="w-full max-w-md"><div class="mb-8 flex items-center gap-3 lg:hidden"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-yellow-400 font-black">E</span><div><b>Edlink</b><p class="text-xs text-slate-500">Platform administration</p></div></div>@yield('content')</div></main>
</div>
</body></html>