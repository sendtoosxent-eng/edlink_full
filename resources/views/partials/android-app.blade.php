<section id="android" class="android-section" aria-labelledby="android-heading">
    <div class="container android-layout">
        <div class="android-copy">
            <span class="android-label">@include('partials.landing-icon', ['name' =>'android']) {{ $landing['mobile_edlink_for_android_ios'] }}</span>
            <h2 id="android-heading">{{ $landing['mobile_your_school_day'] }}<br><span>{{ $landing['mobile_in_your_pocket'] }}</span></h2>
            <p>{{ $landing['mobile_take_edlink_beyond_the_desk_teachers_can_manage_atte'] }}</p>
            <ul class="android-features">
                <li>@include('partials.landing-icon', ['name' =>'calendar']) {{ $landing['mobile_attendance_marks'] }}</li>
                <li>@include('partials.landing-icon', ['name' =>'book']) {{ $landing['mobile_homework_school_notices'] }}</li>
                <li>@include('partials.landing-icon', ['name' =>'receipt']) {{ $landing['mobile_results_fee_balances'] }}</li>
                <li>@include('partials.landing-icon', ['name' =>'shield']) {{ $landing['mobile_biometric_login_on_supported_devices'] }}</li>
            </ul>
            <div class="mobile-download-buttons">
                @foreach([
                    ['android_download_url', 'android', $landing['mobile_download_apk']],
                    ['google_play_url', 'google-play', $landing['mobile_google_play']],
                    ['app_store_url', 'apple', $landing['mobile_app_store']],
                ] as [$key, $icon, $label])
                    @php
                        $downloadUrl = $landing[$key] ?? '';
                        $available = filter_var($downloadUrl, FILTER_VALIDATE_URL) && str_starts_with($downloadUrl, 'https://');
                        if ($key === 'android_download_url' && ($androidAvailable ?? false)) {
                            $downloadUrl = route('downloads.android');
                            $available = true;
                        }
                    @endphp
                    @if($available)
                        <a class="button white android-download" href="{{ $downloadUrl }}" @if($key === 'android_download_url') download="Edlink.apk" @else target="_blank" rel="noopener noreferrer" @endif>@include('partials.landing-icon', ['name' => $icon]) {{ $label }}</a>
                    @else
                        <button class="button white android-download" type="button" disabled>@include('partials.landing-icon', ['name' => $icon]) {{ $label }}</button>
                    @endif
                @endforeach
            </div>
            <p class="android-note">{{ $landing['mobile_same_school_account_connected_cloud_records_internet'] }}</p>
        </div>
        <figure class="android-visual">
            <span class="android-orbit" aria-hidden="true"></span>
            <img src="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'android_image') }}" alt="{{ $landing['accessibility_woman_showing_the_edlink_mobile_app_on_an_android_ph'] }}" width="420" height="500" loading="lazy">
            <figcaption class="float-note">@include('partials.landing-icon', ['name' =>'android'])<div><strong>{{ $landing['mobile_edlink_wherever_school_takes_you'] }}</strong>{{ $landing['mobile_for_teachers_parents_learners'] }}</div></figcaption>
        </figure>
    </div>
</section>
