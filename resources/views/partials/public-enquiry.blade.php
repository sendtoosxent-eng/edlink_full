@php
    $engagementSettings = $landing ?? \App\Models\LandingPageSetting::values();
    $isDemoHelp = ($engagementSource ?? 'landing') === 'demo_setup';
@endphp
@if($isDemoHelp)
<button type="button" class="edlink-help-trigger" data-support-open aria-haspopup="dialog" aria-controls="demo-support-dialog">{{ $engagementSettings['public_demo_help_title'] }} <span aria-hidden="true">↗</span></button>
<dialog id="demo-support-dialog" class="edlink-support-dialog" aria-labelledby="demo-support-heading" wire:ignore.self>
    <div class="support-dialog-heading"><h2 id="demo-support-heading">{{ $engagementSettings['public_demo_help_title'] }}</h2><button type="button" class="support-dialog-close" data-support-close aria-label="Close support form">×</button></div>
    <div class="edlink-enquiry-body">
        <p>{{ $engagementSettings['public_support_description'] }}</p>
        <div class="edlink-support-links"><a href="https://wa.me/{{ preg_replace('/[^0-9]/','',$engagementSettings['whatsapp']) }}" target="_blank" rel="noopener noreferrer">WhatsApp our team ↗</a><a href="mailto:{{ $engagementSettings['support_email'] }}">{{ $engagementSettings['support_email'] }}</a></div>
        <p>{{ $engagementSettings['public_callback_description'] }}</p>
@else
<section class="edlink-support-panel" aria-labelledby="school-support-title">
    <div class="edlink-support-copy">
        <span class="support-eyebrow">{{ $engagementSettings['public_support_eyebrow'] }}</span>
        <div class="support-book" aria-hidden="true"><svg viewBox="0 0 80 80" fill="none"><path d="M40 22C29 15 18 16 10 20v40c10-4 20-3 30 3 10-6 20-7 30-3V20c-8-4-19-5-30 2Z" stroke="currentColor" stroke-width="3" stroke-linejoin="round"/><path d="M40 23v39M18 30c5-1 10 0 15 2m-15 8c5-1 10 0 15 2m14-10c5-2 10-3 15-2m-15 12c5-2 10-3 15-2" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg></div>
        <h2 id="school-support-title">{{ $engagementSettings['public_callback_title'] }}</h2>
        <p>{{ $engagementSettings['public_support_description'] }}</p>
        <div class="support-contact-options">
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/','',$engagementSettings['whatsapp']) }}" target="_blank" rel="noopener noreferrer"><span class="support-contact-icon">@include('partials.landing-icon', ['name'=>'whatsapp'])</span><span><small>{{ $engagementSettings['public_whatsapp_label'] }}</small><strong>+{{ preg_replace('/[^0-9]/','',$engagementSettings['whatsapp']) }}</strong></span><span aria-hidden="true">↗</span></a>
            <a href="mailto:{{ $engagementSettings['support_email'] }}"><span class="support-contact-icon" aria-hidden="true">@</span><span><small>{{ $engagementSettings['public_email_label'] }}</small><strong>{{ $engagementSettings['support_email'] }}</strong></span><span aria-hidden="true">↗</span></a>
        </div>
    </div>
    <div class="edlink-support-form">
        <span class="support-form-label">{{ $engagementSettings['public_callback_form_label'] }}</span>
        <p>{{ $engagementSettings['public_callback_description'] }}</p>
@endif
        <form method="POST" action="{{ route('public.enquiry') }}" data-public-enquiry data-no-loader>
            @csrf
            <input type="hidden" name="source" value="{{ $isDemoHelp ? 'demo_setup' : 'landing' }}"><input type="hidden" name="purpose" value="{{ $isDemoHelp ? 'demo_help' : 'callback' }}">
            <div class="engagement-honeypot" aria-hidden="true"><label>Leave this field empty<input name="fax_number" tabindex="-1" autocomplete="off"></label></div>
            <div class="engagement-fields"><label>Your name<input name="name" autocomplete="name" maxlength="255" required></label><label>School name (optional)<input name="school_name" autocomplete="organization" maxlength="255"></label><label>School location (optional)<input name="school_location" maxlength="255" placeholder="e.g. Kampala"></label><label>Preferred contact method<select name="contact_method"><option value="email">Email</option><option value="whatsapp">WhatsApp</option><option value="phone">Phone call</option></select></label><label>Email address<input name="email" type="email" autocomplete="email" maxlength="255" required></label><label>Phone / WhatsApp number<input name="phone" type="tel" autocomplete="tel" maxlength="40" placeholder="+256…"></label><label class="engagement-wide">How can we help? (optional)<textarea name="message" rows="3" maxlength="3000" placeholder="Tell us where you’re stuck or what you’d like to know."></textarea></label></div>
            <label class="engagement-followup"><input type="checkbox" name="followup_consent" value="1" required><span>{{ \App\Models\PublicEnquiry::CONSENT_TEXT }}</span></label>
            <p class="engagement-note">You can request help without accepting analytics. Contact us to withdraw follow-up permission. <a href="{{ route('privacy') }}#public-website-visitors">How we use your details</a></p>
            <button type="submit" class="engagement-submit">Ask the team to contact me →</button><p class="engagement-status" data-enquiry-status role="status" aria-live="polite"></p>
        </form>
    </div>
@if($isDemoHelp)
</dialog>
@else
</section>
@endif
