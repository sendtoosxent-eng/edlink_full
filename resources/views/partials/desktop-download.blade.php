<section id="desktop" class="desktop-section" aria-labelledby="desktop-heading">
    <div class="container desktop-shell">
        <figure class="desktop-illustration"><img src="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'desktop_image') }}" alt="{{ $landing['accessibility_edlink_dashboard_fee_payments_and_attendance_shown_o'] }}" width="1536" height="1024" loading="lazy"></figure>
        <div class="desktop-copy">
            <span class="eyebrow">{{ $landing['desktop_edlink_for_windows'] }}</span>
            <h2 id="desktop-heading">{{ $landing['desktop_your_school'] }}<br>{{ $landing['desktop_now_on_your_desktop'] }}</h2>
            <p>{{ $landing['desktop_one_click_from_your_desktop_to_your_school_day_open'] }}</p>
            <ul><li>@include('partials.landing-icon', ['name' =>'check']) {{ $landing['desktop_same_account_same_records'] }}</li><li>@include('partials.landing-icon', ['name' =>'check']) {{ $landing['desktop_launch_from_your_desktop'] }}</li></ul>
            @if($desktopRelease)
                <a class="button" href="{{ route('downloads.windows') }}" download="{{ $desktopRelease->filename }}">@include('partials.landing-icon', ['name' =>'windows']) {{ $landing['desktop_download_for_windows'] }}</a>
            @else
                <button class="button" type="button" disabled>@include('partials.landing-icon', ['name' =>'windows']) {{ $landing['desktop_windows_download_coming_soon'] }}</button>
            @endif
            <p class="release-meta">{{ $landing['desktop_windows_64_bit'] }} @if($desktopRelease) {{ $landing['desktop_v'] }}{{ $desktopRelease->version }} · {{ $desktopRelease->sizeLabel() }} @endif</p>
            <p>{{ $landing['desktop_download_attendance_and_draft_marks_in_advance_to_wo'] }}</p>
        </div>
    </div>
</section>
