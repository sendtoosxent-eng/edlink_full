@php
    $avatarSettings = $avatarSettings ?? $landing ?? \App\Models\LandingPageSetting::values();
    $customPortrait = $avatarSettings[$avatarKey] ?? '';
@endphp
<span class="trial-portrait {{ $avatarClass ?? '' }}" aria-hidden="true" style="--avatar-x:{{ ($avatarIndex % 5) * 25 }}%;--avatar-y:{{ $avatarIndex < 5 ? 0 : 100 }}%">
    @if(filled($customPortrait))
        <img src="{{ \App\Models\LandingPageSetting::assetUrl($avatarSettings, $avatarKey) }}" alt="" width="68" height="68" loading="lazy">
    @else
        <span class="trial-memoji"></span>
    @endif
</span>
