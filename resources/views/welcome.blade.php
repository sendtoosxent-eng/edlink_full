<!DOCTYPE html>
<html lang="en" x-data="{ dark: localStorage.getItem('edlink_theme') === 'dark' || (!localStorage.getItem('edlink_theme') && window.matchMedia('(prefers-color-scheme: dark)').matches) }"
      x-init="$watch('dark', v => { localStorage.setItem('edlink_theme', v ? 'dark' : 'light'); document.documentElement.classList.toggle('dark', v); }); document.documentElement.classList.toggle('dark', dark);"
      :class="{ 'dark': dark }">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{{ $landing['site_title'] }}</title>
	<script src="https://cdn.tailwindcss.com"></script>
	<script>
		tailwind.config = {
			darkMode: 'class',
			theme: {
				extend: {
					fontFamily: { sans: ['Poppins', 'sans-serif'] },
					colors: { darken: '#161327', ink: '#0f0d1f' },
					animation: {
						'blob': 'blob 12s infinite',
						'blob-slow': 'blob 18s infinite',
						'float': 'float 6s ease-in-out infinite',
						'float-delay': 'float 6s ease-in-out 2s infinite',
						'gradient-x': 'gradient-x 6s ease infinite',
					},
					keyframes: {
						blob: {
							'0%, 100%': { transform: 'translate(0px, 0px) scale(1)' },
							'33%': { transform: 'translate(30px, -40px) scale(1.1)' },
							'66%': { transform: 'translate(-20px, 20px) scale(0.95)' },
						},
						float: {
							'0%, 100%': { transform: 'translateY(0px)' },
							'50%': { transform: 'translateY(-14px)' },
						},
						'gradient-x': {
							'0%, 100%': { 'background-position': '0% 50%' },
							'50%': { 'background-position': '100% 50%' },
						},
					},
				}
			}
		}
	</script>
	<link rel="preconnect" href="https://fonts.gstatic.com">
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
	<script src="https://unpkg.com/aos@next/dist/aos.js"></script>
	<link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
	<style>
		[x-cloak] { display: none !important; }
		html { scroll-behavior: smooth; }
		body { font-family: 'Poppins', sans-serif; }
		.text-gradient {
			background: linear-gradient(90deg, #facc15, #fb923c, #facc15);
			background-size: 200% auto;
			-webkit-background-clip: text;
			background-clip: text;
			color: transparent;
			animation: gradient-x 4s ease infinite;
		}
		.glass {
			backdrop-filter: blur(16px);
			-webkit-backdrop-filter: blur(16px);
		}
		.grid-fade {
			background-image:
				linear-gradient(to right, rgba(255,255,255,0.06) 1px, transparent 1px),
				linear-gradient(to bottom, rgba(255,255,255,0.06) 1px, transparent 1px);
			background-size: 44px 44px;
			-webkit-mask-image: radial-gradient(ellipse 70% 60% at 50% 0%, black 40%, transparent 100%);
			mask-image: radial-gradient(ellipse 70% 60% at 50% 0%, black 40%, transparent 100%);
		}
		.chat-scroll::-webkit-scrollbar { width: 5px; }
		.chat-scroll::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 10px; }
		.dark .chat-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); }
	</style>
