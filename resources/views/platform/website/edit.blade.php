@extends('layouts.platform', ['title' => 'Website Content Management'])

@section('content')
<div class="space-y-6">
    <div class="rounded-2xl bg-slate-900 p-6 text-white flex flex-wrap items-center justify-between gap-4">
        <div><h1 class="text-2xl font-bold text-yellow-400">Landing Page Content</h1><p class="text-sm text-slate-300 mt-2">Edit the public website by section. Changes appear after you publish.</p></div>
        <a href="{{ route('home') }}" target="_blank" rel="noopener noreferrer" class="rounded-xl bg-yellow-400 text-slate-900 px-5 py-3 font-bold text-sm">Preview Landing Page</a>
    </div>
    @if(session('status'))<div role="status" class="rounded-xl bg-emerald-50 text-emerald-900 p-4">{{ session('status') }}</div>@endif
    @if($errors->any())<div role="alert" class="rounded-xl bg-red-50 text-red-900 p-4"><strong>Please correct these fields:</strong><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="rounded-xl border border-slate-200 bg-white p-4 text-sm text-slate-600">Registered school totals, demo role totals and installer version/size come from the platform automatically. Installer downloads follow the currently published desktop release. Image uploads accept JPG, PNG or WebP up to 4 MB. For testimonials, replace the sample notice only when you have approved real reviews.</div>
    <form method="POST" action="{{ route('platform.website.update') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        @method('PUT')
        @foreach(\App\Models\LandingPageSetting::editorGroups() as $group => $fields)
            <details class="rounded-2xl border border-slate-200 bg-white shadow-sm" @if($loop->first) open @endif>
                <summary class="cursor-pointer px-5 py-4 font-bold text-slate-900">{{ str($group)->replace('_', ' ')->headline() }} <span class="text-xs font-normal text-slate-500">· {{ count($fields) }} fields</span></summary>
                <div class="grid gap-5 border-t border-slate-100 p-5 md:grid-cols-2">
                    @foreach($fields as $key => $field)
                        <div class="min-w-0 {{ $field['multiline'] ? 'md:col-span-2' : '' }}">
                            <label for="field-{{ $key }}" class="block text-sm font-semibold text-slate-700 mb-2">{{ $field['label'] }}</label>
                            @if($field['type'] === 'image')
                                @if(filled($landing[$key] ?? ''))<div class="mb-3 rounded-lg bg-slate-100 p-3"><img src="{{ \App\Models\LandingPageSetting::assetUrl($landing, $key) }}" alt="Current {{ $field['label'] }}" class="{{ str_contains($key, 'portrait') ? 'h-20 w-20 rounded-full object-cover' : 'h-24 max-w-full object-contain' }}"></div>@endif
                                <input id="field-{{ $key }}" type="file" name="{{ $key }}" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-slate-700">
                            @elseif($field['multiline'])
                                <textarea id="field-{{ $key }}" name="{{ $key }}" rows="3" maxlength="3000" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:ring-yellow-400">{{ old($key, $landing[$key] ?? '') }}</textarea>
                            @else
                                <input id="field-{{ $key }}" type="{{ $field['type'] === 'url' ? 'url' : 'text' }}" name="{{ $key }}" value="{{ old($key, $landing[$key] ?? '') }}" maxlength="{{ in_array($field['type'], ['url', 'link']) ? 500 : 3000 }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:ring-yellow-400">
                            @endif
                            @if($field['type'] === 'link')<p class="mt-1 text-xs text-slate-500">Use #section, /local-path, or https://…</p>@endif
                            @error($key)<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    @endforeach
                </div>
            </details>
        @endforeach
        <div class="sticky bottom-4 z-10 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-lg">
            <span class="text-sm text-slate-600">Publish your edits to the public landing page.</span>
            <button type="submit" class="rounded-xl bg-yellow-400 hover:bg-yellow-300 text-slate-900 px-6 py-3 font-bold text-sm">Publish Changes</button>
        </div>
    </form>
</div>
@endsection
