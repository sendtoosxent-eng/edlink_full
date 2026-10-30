<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="{{ $landing['hero_description'] }}">
    <meta name="theme-color" content="#252641">
    <title>{{ $landing['site_title'] }}</title>
    <link rel="icon" type="image/png" href="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'navigation_image_1') }}">
    <link rel="apple-touch-icon" href="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'navigation_image_2') }}">
    <link rel="preload" href="{{ asset('fonts/poppins/Poppins-Regular.ttf') }}" as="font" type="font/ttf" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/edlink-landing.css') }}?v={{ filemtime(public_path('css/edlink-landing.css')) }}">
    <script>try { if (localStorage.getItem('edlink_theme') === 'dark') document.documentElement.classList.add('dark'); } catch {}</script>
    <script src="{{ asset('js/edlink-landing.js') }}?v={{ filemtime(public_path('js/edlink-landing.js')) }}" defer></script>
@include('partials.page-skeleton-head')
    <link rel="stylesheet" href="{{ asset('css/edlink-avatars.css') }}?v={{ filemtime(public_path('css/edlink-avatars.css')) }}">
@include('partials.public-engagement-head')
</head>
<body>
@include('partials.page-skeleton')

@include('partials.landing-icons')
<a class="skip-link" href="{{ $landing['navigation_main'] }}">{{ $landing['navigation_skip_to_content'] }}</a>
<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="{{ $landing['navigation_home_link'] }}" aria-label="Edlink home"><img class="logo-light" src="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'nav_logo') }}" alt="" width="145" height="47"><img class="logo-dark" src="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'navigation_image_3') }}" alt="" width="145" height="47"></a>
        <nav id="site-navigation" class="site-nav" aria-label="Main navigation">
            <a href="{{ $landing['navigation_main'] }}">{{ $landing['navigation_home'] }}</a><a href="{{ $landing['navigation_about'] }}">{{ $landing['navigation_about_us'] }}</a><a href="{{ $landing['navigation_features_11'] }}">{{ $landing['navigation_features'] }}</a><a href="{{ $landing['navigation_desktop'] }}">{{ $landing['navigation_desktop_app'] }}</a><a href="{{ $landing['navigation_pricing_13'] }}">{{ $landing['navigation_pricing'] }}</a><a href="{{ $landing['navigation_contact_14'] }}">{{ $landing['navigation_contact'] }}</a>
        </nav>
        <div class="header-actions">
            <a class="button small" href="{{ $landing['navigation_demo_access'] }}">{{ $landing['navigation_try_a_demo'] }} @include('partials.landing-icon', ['name' =>'diagonal'])</a>
            <a class="icon-button" href="{{ $landing['navigation_login_link'] }}" aria-label="Sign in to Edlink">@include('partials.landing-icon', ['name' =>'people'])</a>
            <button class="icon-button" type="button" data-theme-toggle aria-label="Switch to dark mode">@include('partials.landing-icon', ['name' =>'moon'])</button>
            <button class="icon-button nav-toggle" type="button" aria-controls="site-navigation" aria-expanded="false" aria-label="Open navigation">@include('partials.landing-icon', ['name' =>'menu'])</button>
        </div>
    </div>
