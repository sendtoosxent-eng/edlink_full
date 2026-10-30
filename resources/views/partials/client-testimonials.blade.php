<section id="testimonials" class="section lavender testimonials-section" aria-labelledby="testimonials-heading">
    <div class="container testimonials-layout">
        <div class="testimonials-preview">
            <p class="sample-reviews-label">{{ $landing['testimonials_sample_testimonials_fictional_names'] }}</p>
            <div class="testimonial-cards">
                @foreach([
                    [$landing['testimonials_sarah_namusoke'], $landing['testimonials_school_administrator'], $landing['testimonials_one_place_for_learner_records_staff_details_and_clas'], 'avatar-sarah'],
                    [$landing['testimonials_daniel_okello'], $landing['testimonials_teacher'], $landing['testimonials_keeping_attendance_marks_and_homework_together_would'], 'avatar-daniel'],
                    [$landing['testimonials_brian_kato'], $landing['testimonials_bursar'], $landing['testimonials_a_clear_view_of_payments_receipts_and_balances_is_ex'], 'avatar-brian'],
                    [$landing['testimonials_grace_nankunda'], $landing['testimonials_parent'], $landing['testimonials_following_results_fee_balances_and_school_notices_fr'], 'avatar-grace'],
                ] as [$name, $role, $quote, $avatar])
                    <figure class="testimonial-card {{ in_array($loop->index, [1, 2], true) ? 'testimonial-purple' : '' }}">
                        @if(filled($landing['testimonial_portrait_'.($loop->index + 1)] ?? ''))
                            <img class="testimonial-portrait" src="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'testimonial_portrait_'.($loop->index + 1)) }}" alt="" width="57" height="57" loading="lazy">
                        @else
                            <span class="memoji-avatar {{ $avatar }}" aria-hidden="true"></span>
                        @endif
                        <div class="testimonial-body">
                            <div class="quote-decoration" aria-hidden="true">@include('partials.landing-icon', ['name' =>'quote'])<span></span></div>
                            <blockquote><p>{{ $quote }}</p></blockquote>
                            <figcaption><strong>{{ $name }}</strong><span>{{ $role }} {{ $landing['testimonials_sample'] }}</span></figcaption>
                            <span class="review-signature-line" aria-hidden="true"></span>
                        </div>
                    </figure>
                @endforeach
            </div>
        </div>
        <div class="testimonials-copy">
            <span class="eyebrow">{{ $landing['testimonials_client_testimonials'] }}</span>
            <h2 id="testimonials-heading">{{ $landing['testimonials_school_stories'] }}<br>{{ $landing['testimonials_human_connections'] }}</h2>
            <p>{{ $landing['testimonials_designed_for_the_people_keeping_schools_moving_from'] }}</p>
            <div class="testimonial-stats" aria-label="Edlink product at a glance">
                @php
                    $moduleValue = $landing['stat_one_value'];
                    $moduleIsNumeric = preg_match('/\A(\d+)(\+?)\z/', $moduleValue, $moduleParts);
                    $roleCount = count(\App\Support\DemoAccounts::roles());
                @endphp
                <p class="product-stats-label">{{ $landing['testimonials_edlink_at_a_glance'] }}</p>
                <div class="review-stat">
                    <div class="review-stat-label"><span>@include('partials.landing-icon', ['name' => 'book']) {{ $landing['stat_one_label'] }}</span><strong><span class="sr-only">{{ $moduleValue }}</span><span aria-hidden="true">@if($moduleIsNumeric)<span data-count-to="{{ $moduleParts[1] }}">{{ $moduleParts[1] }}</span>{{ $moduleParts[2] }}@else{{ $moduleValue }}@endif</span></strong></div>
                    <div class="stat-progress-track" aria-hidden="true"><span class="stat-progress-fill"></span></div>
                </div>
                <div class="review-stat">
                    <div class="review-stat-label"><span>@include('partials.landing-icon', ['name' =>'people']) {{ $landing['testimonials_role_based_workspaces'] }}</span><strong><span class="sr-only">{{ $roleCount }}</span><span aria-hidden="true" data-count-to="{{ $roleCount }}">{{ $roleCount }}</span></strong></div>
                    <div class="stat-progress-track" aria-hidden="true"><span class="stat-progress-fill"></span></div>
                </div>
            </div>
            <a class="button" href="{{ $landing['testimonials_demo_access'] }}">{{ $landing['testimonials_see_edlink_in_action'] }}<span class="plan-action-icon">@include('partials.landing-icon', ['name' =>'arrow'])</span></a>
        </div>
    </div>
</section>
