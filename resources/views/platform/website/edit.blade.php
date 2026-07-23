@extends('layouts.platform', ['title' => 'Website Content Management'])

@section('content')
<div class="mx-auto max-w-6xl space-y-6 pb-20">
    <!-- Header Block with Dark Gradient Background & Ambient Glow -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-sm">
        <div class="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-400/10 border border-amber-400/20 text-amber-300 text-xs font-bold mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                    </svg>
                    <span>Public Website Manager</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-amber-300">
                    Landing Page Content
                </h1>
                <p class="text-sm font-medium text-slate-400 mt-1.5 leading-relaxed">
                    Update public copy, contact details, statistics, and visual messaging from one screen.
                </p>
            </div>

            <!-- Preview External Link Button -->
            <a href="{{ route('home') }}" target="_blank" 
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-400 hover:bg-amber-300 active:scale-95 text-slate-950 font-extrabold text-xs px-5 py-3 transition shadow-md hover:shadow-lg shrink-0">
                <span>Preview Landing Page</span>
                <svg class="w-4 h-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
            </a>
        </div>
        
        <!-- Decorative Ambient Glow -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Status Alerts -->
    @if(session('status'))
        <div class="flex items-center justify-between gap-3 bg-emerald-50 border border-emerald-200/80 text-emerald-900 text-sm rounded-2xl p-4 shadow-xs">
            <div class="flex items-center gap-3">
                <div class="p-1 bg-emerald-100 rounded-lg shrink-0">
                    <svg class="w-5 h-5 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="font-semibold">{{ session('status') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    @endif

    @if($errors->any())
        <div class="flex items-center justify-between gap-3 bg-rose-50 border border-rose-200/80 text-rose-900 text-sm rounded-2xl p-4 shadow-xs">
            <div class="flex items-center gap-3">
                <div class="p-1 bg-rose-100 rounded-lg shrink-0">
                    <svg class="w-5 h-5 text-rose-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <span class="font-semibold">Please correct the highlighted fields below.</span>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('platform.website.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf 
        @method('PUT')

        <!-- Section 1: Hero & Identity -->
        <section class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 space-y-5">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="font-bold text-slate-900 text-base">Hero & Identity</h3>
                <p class="text-xs font-medium text-slate-500 mt-0.5">Configure main headers, title tags, and hero call-to-action buttons.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Browser Title</label>
                    <input name="site_title" value="{{ old('site_title', $landing['site_title']) }}" 
                           class="w-full text-sm font-semibold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs">
                    @error('site_title')<p class="text-rose-600 text-xs font-medium mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Banner Announcement</label>
                    <input name="announcement" value="{{ old('announcement', $landing['announcement']) }}" 
                           class="w-full text-sm font-semibold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs">
                    @error('announcement')<p class="text-rose-600 text-xs font-medium mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Hero Opening</label>
                    <input name="hero_title" value="{{ old('hero_title', $landing['hero_title']) }}" 
                           class="w-full text-sm font-semibold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs">
                    @error('hero_title')<p class="text-rose-600 text-xs font-medium mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Highlighted Words</label>
                    <input name="hero_highlight" value="{{ old('hero_highlight', $landing['hero_highlight']) }}" 
                           class="w-full text-sm font-semibold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs">
                    @error('hero_highlight')<p class="text-rose-600 text-xs font-medium mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Hero Ending</label>
                    <input name="hero_title_suffix" value="{{ old('hero_title_suffix', $landing['hero_title_suffix']) }}" 
                           class="w-full text-sm font-semibold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs">
                    @error('hero_title_suffix')<p class="text-rose-600 text-xs font-medium mt-1">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Description</label>
                    <textarea name="hero_description" rows="3" 
                              class="w-full text-sm font-medium bg-white border border-slate-200 rounded-xl p-3.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs">{{ old('hero_description', $landing['hero_description']) }}</textarea>
                    @error('hero_description')<p class="text-rose-600 text-xs font-medium mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Primary CTA Label</label>
                    <input name="primary_cta" value="{{ old('primary_cta', $landing['primary_cta']) }}" 
                           class="w-full text-sm font-semibold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs">
                    @error('primary_cta')<p class="text-rose-600 text-xs font-medium mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Secondary CTA Label</label>
                    <input name="secondary_cta" value="{{ old('secondary_cta', $landing['secondary_cta']) }}" 
                           class="w-full text-sm font-semibold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs">
                    @error('secondary_cta')<p class="text-rose-600 text-xs font-medium mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <!-- Section 2: Statistics & Headings -->
        <section class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 space-y-5">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="font-bold text-slate-900 text-base">Statistics & Section Titles</h3>
                <p class="text-xs font-medium text-slate-500 mt-0.5">Customize metrics counter cards and section headers.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                @foreach([1 => 'one', 2 => 'two', 3 => 'three'] as $number => $word)
                    <div class="rounded-xl border border-slate-200/80 bg-slate-50/60 p-4 space-y-3">
                        <span class="inline-block text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md bg-slate-200/80 text-slate-700">
                            Stat Metric {{ $number }}
                        </span>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Value</label>
                            <input name="stat_{{ $word }}_value" value="{{ old('stat_'.$word.'_value', $landing['stat_'.$word.'_value']) }}" 
                                   class="w-full text-sm font-bold bg-white border border-slate-200 rounded-xl px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 transition shadow-2xs">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Label</label>
                            <input name="stat_{{ $word }}_label" value="{{ old('stat_'.$word.'_label', $landing['stat_'.$word.'_label']) }}" 
                                   class="w-full text-sm font-medium bg-white border border-slate-200 rounded-xl px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 transition shadow-2xs">
                        </div>
                    </div>
                @endforeach

                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Trust Bar Banner</label>
                    <input name="trust_text" value="{{ old('trust_text', $landing['trust_text']) }}" 
                           class="w-full text-sm font-semibold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs">
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Features Section Heading</label>
                    <input name="features_heading" value="{{ old('features_heading', $landing['features_heading']) }}" 
                           class="w-full text-sm font-semibold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs">
                </div>

                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Pricing Section Heading</label>
                    <input name="pricing_heading" value="{{ old('pricing_heading', $landing['pricing_heading']) }}" 
                           class="w-full text-sm font-semibold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs">
                </div>
            </div>
        </section>

        <!-- Section 3: About & Contact Details -->
        <section class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6 space-y-5">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="font-bold text-slate-900 text-base">About & Contact Details</h3>
                <p class="text-xs font-medium text-slate-500 mt-0.5">Manage public support channels and footer copyright details.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">About Heading</label>
                    <input name="about_heading" value="{{ old('about_heading', $landing['about_heading']) }}" 
                           class="w-full text-sm font-semibold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">About Paragraph One</label>
                    <textarea name="about_text" rows="4" 
                              class="w-full text-sm font-medium bg-white border border-slate-200 rounded-xl p-3.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs">{{ old('about_text', $landing['about_text']) }}</textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">About Paragraph Two</label>
                    <textarea name="about_text_two" rows="4" 
                              class="w-full text-sm font-medium bg-white border border-slate-200 rounded-xl p-3.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs">{{ old('about_text_two', $landing['about_text_two']) }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Contact Heading</label>
                    <input name="contact_heading" value="{{ old('contact_heading', $landing['contact_heading']) }}" 
                           class="w-full text-sm font-semibold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Contact Description</label>
                    <input name="contact_description" value="{{ old('contact_description', $landing['contact_description']) }}" 
                           class="w-full text-sm font-semibold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Phone Line</label>
                    <input name="phone" value="{{ old('phone', $landing['phone']) }}" 
                           class="w-full text-sm font-semibold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">WhatsApp Digits</label>
                    <input name="whatsapp" value="{{ old('whatsapp', $landing['whatsapp']) }}" 
                           class="w-full text-sm font-semibold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Support Email</label>
                    <input name="support_email" type="email" value="{{ old('support_email', $landing['support_email']) }}" 
                           class="w-full text-sm font-semibold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Footer Copyright Text</label>
                    <input name="footer_text" value="{{ old('footer_text', $landing['footer_text']) }}" 
                           class="w-full text-sm font-semibold bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-2xs">
                </div>
            </div>
        </section>

        <!-- Fixed/Sticky Bottom Action Bar -->
        <div class="sticky bottom-4 z-20 rounded-2xl bg-slate-900/95 backdrop-blur-md border border-slate-800 p-4 text-white shadow-xl flex items-center justify-between gap-4">
            <div class="hidden sm:flex items-center gap-2 text-xs font-medium text-slate-400">
                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Ensure all changed values are reviewed before updating.</span>
            </div>

            <button type="submit" 
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-amber-400 hover:bg-amber-300 active:scale-95 text-slate-950 font-black text-sm px-6 py-2.5 transition shadow-md cursor-pointer">
                <svg class="w-4 h-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <span>Save All Changes</span>
            </button>
        </div>
    </form>
</div>

<section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><h3 class="font-black text-slate-900">Landing-page assets</h3><p class="mt-1 text-xs text-slate-500">Upload JPG, PNG or WebP files up to 4 MB. Existing images remain unless replaced.</p><div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">@foreach(['nav_logo'=>'Navigation logo','hero_image'=>'Hero image','feature_image'=>'Feature image','about_image'=>'About image','footer_logo'=>'Footer logo'] as $key=>$label)<label class="rounded-xl border border-dashed border-slate-300 p-4 text-xs font-bold text-slate-600"><span>{{ $label }}</span><img src="{{ \App\Models\LandingPageSetting::assetUrl($landing,$key) }}" class="my-3 h-24 w-full rounded-lg bg-slate-100 object-contain" alt=""><input type="file" name="{{ $key }}" accept="image/*" class="block w-full text-[10px] font-normal"></label>@endforeach</div></section>
<div class="sticky bottom-5 flex justify-end"><button class="rounded-xl bg-yellow-400 px-7 py-4 text-sm font-black text-slate-900 shadow-xl hover:bg-slate-900 hover:text-white">Publish landing page</button></div></form></div>
@endsection