</header>
<main id="main">
    <section class="hero" aria-labelledby="hero-heading">
        <div class="container hero-inner">
            <div class="hero-person left" aria-hidden="true">
                <img src="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'hero_image') }}" alt="" width="235" height="340" fetchpriority="high">
                <div class="float-note">@include('partials.landing-icon', ['name' =>'calendar'])<div><strong>{{ $landing['hero_school_day_sorted'] }}</strong>{{ $landing['hero_more_time_for_learning'] }}</div></div>
            </div>
            <div class="hero-copy">
                <span class="eyebrow">{{ $landing['announcement'] }}</span>
                <h1 id="hero-heading">{{ $landing['hero_title'] }} <span>{{ $landing['hero_highlight'] }}</span> {{ $landing['hero_title_suffix'] }}</h1>
                <p>{{ $landing['hero_description'] }}</p>
                <div class="hero-ctas">
                    <a class="button" href="{{ $landing['hero_demo_access'] }}">{{ $landing['primary_cta'] }} @include('partials.landing-icon', ['name' =>'diagonal'])</a>
                    <a class="button hero-login" href="{{ $landing['hero_login_link'] }}">{{ $landing['hero_login_label'] }} @include('partials.landing-icon', ['name' => 'arrow'])</a>
                </div>
                <div class="hero-domains" aria-label="Connected school services">
                    <span>@include('partials.landing-icon', ['name' =>'people']) {{ $landing['hero_students'] }}</span><span>@include('partials.landing-icon', ['name' =>'book']) {{ $landing['hero_academics'] }}</span><span>@include('partials.landing-icon', ['name' =>'receipt']) {{ $landing['hero_finance'] }}</span><span>@include('partials.landing-icon', ['name' =>'message']) {{ $landing['hero_families'] }}</span>
                </div>
            </div>
            <div class="hero-person right" aria-hidden="true">
                <img src="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'hero_image_1') }}" alt="" width="260" height="390" fetchpriority="high">
            </div>
            @include('partials.landing-icon', ['name' => 'pencil', 'class' => 'decor school-pencil'])
            @include('partials.landing-icon', ['name' => 'pen', 'class' => 'decor school-pen'])
            @include('partials.landing-icon', ['name' => 'book', 'class' => 'decor school-book'])
            @include('partials.landing-icon', ['name' => 'math', 'class' =>'decor school-math'])</div>
    </section>

    <section class="container benefits" aria-label="Our goals and benefits">
        <div class="benefits-intro"><span class="eyebrow">{{ $landing['benefits_why_schools_choose_edlink'] }}</span><h2>{{ $landing['benefits_our_goals_benefits'] }}</h2><a class="button small" href="{{ $landing['benefits_about'] }}">{{ $landing['benefits_find_out_more'] }} @include('partials.landing-icon', ['name' =>'arrow'])</a></div>
        @foreach([
            ['people', $landing['benefits_less_paperwork'], $landing['benefits_keep_learner_and_staff_records_together_instead_of_s']],
            ['receipt', $landing['benefits_clearer_finances'], $landing['benefits_track_school_fees_payments_and_outstanding_balances']],
            ['message', $landing['benefits_better_connections'], $landing['benefits_keep_teachers_parents_and_learners_connected_to_scho']],
        ] as [$icon, $title, $description])
            <article class="benefit {{ $loop->index === 1 ? 'featured' : '' }}"><div class="icon-tile">@include('partials.landing-icon', ['name' =>$icon])</div><h3>{{ $title }}</h3><p>{{ $description }}</p></article>
        @endforeach
    </section>

    <section id="about" class="section">
        <div class="container about-layout">
            <div class="about-visual" aria-label="Learning and school administration">
                <figure class="about-photo one"><img src="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'about_image_1') }}" alt="{{ $landing['accessibility_learner_holding_her_school_books'] }}" width="300" height="330" loading="lazy"></figure>
                <figure class="about-photo two"><img src="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'about_image_2') }}" alt="{{ $landing['accessibility_learning_through_a_connected_device'] }}" width="180" height="180" loading="lazy"></figure>
                <figure class="about-photo three"><img src="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'about_image') }}" alt="{{ $landing['accessibility_teacher_and_learners_in_a_classroom'] }}" width="300" height="300" loading="lazy"></figure>
                <figure class="about-photo four"><img src="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'feature_image') }}" alt="{{ $landing['accessibility_edlink_academic_records_illustration'] }}" width="160" height="160" loading="lazy"></figure>
                <div class="float-note">@include('partials.landing-icon', ['name' =>'shield'])<div><strong>{{ $landing['about_built_around_your_school'] }}</strong>{{ $landing['about_access_shaped_by_each_role'] }}</div></div>
            </div>
            <div class="about-copy">
                <span class="eyebrow">{{ $landing['about_a_little_about_edlink'] }}</span>
                <h2>{{ $landing['about_heading'] }}</h2>
                <p>{{ $landing['about_text'] }}</p>
                <p>{{ $landing['about_text_two'] }}</p>
                <div class="feature-lines">
                    <div class="feature-line">@include('partials.landing-icon', ['name' =>'shield']) {{ $landing['about_separate_school_records_controlled_access'] }}</div>
                    <div class="feature-line">@include('partials.landing-icon', ['name' =>'laptop']) {{ $landing['about_web_mobile_and_windows_desktop'] }}</div>
                </div>
                <a class="button" href="{{ $landing['about_demo_access'] }}">{{ $landing['about_meet_your_new_school_workspace'] }} @include('partials.landing-icon', ['name' =>'arrow'])</a>
            </div>
        </div>
    </section>

    <section id="features" class="section lavender">
        <div class="container">
            <div class="section-heading"><span class="eyebrow">{{ $landing['features_made_for_your_school_day'] }}</span><h2>{{ $landing['features_heading'] }}</h2><p>{{ $landing['features_from_the_first_admission_to_the_final_term_report_br'] }}</p></div>
            <div class="service-grid">
                @foreach([
                    ['people', $landing['features_students_classes_streams'], $landing['features_manage_admissions_learner_profiles_classes_and_strea'], 'features_image_1', 'administrator'],
                    ['calendar', $landing['features_attendance_school_life'], $landing['features_keep_class_registers_timetables_homework_and_school'], 'features_image_2', 'class-teacher'],
                    ['receipt', $landing['features_school_fees_accounting'], $landing['features_record_payments_issue_receipts_follow_balances_and_k'], 'features_image_3', 'bursar'],
                    ['book', $landing['features_academics_report_cards'], $landing['features_enter_marks_review_results_and_prepare_reports_with'], 'features_image_4', 'subject-teacher'],
                ] as [$icon, $title, $description, $image, $role])
                    <article class="service-card"><div><h3>{{ $title }}</h3><p>{{ $description }}</p><a class="text-link" href="{{ $landing['role_'.$role.'_link'] ?? route('login', ['demo' => $role]) }}">{{ $landing['features_explore_the_workspace'] }} @include('partials.landing-icon', ['name' =>'diagonal'])</a></div><div class="service-image"><img src="{{ \App\Models\LandingPageSetting::assetUrl($landing, $image) }}" alt="{{ $title }}" width="165" height="165" loading="lazy"><span class="round-icon">@include('partials.landing-icon', ['name' =>$icon])</span></div></article>
                @endforeach
            </div>
            <div class="center after-grid"><a class="button" href="{{ $landing['features_demo_access'] }}">{{ $landing['features_see_edlink_in_action'] }} @include('partials.landing-icon', ['name' =>'arrow'])</a></div>
        </div>
    </section>

    <section class="spotlight" id="edlink-impact" aria-labelledby="people-heading">
        <div class="container">
            <div class="spotlight-layout">
                <div class="spotlight-copy"><span class="eyebrow">{{ $landing['edlink_impact_built_around_your_school'] }}</span><h2 id="people-heading">{{ $landing['edlink_impact_one_connected_platform'] }}<br><em>{{ $landing['edlink_impact_a_better_school_day'] }}</em></h2><p>{{ $landing['edlink_impact_from_admissions_and_attendance_to_fees_results_and_p'] }}</p><div class="small-proof">@include('partials.landing-icon', ['name' =>'shield'])<div><strong>{{ $landing['edlink_impact_clarity_for_every_role'] }}</strong>{{ $landing['edlink_impact_school_records_permissions_and_audit_history'] }}</div></div><a class="button" href="{{ $landing['edlink_impact_demo_access'] }}">{{ $landing['edlink_impact_discover_edlink'] }} @include('partials.landing-icon', ['name' =>'diagonal'])</a></div>
                <div class="spotlight-person"><img src="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'edlink_impact_image_1') }}" alt="{{ $landing['accessibility_edlink_accessible_from_a_phone'] }}" width="390" height="370" loading="lazy"><span class="floating-icon">@include('partials.landing-icon', ['name' =>'check'])</span><div class="float-note">@include('partials.landing-icon', ['name' =>'globe'])<div><strong>{{ $landing['edlink_impact_school_stays_connected'] }}</strong>{{ $landing['edlink_impact_at_your_desk_or_on_the_move'] }}</div></div></div>
            </div>
            <div class="stats impact-stats" data-animated-stats>
                <div class="stat"><div class="icon-tile">@include('partials.landing-icon', ['name' =>'book'])</div><div><strong>{{ $landing['stat_one_value'] }}</strong><span>{{ $landing['stat_one_label'] }}</span></div></div>
                <div class="stat"><div class="icon-tile">@include('partials.landing-icon', ['name' =>'building'])</div><div><strong data-count-to="{{ $registeredSchoolCount ?? 0 }}">{{ number_format($registeredSchoolCount ?? 0) }}</strong><span>{{ $landing['edlink_impact_registered_schools'] }}</span><small>{{ $landing['edlink_impact_excluding_demo_schools'] }}</small></div></div>
                <div class="stat experience-stat"><div class="icon-tile">@include('partials.landing-icon', ['name' =>'shield'])</div><div><strong>{{ $landing['experience_value'] }}</strong><span>{{ $landing['experience_label'] }}</span></div></div>
            </div>
        </div>
    </section>

    <section class="section" id="experience">
        <div class="container">
            <div class="section-heading"><span class="eyebrow">{{ $landing['experience_the_bigger_picture'] }}</span><h2>{{ $landing['experience_a_connected_school'] }}<br>{{ $landing['experience_from_every_angle'] }}</h2><p>{{ $landing['trust_text'] }}</p></div>
            <div class="school-gallery">
                <figure class="gallery-item one"><img src="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'experience_image_1') }}" alt="{{ $landing['accessibility_a_connected_classroom'] }}" width="350" height="210" loading="lazy"><span>{{ $landing['experience_teaching_learning'] }}</span></figure>
                <figure class="gallery-item two"><img src="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'experience_image_2') }}" alt="{{ $landing['accessibility_learning_through_a_laptop'] }}" width="350" height="210" loading="lazy"><span>{{ $landing['experience_everyday_connections'] }}</span></figure>
                <figure class="gallery-item tall"><img src="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'hero_image') }}" alt="{{ $landing['accessibility_putting_learners_at_the_centre'] }}" width="390" height="440" loading="lazy"><span>{{ $landing['experience_learners_at_the_heart'] }}</span></figure>
                <figure class="gallery-item four"><img src="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'product_image') }}" alt="{{ $landing['accessibility_edlink_school_records_illustration'] }}" width="350" height="210" loading="lazy"><span>{{ $landing['experience_one_organised_workspace'] }}</span></figure>
                <figure class="gallery-item five"><img src="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'experience_image_3') }}" alt="{{ $landing['accessibility_edlink_across_devices'] }}" width="350" height="210" loading="lazy"><span>{{ $landing['experience_every_screen_connected'] }}</span></figure>
            </div>
        </div>
    </section>

    @include('partials.desktop-download')
    @include('partials.android-app')

    <section id="pricing" class="section lavender">
        <div class="container">
            <div class="section-heading"><span class="eyebrow">{{ $landing['pricing_plans_that_grow_with_you'] }}</span><h2>{{ $landing['pricing_heading'] }}</h2><p>{{ $landing['pricing_choose_the_right_fit_for_your_school_we_help_you_mov'] }}</p></div>
            <div class="pricing-grid pricing-reference">
                @foreach([
                    ['home', $landing['pricing_starter'], $landing['pricing_for_a_single_small_school'], $landing['pricing_ugx_150k'], $landing['pricing_1_500_students'], [$landing['pricing_students_attendance'], $landing['pricing_fees_tracking'], $landing['pricing_report_cards'], $landing['pricing_data_migration'], $landing['pricing_staff_training']], false],
                    ['building', $landing['pricing_growth'], $landing['pricing_for_primary_secondary_schools'], $landing['pricing_ugx_350k'], $landing['pricing_500_900_students'], [$landing['pricing_everything_in_starter'], $landing['pricing_sms_announcements'], $landing['pricing_multi_branch_support'], $landing['pricing_data_migration'], $landing['pricing_staff_training']], true],
                    ['school-care', $landing['pricing_enterprise'], $landing['pricing_for_school_groups_large_institutions'], $landing['pricing_custom_price'], $landing['pricing_1_000_students'], [$landing['pricing_everything_in_growth'], $landing['pricing_dedicated_onboarding'], $landing['pricing_sla_backed_support'], $landing['pricing_data_migration'], $landing['pricing_staff_training']], false],
                ] as [$icon, $title, $description, $price, $capacity, $features, $featured])
                    <article class="reference-plan {{ $featured ? 'reference-plan-featured' : '' }}">
                        <div class="plan-summary">
                            <div class="plan-header-row">
                                @include('partials.landing-icon', ['name' =>$icon])<h3 class="plan-name">{{ $title }}</h3>
                            </div>
                            <div class="plan-amount">{{ $price }} @if(! $loop->last)<small>{{ $landing['pricing_term'] }}</small>@endif</div>
                            <p class="plan-capacity">{{ $capacity }}</p>
                            <p class="plan-description">{{ $description }}</p>
                        </div>
                        <ul class="plan-features">
                            @foreach($features as $feature)<li><span class="feature-dot" aria-hidden="true"></span><span>{{ $feature }}</span></li>@endforeach
                        </ul>
                        <div class="plan-footer">
                            <a class="button" href="{{ $loop->last ? $landing['pricing_contact_link'] : $landing['pricing_start_link'] }}">{{ $loop->last ? $landing['pricing_contact_button'] : $landing['pricing_start_button'] }} <span class="plan-action-icon">@include('partials.landing-icon', ['name' =>'arrow'])</span></a>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="included"><strong>{{ $landing['pricing_free_with_every_edlink_package'] }}</strong>@foreach([$landing['pricing_data_migration'], $landing['pricing_school_website'], $landing['pricing_staff_training'], $landing['pricing_ongoing_support']] as $benefit)<span>@include('partials.landing-icon', ['name' => 'check']) {{ $benefit }}</span>@endforeach</div>
        </div>
    </section>

    <section id="contact" class="section contact-section">
        <div class="container contact-layout">
            <div class="contact-copy"><span class="eyebrow">{{ $landing['contact_let_s_make_school_work_simpler'] }}</span><h2>{{ $landing['contact_heading'] }}</h2><p>{{ $landing['contact_description'] }}</p>
                @if(session('contact_status'))<div class="form-status" role="status">{{ session('contact_status') }}</div>@endif
                @if($errors->any())<div class="form-errors" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
                <form method="POST" action="{{ route('contact.store') }}" class="contact-form">
                    @csrf
                    <input type="hidden" name="type" value="contact">
                    <div class="form-row"><label><span class="sr-only">{{ $landing['contact_your_name'] }}</span><input required name="name" value="{{ old('name') }}" autocomplete="name" placeholder="{{ $landing['accessibility_your_name'] }}" maxlength="255"></label><label><span class="sr-only">{{ $landing['contact_email_address'] }}</span><input required type="email" name="email" value="{{ old('email') }}" autocomplete="email" placeholder="{{ $landing['accessibility_email_address'] }}" maxlength="255"></label></div>
                    <label><span class="sr-only">{{ $landing['contact_subject'] }}</span><input required name="subject" value="{{ old('subject') }}" placeholder="{{ $landing['accessibility_how_can_we_help_your_school'] }}" maxlength="255"></label>
                    <label><span class="sr-only">{{ $landing['contact_your_message'] }}</span><textarea required name="message" rows="4" placeholder="{{ $landing['accessibility_tell_us_a_little_about_your_school'] }}" maxlength="5000">{{ old('message') }}</textarea></label>
                    <button class="button" type="submit">{{ $landing['contact_send_message'] }} @include('partials.landing-icon', ['name' =>'diagonal'])</button>
                </form>
                <div class="contact-methods"><a href="https://wa.me/{{ $landing['whatsapp'] }}" target="_blank" rel="noopener noreferrer">@include('partials.landing-icon', ['name' =>'message']) {{ $landing['contact_whatsapp_us'] }}</a><a href="tel:{{ preg_replace('/[^0-9+]/', '', $landing['phone']) }}">@include('partials.landing-icon', ['name' => 'phone']) {{ $landing['phone'] }}</a></div>
            </div>
            <div class="contact-visual" aria-hidden="true"><div class="contact-orbit"><div><img src="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'contact_image_1') }}" alt="" width="340" height="340" loading="lazy"></div></div><div class="contact-circle"><img src="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'about_image') }}" alt="" width="265" height="265" loading="lazy"></div><div class="float-note">@include('partials.landing-icon', ['name' =>'message'])<div><strong>{{ $landing['contact_let_s_talk_about_your_school'] }}</strong>{{ $landing['contact_our_team_is_here_to_help'] }}</div></div></div>
        </div>
    </section>

    @include('partials.client-testimonials')

    <section id="demo-access" class="section">
        <div class="container demo-layout">
            <div class="role-grid">
                @foreach(\App\Support\DemoAccounts::roles() as $role =>$account)<a class="role-card" href="{{ $landing['role_'.$role.'_link'] ?? route('login', ['demo' => $role]) }}">@include('partials.trial-avatar', ['avatarKey' => 'role_'.$role.'_portrait', 'avatarIndex' => ['administrator' => 0, 'class-teacher' => 1, 'subject-teacher' => 2, 'bursar' => 3, 'parent' => 4, 'student' => 5][$role] ?? 0, 'avatarSettings' => $landing])<h3>{{ $landing['role_'.$role.'_label'] ?? $account['label'] }}</h3><p>{{ $landing['role_'.$role.'_description'] ?? $account['description'] }}</p><span class="role-action">{{ $landing['demo_access_open_demo_login'] }} @include('partials.landing-icon', ['name' =>'arrow'])</span></a>
                @endforeach
            </div>
            <div class="demo-copy"><span class="eyebrow">{{ $landing['demo_access_find_your_place_in_edlink'] }}</span><h2>{{ $landing['demo_access_choose_whose_workspace_you_want_to_explore'] }}</h2><p>{{ $landing['demo_access_pick_a_role_to_open_the_matching_demo_login_see_how'] }}</p><a class="button" href="{{ $landing['navigation_login_link'] }}">{{ $landing['demo_access_i_already_have_an_account'] }} @include('partials.landing-icon', ['name' =>'arrow'])</a>
                <div class="faq" aria-label="Frequently asked questions">
                    @foreach([
                        [$landing['demo_access_how_long_is_the_free_demo'], $landing['demo_access_explore_edlink_for_10_days_with_no_payment_card_requ']],
                        [$landing['demo_access_can_we_bring_our_existing_school_records'], $landing['demo_access_yes_existing_student_and_teacher_lists_can_be_import']],
                        [$landing['demo_access_can_we_work_offline'], $landing['demo_access_download_daily_attendance_and_draft_marks_in_advance']],
                        [$landing['demo_access_are_sms_messages_included'], $landing['demo_access_sms_delivery_depends_on_the_school_s_messaging_confi']],
                    ] as [$question, $answer])<details><summary>{{ $question }}</summary><p>{{ $answer }}</p></details>@endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container">
            <div class="section-heading"><span class="eyebrow">{{ $landing['guides_a_better_school_day_starts_here'] }}</span><h2>{{ $landing['guides_less_admin'] }}<br>{{ $landing['guides_more_room_for_what_matters'] }}</h2><p>{{ $landing['guides_explore_the_everyday_workflows_that_keep_a_school_ru'] }}</p></div>
            <div class="guide-grid">
                @foreach([
                    ['people', $landing['guides_school_administration'], $landing['guides_bring_every_learner_into_focus'], $landing['guides_admissions_classes_staff_and_parent_links_organised'], 'guides_image_1', 'administrator'],
                    ['book', $landing['guides_teaching_learning'], $landing['guides_give_teachers_a_clearer_day'], $landing['guides_attendance_marks_and_homework_in_one_workspace_shape'], 'guides_image_2', 'class-teacher'],
                    ['receipt', $landing['guides_school_finance'], $landing['guides_know_where_your_fees_stand'], $landing['guides_follow_payments_receipts_and_balances_without_losing'], 'guides_image_3', 'bursar'],
                ] as [$icon, $label, $title, $description, $image, $role])<article class="guide-card"><div class="guide-photo"><img src="{{ \App\Models\LandingPageSetting::assetUrl($landing, $image) }}" alt="{{ $label }}" width="360" height="230" loading="lazy"><span class="guide-icon">@include('partials.landing-icon', ['name' =>$icon])</span></div><div class="guide-body"><span class="eyebrow">{{ $label }}</span><h3>{{ $title }}</h3><p>{{ $description }}</p><a class="text-link" href="{{ $landing['role_'.$role.'_link'] ?? route('login', ['demo' => $role]) }}">{{ $landing['guides_explore_the_demo'] }} @include('partials.landing-icon', ['name' =>'diagonal'])</a></div></article>@endforeach
            </div>
        </div>
    </section>
