<section id="desktop" class="mobile-section scroll-mt-20 max-w-7xl mx-auto px-4 sm:px-6 pt-12 pb-4" aria-labelledby="desktop-heading">
    <div data-aos="fade-up" class="relative overflow-hidden rounded-2xl sm:rounded-[2.5rem] border border-yellow-300/30 bg-[#252641] shadow-2xl shadow-slate-900/10">
        <div class="absolute -left-24 -top-24 h-80 w-80 rounded-full bg-yellow-400/10 blur-3xl pointer-events-none" aria-hidden="true"></div>
        <div class="relative grid items-center gap-8 lg:grid-cols-2 p-6 sm:p-10 lg:p-12">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1.5 text-xs font-semibold text-yellow-300">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M2 4.5 10.5 3v8H2zm10-1.8L22 1v10H12zM2 13h8.5v8L2 19.5zm10 0h10v10l-10-1.7z"/></svg>
                    EDLINK FOR WINDOWS
                </span>
                <h2 id="desktop-heading" class="mt-6 text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-tight tracking-tight text-white">Your school.<br><span class="text-yellow-400">Now on your desktop.</span></h2>
                <p class="mt-5 max-w-lg text-sm sm:text-base leading-7 text-slate-300">One click from your desktop to your school day. Open Edlink, sign in, and manage your school with the same account and cloud records you use on the web.</p>
                <div class="mt-6 flex flex-wrap gap-x-5 gap-y-3 text-xs sm:text-sm text-slate-200">
                    <span class="inline-flex items-center gap-2"><span class="text-yellow-400" aria-hidden="true">✓</span> Same account. Same records.</span>
                    <span class="inline-flex items-center gap-2"><span class="text-yellow-400" aria-hidden="true">✓</span> Launch from your desktop</span>
                </div>
                <div class="mt-8 flex flex-wrap items-center gap-4">
                    @if($desktopRelease)
                        <a href="{{ route('downloads.windows') }}" download="{{ $desktopRelease->filename }}" class="inline-flex items-center justify-center gap-3 rounded-xl bg-yellow-400 px-6 py-4 text-sm font-extrabold text-[#252641] transition hover:bg-yellow-300 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-yellow-300">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m-4-4 4 4 4-4M4 16v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4"/></svg>
                            Download for Windows
                        </a>
                    @else
                        <span class="inline-flex rounded-xl bg-white/10 px-6 py-4 text-sm font-semibold text-slate-300">Windows download coming soon</span>
                    @endif
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-white underline underline-offset-4 decoration-white/40 hover:decoration-yellow-400">Use the web version <span aria-hidden="true">↗</span></a>
                </div>
                <p class="mt-4 text-xs text-slate-400">Windows · 64-bit @if($desktopRelease) · v{{ $desktopRelease->version }} · {{ $desktopRelease->sizeLabel() }} @endif</p>
                <p class="mt-5 max-w-lg text-xs leading-6 text-slate-400">Connected in the cloud. Download attendance and draft marks in advance to work offline, then sync when you reconnect.</p>
            </div>
            <figure class="relative m-0 overflow-hidden rounded-2xl border border-white/10 bg-white shadow-xl">
                <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                    <span class="text-xs font-bold text-[#252641]">One Edlink. Every screen.</span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-semibold text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500" aria-hidden="true"></span> Cloud connected</span>
                </div>
                <img src="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'desktop_image') }}" alt="Edlink dashboard, fee payments and attendance shown on a desktop monitor, laptop, tablet and phone" width="1536" height="1024" loading="lazy" class="block h-auto w-full object-contain">
                <figcaption class="border-t border-slate-100 px-5 py-3 text-center text-xs text-slate-500">Web or desktop. Your school stays connected.</figcaption>
            </figure>
        </div>
    </div>
</section>