</head>
<body class="antialiased bg-white dark:bg-ink text-gray-700 dark:text-gray-300 transition-colors duration-300">

	<!-- ============ NAVBAR ============ -->
	<div x-data="{ open: false }" class="fixed top-0 inset-x-0 z-40 glass bg-white/70 dark:bg-ink/70 border-b border-gray-100 dark:border-white/10">
		<div class="max-w-7xl mx-auto px-6 flex items-center justify-between h-16">
			<a href="#" class="flex items-center space-x-2">
			
				<span class="font-bold text-darken dark:text-white tracking-tight">
					<img src="{{ \App\Models\LandingPageSetting::assetUrl($landing,'nav_logo') }}" class="h-12" alt="Edlink logo">
				</span>
			</a>

			<nav class="hidden md:flex items-center space-x-8 text-sm font-medium">
				<a href="#features" class="hover:text-yellow-500 transition-colors">Features</a>
				<a href="#pricing" class="hover:text-yellow-500 transition-colors">Pricing</a>
				<a href="#about" class="hover:text-yellow-500 transition-colors">About</a>
				<a href="#contact" class="hover:text-yellow-500 transition-colors">Contact</a>
			</nav>

			<div class="flex items-center space-x-3">
				<!-- Theme toggle -->
				<button @click="dark = !dark" class="w-9 h-9 rounded-full flex items-center justify-center border border-gray-200 dark:border-white/10 hover:border-yellow-400 transition-colors">
					<svg x-show="!dark" class="w-4 h-4 text-darken" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
					<svg x-show="dark" x-cloak class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 24 24"><path d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 008.998-5.998z"/></svg>
				</button>

				<a href="{{ route('login') }}" wire:navigate class="hidden sm:inline-block px-5 py-2 text-sm font-medium rounded-full border border-gray-200 dark:border-white/15 hover:border-yellow-400 transition-colors">Login</a>
				<a href="{{ route('register') }}" wire:navigate class="px-5 py-2 text-sm font-semibold rounded-full bg-gradient-to-r from-yellow-400 to-orange-500 text-darken shadow-lg shadow-yellow-500/30 hover:shadow-yellow-500/50 hover:scale-105 transition-all">Try free demo</a>

				<button @click="open = !open" class="md:hidden w-9 h-9 flex items-center justify-center">
					<svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path x-show="!open" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /><path x-show="open" x-cloak stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
				</button>
			</div>
		</div>
		<div x-show="open" x-cloak x-transition class="md:hidden px-6 pb-4 flex flex-col space-y-3 text-sm font-medium border-t border-gray-100 dark:border-white/10 pt-3">
			<a href="#features" @click="open=false">Features</a>
			<a href="#pricing" @click="open=false">Pricing</a>
			<a href="#about" @click="open=false">About</a>
			<a href="#contact" @click="open=false">Contact</a>
		</div>
	</div>

	<!-- ============ HERO ============ -->
	<section class="relative pt-40 pb-32 overflow-hidden bg-gradient-to-b from-white via-yellow-50/40 to-white dark:from-ink dark:via-darken dark:to-ink">
		<!-- animated blobs -->
		<div class="absolute inset-0 overflow-hidden pointer-events-none">
			<div class="absolute -top-20 -left-20 w-96 h-96 bg-yellow-300/40 dark:bg-yellow-500/10 rounded-full mix-blend-multiply dark:mix-blend-screen filter blur-3xl animate-blob"></div>
			<div class="absolute top-40 -right-10 w-96 h-96 bg-purple-300/40 dark:bg-purple-600/10 rounded-full mix-blend-multiply dark:mix-blend-screen filter blur-3xl animate-blob-slow"></div>
			<div class="absolute bottom-0 left-1/3 w-96 h-96 bg-teal-300/30 dark:bg-teal-500/10 rounded-full mix-blend-multiply dark:mix-blend-screen filter blur-3xl animate-blob"></div>
			<div class="absolute inset-0 grid-fade"></div>
		</div>

		<div class="relative max-w-7xl mx-auto px-6 grid lg:grid-cols-2 gap-16 items-center">
			<div data-aos="fade-right" data-aos-duration="800">
				<span class="inline-flex items-center space-x-2 px-4 py-1.5 rounded-full bg-white/80 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-xs font-semibold text-darken dark:text-yellow-400 shadow-sm mb-6">
					<span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
					<span>{{ $landing['announcement'] }}</span>
				</span>

				<h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-[1.1] text-darken dark:text-white">
					{{ $landing['hero_title'] }} <span class="text-gradient">{{ $landing['hero_highlight'] }}</span> {{ $landing['hero_title_suffix'] }}
				</h1>
				<p class="mt-6 text-lg text-gray-500 dark:text-gray-400 max-w-lg">
					{{ $landing['hero_description'] }}
				</p>

				<div class="mt-9 flex flex-wrap items-center gap-4">
					<a href="{{ route('register') }}" wire:navigate class="group px-8 py-4 rounded-full bg-gradient-to-r from-yellow-400 to-orange-500 text-darken font-bold shadow-xl shadow-yellow-500/30 hover:shadow-yellow-500/50 hover:scale-105 transition-all flex items-center space-x-2">
						<span>{{ $landing['primary_cta'] }}</span>
						<svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
					</a>
					<a href="#features" class="px-8 py-4 rounded-full border border-gray-200 dark:border-white/15 font-semibold hover:border-yellow-400 transition-colors">{{ $landing['secondary_cta'] }}</a>
				</div>

				<div class="mt-10 flex items-center gap-8 text-sm">
					<div><span class="text-2xl font-extrabold text-darken dark:text-white">{{ $landing['stat_one_value'] }}</span><p class="text-gray-400">{{ $landing['stat_one_label'] }}</p></div>
					<div class="w-px h-8 bg-gray-200 dark:bg-white/10"></div>
					<div><span class="text-2xl font-extrabold text-darken dark:text-white">{{ $landing['stat_two_value'] }}</span><p class="text-gray-400">{{ $landing['stat_two_label'] }}</p></div>
					<div class="w-px h-8 bg-gray-200 dark:bg-white/10"></div>
					<div><span class="text-2xl font-extrabold text-darken dark:text-white">{{ $landing['stat_three_value'] }}</span><p class="text-gray-400">{{ $landing['stat_three_label'] }}</p></div>
				</div>
			</div>

			<div data-aos="fade-left" data-aos-duration="800" class="relative">
				<div class="absolute -inset-6 bg-gradient-to-tr from-yellow-300/30 via-purple-300/20 to-teal-300/30 dark:from-yellow-500/10 dark:via-purple-500/10 dark:to-teal-500/10 rounded-[2.5rem] blur-2xl"></div>
				<div class="relative rounded-[2rem] overflow-hidden shadow-2xl border border-white/50 dark:border-white/10">
					<img src="{{ \App\Models\LandingPageSetting::assetUrl($landing,'hero_image') }}" class="w-full object-cover" alt="Student using Edlink">
				</div>

				<!-- floating stat card -->
				<div class="absolute -bottom-6 -left-6 animate-float glass bg-white/90 dark:bg-darken/90 rounded-2xl shadow-xl border border-gray-100 dark:border-white/10 px-5 py-4">
					<p class="text-xs text-gray-400">Attendance today</p>
					<p class="text-xl font-extrabold text-darken dark:text-white">96.4%</p>
					<div class="mt-1 flex space-x-0.5">
						<span class="w-1.5 h-4 bg-teal-400 rounded-full"></span>
						<span class="w-1.5 h-5 bg-teal-400 rounded-full"></span>
						<span class="w-1.5 h-3 bg-teal-300 rounded-full"></span>
						<span class="w-1.5 h-6 bg-teal-400 rounded-full"></span>
					</div>
				</div>

				<!-- floating badge -->
				<div class="absolute -top-6 -right-6 animate-float-delay glass bg-white/90 dark:bg-darken/90 rounded-2xl shadow-xl border border-gray-100 dark:border-white/10 px-4 py-3 flex items-center space-x-2">
					<span class="w-8 h-8 rounded-lg bg-yellow-400/20 flex items-center justify-center">
						<svg class="w-4 h-4 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
					</span>
					<span class="text-xs font-semibold text-darken dark:text-white">Fees auto-mapped</span>
				</div>
			</div>
		</div>
	</section>

	<!-- ============ LOGOS / TRUST BAR ============ -->
	<section class="py-10 border-y border-gray-100 dark:border-white/10 bg-gray-50/60 dark:bg-white/[0.02]">
		<p class="text-center text-xs uppercase tracking-widest text-gray-400 font-semibold">{{ $landing['trust_text'] }}</p>
	</section>

	<!-- ============ BENTO FEATURES ============ -->
	<section id="features" class="py-28 max-w-7xl mx-auto px-6">
		<div class="text-center max-w-2xl mx-auto mb-16" data-aos="fade-up">
			<span class="text-xs font-bold tracking-widest text-yellow-500 uppercase">What's inside</span>
			<h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-darken dark:text-white">{{ $landing['features_heading'] }}</h2>
		</div>

		<div class="grid md:grid-cols-6 gap-5">
			<!-- big card -->
			<div data-aos="fade-up" class="md:col-span-4 md:row-span-2 rounded-3xl p-8 bg-gradient-to-br from-darken to-[#2a2560] text-white relative overflow-hidden group">
				<div class="absolute -right-10 -bottom-10 w-56 h-56 bg-yellow-400/10 rounded-full group-hover:scale-110 transition-transform duration-700"></div>
				<span class="inline-flex w-11 h-11 rounded-xl bg-yellow-400 items-center justify-center mb-6">
					<svg class="w-5 h-5 text-darken" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0121 17.5" /></svg>
				</span>
				<h3 class="text-2xl font-bold mb-3">Students, classes &amp; streams</h3>
				<p class="text-gray-300 max-w-md">Admissions, class streams, categories, and full academic history in one profile per learner ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â with fees auto-mapped the moment a student is assigned.</p>
				<img src="{{ \App\Models\LandingPageSetting::assetUrl($landing,'feature_image') }}" class="mt-8 rounded-xl w-full max-w-md relative z-10" alt="">
			</div>

			<!-- small cards -->
			<div data-aos="fade-up" data-aos-delay="100" class="md:col-span-2 rounded-3xl p-7 bg-teal-50 dark:bg-teal-500/10 hover:-translate-y-1 transition-transform">
				<span class="inline-flex w-10 h-10 rounded-xl bg-teal-500 items-center justify-center mb-5">
					<svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
				</span>
				<h3 class="font-bold text-darken dark:text-white mb-2">Attendance</h3>
				<p class="text-sm text-gray-500 dark:text-gray-400">Mark a class register in under a minute, with automatic parent alerts.</p>
			</div>

			<div data-aos="fade-up" data-aos-delay="150" class="md:col-span-2 rounded-3xl p-7 bg-purple-50 dark:bg-purple-500/10 hover:-translate-y-1 transition-transform">
				<span class="inline-flex w-10 h-10 rounded-xl bg-purple-500 items-center justify-center mb-5">
					<svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V6m0 10v2m9-8a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
				</span>
				<h3 class="font-bold text-darken dark:text-white mb-2">Fees &amp; arrears</h3>
				<p class="text-sm text-gray-500 dark:text-gray-400">Per-term fee structures with automatic arrears rollover on term close.</p>
			</div>

			<div data-aos="fade-up" data-aos-delay="200" class="md:col-span-3 rounded-3xl p-7 bg-orange-50 dark:bg-orange-500/10 hover:-translate-y-1 transition-transform">
				<span class="inline-flex w-10 h-10 rounded-xl bg-orange-500 items-center justify-center mb-5">
					<svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 014-4h4M9 17H7a2 2 0 01-2-2V7a2 2 0 012-2h6l4 4v6a2 2 0 01-2 2h-2" /></svg>
				</span>
				<h3 class="font-bold text-darken dark:text-white mb-2">Exams &amp; report cards</h3>
				<p class="text-sm text-gray-500 dark:text-gray-400">Enter marks and generate report cards formatted the way parents expect.</p>
			</div>

			<div data-aos="fade-up" data-aos-delay="250" class="md:col-span-3 rounded-3xl p-7 bg-pink-50 dark:bg-pink-500/10 hover:-translate-y-1 transition-transform">
				<span class="inline-flex w-10 h-10 rounded-xl bg-pink-500 items-center justify-center mb-5">
					<svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" /></svg>
				</span>
				<h3 class="font-bold text-darken dark:text-white mb-2">Announcements</h3>
				<p class="text-sm text-gray-500 dark:text-gray-400">Reach one class or the whole school by SMS or in-app notice.</p>
			</div>
		</div>
	</section>

	<!-- ============ ABOUT ============ -->
	<section id="about" class="py-24 relative overflow-hidden">
		<div class="max-w-7xl mx-auto px-6 grid md:grid-cols-2 gap-14 items-center">
			<div data-aos="fade-right" class="relative">
				<div class="absolute -inset-4 bg-gradient-to-tr from-teal-300/30 to-yellow-300/30 dark:from-teal-500/10 dark:to-yellow-500/10 rounded-[2rem] blur-xl"></div>
				<img src="{{ \App\Models\LandingPageSetting::assetUrl($landing,'about_image') }}" class="relative rounded-[2rem] shadow-xl w-full" alt="">
			</div>
			<div data-aos="fade-left">
				<span class="text-xs font-bold tracking-widest text-yellow-500 uppercase">About us</span>
				<h2 class="mt-3 text-3xl font-extrabold text-darken dark:text-white">{{ $landing['about_heading'] }}</h2>
				<p class="mt-5 text-gray-500 dark:text-gray-400">{{ $landing['about_text'] }}</p>
				<p class="mt-4 text-gray-500 dark:text-gray-400">{{ $landing['about_text_two'] }}</p>
			</div>
		</div>
	</section>

	<!-- ============ PRICING ============ -->
	<section id="pricing" class="py-28 bg-gray-50/60 dark:bg-white/[0.02]">
		<div class="max-w-7xl mx-auto px-6">
			<div class="text-center max-w-2xl mx-auto mb-16" data-aos="fade-up">
				<span class="text-xs font-bold tracking-widest text-yellow-500 uppercase">Pricing</span>
				<h2 class="mt-3 text-3xl sm:text-4xl font-extrabold text-darken dark:text-white">{{ $landing['pricing_heading'] }}</h2>
			</div>

			<div class="grid md:grid-cols-3 gap-8 items-stretch">
				<div data-aos="fade-up" class="rounded-3xl p-8 bg-white dark:bg-darken border border-gray-100 dark:border-white/10 shadow-sm flex flex-col hover:-translate-y-1 transition-transform">
					<h3 class="font-bold text-lg text-darken dark:text-white">Starter</h3>
					<p class="text-sm text-gray-400 mt-1">For a single small school</p>
					<p class="mt-6 text-3xl font-extrabold text-darken dark:text-white">UGX 150k <span class="text-sm font-medium text-gray-400">/ term</span></p>
					<ul class="mt-6 space-y-3 text-sm text-gray-500 dark:text-gray-400 flex-grow">
						<li class="flex items-center space-x-2"><span class="text-teal-500 font-bold">ÃƒÂ¢Ã…â€œÃ¢â‚¬Å“</span><span>Students &amp; attendance</span></li>
						<li class="flex items-center space-x-2"><span class="text-teal-500 font-bold">ÃƒÂ¢Ã…â€œÃ¢â‚¬Å“</span><span>Fees tracking</span></li>
						<li class="flex items-center space-x-2"><span class="text-teal-500 font-bold">ÃƒÂ¢Ã…â€œÃ¢â‚¬Å“</span><span>Report cards</span></li>
					</ul>
					<a href="{{ route('register') }}" wire:navigate class="mt-8 text-center py-3 rounded-full border border-gray-200 dark:border-white/15 font-semibold hover:border-yellow-400 transition-colors">Start free demo</a>
				</div>

				<div data-aos="fade-up" data-aos-delay="100" class="rounded-3xl p-8 bg-gradient-to-br from-darken to-[#2a2560] text-white shadow-2xl flex flex-col relative md:-translate-y-4">
					<span class="absolute -top-3 left-8 bg-gradient-to-r from-yellow-400 to-orange-500 text-darken text-xs font-bold px-4 py-1 rounded-full shadow-lg">MOST POPULAR</span>
					<h3 class="font-bold text-lg">Growth</h3>
					<p class="text-sm text-gray-300 mt-1">For most primary &amp; secondary schools</p>
					<p class="mt-6 text-3xl font-extrabold">UGX 350k <span class="text-sm font-medium text-gray-300">/ term</span></p>
					<ul class="mt-6 space-y-3 text-sm text-gray-200 flex-grow">
						<li class="flex items-center space-x-2"><span class="text-yellow-400 font-bold">ÃƒÂ¢Ã…â€œÃ¢â‚¬Å“</span><span>Everything in Starter</span></li>
						<li class="flex items-center space-x-2"><span class="text-yellow-400 font-bold">ÃƒÂ¢Ã…â€œÃ¢â‚¬Å“</span><span>SMS announcements</span></li>
						<li class="flex items-center space-x-2"><span class="text-yellow-400 font-bold">ÃƒÂ¢Ã…â€œÃ¢â‚¬Å“</span><span>Multi-branch support</span></li>
					</ul>
					<a href="{{ route('register') }}" wire:navigate class="mt-8 text-center py-3 rounded-full bg-gradient-to-r from-yellow-400 to-orange-500 text-darken font-bold hover:scale-105 transition-transform">Start free demo</a>
				</div>

				<div data-aos="fade-up" data-aos-delay="200" class="rounded-3xl p-8 bg-white dark:bg-darken border border-gray-100 dark:border-white/10 shadow-sm flex flex-col hover:-translate-y-1 transition-transform">
					<h3 class="font-bold text-lg text-darken dark:text-white">Enterprise</h3>
					<p class="text-sm text-gray-400 mt-1">For school groups &amp; large institutions</p>
					<p class="mt-6 text-3xl font-extrabold text-darken dark:text-white">Custom</p>
					<ul class="mt-6 space-y-3 text-sm text-gray-500 dark:text-gray-400 flex-grow">
						<li class="flex items-center space-x-2"><span class="text-teal-500 font-bold">ÃƒÂ¢Ã…â€œÃ¢â‚¬Å“</span><span>Everything in Growth</span></li>
						<li class="flex items-center space-x-2"><span class="text-teal-500 font-bold">ÃƒÂ¢Ã…â€œÃ¢â‚¬Å“</span><span>Dedicated onboarding</span></li>
						<li class="flex items-center space-x-2"><span class="text-teal-500 font-bold">ÃƒÂ¢Ã…â€œÃ¢â‚¬Å“</span><span>SLA-backed support</span></li>
					</ul>
					<a href="#contact" class="mt-8 text-center py-3 rounded-full border border-gray-200 dark:border-white/15 font-semibold hover:border-yellow-400 transition-colors">Talk to us</a>
				</div>
			</div>
		</div>
	</section>

	<!-- ============ CTA ============ -->
	<section class="py-24 max-w-7xl mx-auto px-6">
		<div data-aos="zoom-in" class="rounded-[2.5rem] overflow-hidden relative bg-gradient-to-br from-darken via-[#2a2560] to-darken">
			<div class="absolute -right-10 -bottom-16 w-72 h-72 bg-yellow-400/10 rounded-full animate-blob"></div>
			<div class="absolute -left-10 -top-10 w-56 h-56 bg-teal-400/10 rounded-full animate-blob-slow"></div>
			<div class="relative z-10 px-8 py-16 md:py-20 text-center">
				<h2 class="text-white text-3xl md:text-4xl font-extrabold">Ready to <span class="text-gradient">ditch the notebooks?</span></h2>
				<p class="text-gray-300 mt-4 max-w-xl mx-auto">Set up your school on Edlink in minutes ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â no card required, no long onboarding calls.</p>
				<div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
					<a href="{{ route('register') }}" wire:navigate class="bg-gradient-to-r from-yellow-400 to-orange-500 text-darken font-bold rounded-full py-4 px-9 shadow-xl shadow-yellow-500/30 hover:scale-105 transition-transform">Try a free demo</a>
					<a href="#contact" class="border border-white/20 text-white font-medium rounded-full py-4 px-9 hover:border-yellow-400 transition-colors">Talk to our team</a>
				</div>
			</div>
		</div>
	</section>

	<!-- ============ CONTACT ============ -->
	<section id="contact" class="pb-24 max-w-7xl mx-auto px-6">
		<div class="grid lg:grid-cols-5 rounded-[2rem] overflow-hidden shadow-2xl border border-gray-100 dark:border-white/10">
			<div class="lg:col-span-2 p-10 text-white bg-gradient-to-br from-darken to-[#2a2560] flex flex-col justify-between">
				<div>
					<p class="text-yellow-400 text-xs font-bold tracking-widest uppercase">Contact Edlink</p>
					<h2 class="mt-3 text-2xl md:text-3xl font-extrabold">{{ $landing['contact_heading'] }}</h2>
					<p class="mt-4 text-gray-300">{{ $landing['contact_description'] }}</p>
				</div>
				<div class="mt-10 space-y-3">
					<a href="https://wa.me/{{ $landing['whatsapp'] }}" target="_blank" class="flex items-center space-x-3 bg-white/10 hover:bg-white/15 rounded-xl px-4 py-3 transition">
						<span class="w-9 h-9 rounded-full bg-green-500 flex items-center justify-center flex-shrink-0">
							<svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.79.47 3.47 1.29 4.92L2 22l5.25-1.38c1.4.76 2.99 1.2 4.68 1.2h.01c5.46 0 9.91-4.45 9.91-9.91C21.85 6.45 17.5 2 12.04 2zm5.79 14.02c-.24.68-1.4 1.3-1.93 1.38-.5.08-1.13.11-1.82-.11-.42-.13-.96-.31-1.65-.61-2.9-1.25-4.79-4.17-4.94-4.36-.14-.2-1.18-1.57-1.18-3 0-1.42.75-2.13 1.01-2.42.27-.29.58-.36.78-.36.2 0 .39 0 .56.01.18.01.42-.07.65.5.24.58.83 2.01.9 2.15.07.15.12.32.02.51-.1.2-.15.32-.29.49-.15.17-.31.38-.44.51-.15.15-.3.31-.13.6.17.29.75 1.24 1.62 2.01 1.11.99 2.05 1.3 2.34 1.44.29.15.46.13.63-.08.17-.2.72-.84.92-1.13.19-.29.39-.24.65-.15.27.1 1.7.8 1.99.95.29.15.48.22.55.34.07.13.07.72-.17 1.4z"/></svg>
						</span>
						<div class="text-sm"><p class="font-semibold">WhatsApp us</p><p class="text-gray-300 text-xs">{{ $landing['phone'] }}</p></div>
					</a>
					<a href="tel:{{ preg_replace('/[^0-9+]/', '', $landing['phone']) }}" class="flex items-center space-x-3 bg-white/10 hover:bg-white/15 rounded-xl px-4 py-3 transition">
						<span class="w-9 h-9 rounded-full bg-yellow-500 flex items-center justify-center flex-shrink-0">
							<svg class="w-4 h-4 text-darken" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
						</span>
						<div class="text-sm"><p class="font-semibold">Call us</p><p class="text-gray-300 text-xs">{{ $landing['phone'] }}</p></div>
					</a>
				</div>
			</div>

			<div class="lg:col-span-3 bg-white dark:bg-darken p-10">
				@if(session('contact_status'))
					<div class="mb-5 rounded-xl bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 p-4 text-sm text-green-700 dark:text-green-400">{{ session('contact_status') }}</div>
				@endif
				<form method="POST" action="{{ route('contact.store') }}" class="space-y-4">
					@csrf
					<input type="hidden" name="type" value="contact">
					<div class="grid sm:grid-cols-2 gap-4">
						<input required name="name" value="{{ old('name') }}" placeholder="Your name" class="w-full rounded-xl border border-gray-200 dark:border-white/10 dark:bg-white/5 dark:text-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
						<input required type="email" name="email" value="{{ old('email') }}" placeholder="Email address" class="w-full rounded-xl border border-gray-200 dark:border-white/10 dark:bg-white/5 dark:text-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
					</div>
					<input required name="subject" value="{{ old('subject') }}" placeholder="How can we help?" class="w-full rounded-xl border border-gray-200 dark:border-white/10 dark:bg-white/5 dark:text-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
					<textarea required name="message" rows="4" placeholder="Tell us more..." class="w-full rounded-xl border border-gray-200 dark:border-white/10 dark:bg-white/5 dark:text-white px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">{{ old('message') }}</textarea>
					<button type="submit" class="w-full sm:w-auto bg-gradient-to-r from-darken to-[#2a2560] text-white font-semibold rounded-full px-8 py-3.5 hover:scale-105 transition-transform">Send message</button>
				</form>
			</div>
		</div>
	</section>

	<!-- ============ FOOTER ============ -->
	<footer class="bg-darken text-gray-400">
		<div class="max-w-7xl mx-auto px-6 py-12 flex flex-col items-center text-center">
			<div class="flex items-center space-x-2 mb-4">

				<span class="font-bold text-white">
					<img src="{{ \App\Models\LandingPageSetting::assetUrl($landing,'footer_logo') }}" class="w-24" alt="Edlink logo">
				</span>
			</div>
			<div class="flex items-center space-x-4 text-sm mb-6">
				<a href="#" class="hover:text-white transition-colors">Careers</a>
				<span class="text-gray-600">ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¢</span>
				<a href="#" class="hover:text-white transition-colors">Privacy</a>
				<span class="text-gray-600">ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¢</span>
				<a href="#" class="hover:text-white transition-colors">Terms</a>
			</div>
			<p class="text-xs text-gray-500">&copy; <span x-data x-text="new Date().getFullYear()"></span> {{ $landing['footer_text'] }}</p>
		</div>
	</footer>

	<!-- ============ FLOATING CHAT ASSISTANT ============ -->
	<div x-data="edlinkChat()" class="fixed bottom-6 right-6 z-50 flex flex-col items-end">

		<!-- Chat window -->
		<div x-show="open" x-cloak x-transition
			 class="w-[22rem] max-w-[88vw] h-[30rem] max-h-[75vh] rounded-3xl overflow-hidden shadow-2xl border border-gray-100 dark:border-white/10 bg-white dark:bg-darken flex flex-col mb-4">

			<!-- header -->
			<div class="bg-gradient-to-r from-darken to-[#2a2560] px-5 py-4 flex items-center justify-between flex-shrink-0">
				<div class="flex items-center space-x-3">
					<div class="relative">
						<span class="w-9 h-9 rounded-full bg-yellow-400 flex items-center justify-center text-darken font-extrabold text-sm">E</span>
						<span class="absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full bg-green-400 border-2 border-darken"></span>
					</div>
					<div>
						<p class="text-white text-sm font-semibold">Edlink Assistant</p>
						<p class="text-green-300 text-xs">Online ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â instant answers</p>
					</div>
				</div>
				<button @click="open = false" class="text-white/70 hover:text-white">
					<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
				</button>
			</div>

			<!-- messages -->
			<div class="flex-1 overflow-y-auto chat-scroll px-4 py-4 space-y-3 bg-gray-50 dark:bg-white/[0.02]" x-ref="scrollArea">
				<template x-for="(m, i) in messages" :key="i">
					<div class="flex" :class="m.from === 'user' ? 'justify-end' : 'justify-start'">
						<div class="flex items-end space-x-2 max-w-[85%]" :class="m.from === 'user' && 'flex-row-reverse space-x-reverse'">
							<span x-show="m.from === 'bot'" class="w-6 h-6 rounded-full bg-yellow-400 flex items-center justify-center text-darken font-bold text-[10px] flex-shrink-0">E</span>
							<div class="rounded-2xl px-4 py-2.5 text-sm"
								 :class="m.from === 'user' ? 'bg-gradient-to-r from-yellow-400 to-orange-500 text-darken rounded-br-sm font-medium' : 'bg-white dark:bg-white/10 text-gray-700 dark:text-gray-200 border border-gray-100 dark:border-white/10 rounded-bl-sm'"
								 x-text="m.text"></div>
						</div>
					</div>
				</template>

				<div x-show="typing" x-cloak class="flex items-center space-x-2">
					<span class="w-6 h-6 rounded-full bg-yellow-400 flex items-center justify-center text-darken font-bold text-[10px] flex-shrink-0">E</span>
					<div class="bg-white dark:bg-white/10 border border-gray-100 dark:border-white/10 rounded-2xl rounded-bl-sm px-4 py-3 flex space-x-1">
						<span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
						<span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
						<span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
					</div>
				</div>
			</div>

			<!-- suggested questions -->
			<div class="px-4 py-3 border-t border-gray-100 dark:border-white/10 flex-shrink-0">
				<p class="text-[11px] text-gray-400 mb-2 uppercase tracking-wide font-semibold">Quick questions</p>
				<div class="flex flex-wrap gap-1.5">
					<template x-for="q in faqs" :key="q.q">
						<button @click="ask(q)" class="text-xs px-3 py-1.5 rounded-full bg-yellow-50 dark:bg-yellow-400/10 text-yellow-700 dark:text-yellow-400 font-medium hover:bg-yellow-100 dark:hover:bg-yellow-400/20 transition-colors" x-text="q.q"></button>
					</template>
				</div>
				<a href="#contact" @click="open = false" class="mt-3 flex items-center space-x-1 text-xs font-semibold text-darken dark:text-white hover:text-yellow-500 dark:hover:text-yellow-400">
					<span>Still stuck? Message our team</span>
					<svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" /></svg>
				</a>
			</div>
		</div>

		<!-- Floating buttons -->
		<div class="flex flex-col items-end space-y-3">
			<a href="tel:{{ preg_replace('/[^0-9+]/', '', $landing['phone']) }}" title="Call us" class="w-12 h-12 rounded-full bg-darken flex items-center justify-center shadow-lg hover:scale-110 transition-transform">
				<svg class="w-5 h-5 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
			</a>
			<a href="https://wa.me/{{ $landing['whatsapp'] }}" target="_blank" title="WhatsApp us" class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center shadow-lg hover:scale-110 transition-transform">
				<svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.79.47 3.47 1.29 4.92L2 22l5.25-1.38c1.4.76 2.99 1.2 4.68 1.2h.01c5.46 0 9.91-4.45 9.91-9.91C21.85 6.45 17.5 2 12.04 2zm5.79 14.02c-.24.68-1.4 1.3-1.93 1.38-.5.08-1.13.11-1.82-.11-.42-.13-.96-.31-1.65-.61-2.9-1.25-4.79-4.17-4.94-4.36-.14-.2-1.18-1.57-1.18-3 0-1.42.75-2.13 1.01-2.42.27-.29.58-.36.78-.36.2 0 .39 0 .56.01.18.01.42-.07.65.5.24.58.83 2.01.9 2.15.07.15.12.32.02.51-.1.2-.15.32-.29.49-.15.17-.31.38-.44.51-.15.15-.3.31-.13.6.17.29.75 1.24 1.62 2.01 1.11.99 2.05 1.3 2.34 1.44.29.15.46.13.63-.08.17-.2.72-.84.92-1.13.19-.29.39-.24.65-.15.27.1 1.7.8 1.99.95.29.15.48.22.55.34.07.13.07.72-.17 1.4z"/></svg>
			</a>
			<button @click="open = !open" title="Ask a question" class="w-14 h-14 rounded-full bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center shadow-xl shadow-yellow-500/30 hover:scale-110 transition-transform">
				<svg x-show="!open" class="w-6 h-6 text-darken" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
				<svg x-show="open" x-cloak class="w-6 h-6 text-darken" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
			</button>
		</div>
	</div>

	<script>
		function edlinkChat() {
			return {
				open: false,
				typing: false,
				messages: [
					{ from: 'bot', text: "Hi! I'm the Edlink assistant. Ask me anything about pricing, demos, or getting started ÃƒÂ°Ã…Â¸Ã¢â‚¬ËœÃ¢â‚¬Â¹" }
				],
				faqs: [
					{ q: 'Free demo?', a: 'Yes ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â every school gets a free 7-day demo with sample data pre-loaded, no card required. Just click "Try a free demo" up top.' },
					{ q: 'Pricing?', a: 'Plans start at UGX 150k/term for a single small school, up to custom pricing for school groups. Check the Pricing section above for full details.' },
					{ q: 'Setup time?', a: 'Most schools are fully set up ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â classes, streams, students, and fees ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â within a day. The demo itself takes about 2 minutes to start.' },
					{ q: 'Data security?', a: "Yes. Your school's data is isolated from every other school on Edlink and only accessible to your own staff accounts." },
					{ q: 'Multi-branch?', a: 'Yes, our Growth and Enterprise plans support multi-branch schools with per-branch reporting and permissions.' },
					{ q: 'Talk to support?', a: 'Use the WhatsApp or Call buttons below, or send a message through the contact form and we\'ll get back to you fast.' },
				],
				ask(q) {
					this.messages.push({ from: 'user', text: q.q });
					this.typing = true;
					this.scrollDown();
					setTimeout(() => {
						this.typing = false;
						this.messages.push({ from: 'bot', text: q.a });
						this.scrollDown();
					}, 700);
				},
				scrollDown() {
					this.$nextTick(() => {
						if (this.$refs.scrollArea) {
							this.$refs.scrollArea.scrollTop = this.$refs.scrollArea.scrollHeight;
						}
					});
				}
			}
		}
	</script>

	<script type="module" src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js"></script>
	<script>AOS.init({ once: true, duration: 700 });</script>
</body>
</html>