<section class="public-enquiry-section" id="contact-support"><div>@include('partials.public-enquiry', ['engagementSource' => 'landing'])</div></section>
</main>
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div><h3>{{ $landing['footer_about_edlink'] }}</h3><p>{{ $landing['footer_one_connected_workspace_for_the_front_office_the_sta'] }}</p><a href="mailto:{{ $landing['support_email'] }}">{{ $landing['support_email'] }}</a></div>
            <div><h3>{{ $landing['footer_school_services'] }}</h3><a href="{{ $landing['footer_features'] }}">{{ $landing['footer_students_attendance'] }}</a><a href="{{ $landing['footer_features'] }}">{{ $landing['footer_fees_accounting'] }}</a><a href="{{ $landing['footer_features'] }}">{{ $landing['footer_academics_reports'] }}</a><a href="{{ $landing['footer_desktop'] }}">{{ $landing['footer_windows_desktop_app'] }}</a><a href="{{ $landing['footer_android'] }}">{{ $landing['footer_android_ios_mobile_apps'] }}</a></div>
            <div><h3>{{ $landing['footer_here_to_help'] }}</h3><a href="{{ $landing['footer_contact_171'] }}">{{ $landing['footer_talk_to_our_team'] }}</a><a href="https://wa.me/{{ $landing['whatsapp'] }}" target="_blank" rel="noopener noreferrer">{{ $landing['footer_whatsapp_support'] }}</a><a href="{{ $landing['footer_pricing'] }}">{{ $landing['footer_plans_pricing'] }}</a><a href="{{ $landing['footer_demo_access'] }}">{{ $landing['footer_try_a_role_based_demo'] }}</a></div>
            <div><h3>{{ $landing['footer_useful_links'] }}</h3><a href="{{ $landing['navigation_login_link'] }}">{{ $landing['footer_school_login'] }}</a><a href="{{ $landing['footer_about'] }}">{{ $landing['footer_about_us'] }}</a><a href="{{ $landing['footer_privacy_link'] }}">{{ $landing['footer_privacy_policy'] }}</a><a href="{{ $landing['footer_terms_link'] }}">{{ $landing['footer_terms_of_service'] }}</a></div>
        </div>
        <div class="footer-brand"><img src="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'footer_logo') }}" alt="{{ $landing['accessibility_edlink_logo'] }}" width="125" height="45" loading="lazy"><p>{{ $landing['footer_bringing_your_school_day_together'] }}</p>
            <div class="socials" aria-label="Edlink social media profiles">@foreach(['facebook_url' => [$landing['footer_facebook'], 'facebook'], 'instagram_url' => [$landing['footer_instagram'], 'instagram'], 'x_url' => [$landing['footer_x'], 'x'], 'linkedin_url' => [$landing['footer_linkedin'], 'linkedin'], 'youtube_url' => [$landing['footer_youtube'], 'youtube'], 'tiktok_url' => [$landing['footer_tiktok'], 'tiktok']] as $key => [$label, $socialIcon])@if(filled($landing[$key] ?? null))<a href="{{ $landing[$key] }}" target="_blank" rel="noopener noreferrer" aria-label="Follow Edlink on {{ $label }}">@include('partials.landing-icon', ['name' =>$socialIcon])</a>@endif @endforeach</div>
        </div>
        <div class="footer-bottom"><span>{{ $landing['footer_'] }} {{ date('Y') }} {{ $landing['footer_text'] }}</span><div><a href="{{ $landing['footer_privacy_link'] }}">{{ $landing['footer_privacy'] }}</a><a href="{{ $landing['footer_terms_link'] }}">{{ $landing['footer_terms'] }}</a><a href="{{ $landing['footer_contact_175'] }}">{{ $landing['footer_contact'] }}</a></div></div>
    </div>
</footer>
<a class="support-float whatsapp-float" href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $landing['whatsapp']) }}" target="_blank" rel="noopener noreferrer" aria-label="Chat with Edlink on WhatsApp">@include('partials.landing-icon', ['name' =>'whatsapp'])</a>
@include('partials.public-consent')
</body>
</html>
