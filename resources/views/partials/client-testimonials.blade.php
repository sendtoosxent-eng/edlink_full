<section id="testimonials" class="section lavender testimonials-section" aria-labelledby="testimonials-heading">
    <div class="container testimonials-layout">
        <div class="testimonials-preview">
            <div class="testimonial-cards">
                @foreach([
                    [$landing['head_teacher_records_benefit'], 'avatar-sarah'],
                    [$landing['head_teacher_teaching_benefit'], 'avatar-daniel'],
                    [$landing['head_teacher_finance_benefit'], 'avatar-brian'],
                    [$landing['head_teacher_parent_benefit'], 'avatar-grace'],
                ] as [$benefit, $avatar])
                    <figure class="testimonial-card {{ in_array($loop->index, [1, 2], true) ? 'testimonial-purple' : '' }}">
                        @if(filled($landing['testimonial_portrait_'.($loop->index + 1)] ?? ''))
                            <img class="testimonial-portrait" src="{{ \App\Models\LandingPageSetting::assetUrl($landing, 'testimonial_portrait_'.($loop->index + 1)) }}" alt="" width="57" height="57" loading="lazy">
                        @else
                            <span class="memoji-avatar {{ $avatar }}" aria-hidden="true"></span>
                        @endif
                        <div class="testimonial-body">
                            <div class="quote-decoration" aria-hidden="true">@include('partials.landing-icon', ['name' => 'school-care'])<span></span></div>
                            <p class="head-teacher-benefit">{{ $benefit }}</p>
                            <figcaption><strong>{{ $landing['head_teacher_role_label'] }}</strong><span>{{ $landing['head_teacher_school_label'] }}</span></figcaption>
                            <span class="review-signature-line" aria-hidden="true"></span>
                        </div>
                    </figure>
                @endforeach
            </div>
        </div>
        <div class="testimonials-copy">
            <span class="eyebrow">{{ $landing['head_teacher_section_label'] }}</span>
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
