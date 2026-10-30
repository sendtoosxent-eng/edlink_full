@php
    $engagementSettings = $landing ?? \App\Models\LandingPageSetting::values();
    $isDemoHelp = ($engagementSource ?? 'landing') === 'demo_setup';
@endphp
<details class="edlink-enquiry">
    <summary>{{ $isDemoHelp ? $engagementSettings['public_demo_help_title'] : $engagementSettings['public_callback_title'] }}</summary>
    <div class="edlink-enquiry-body">
        <p>{{ $engagementSettings['public_support_description'] }}</p>
        <div class="edlink-support-links"><a href="https://wa.me/{{ preg_replace('/[^0-9]/','',$engagementSettings['whatsapp']) }}" target="_blank" rel="noopener noreferrer">WhatsApp our team ↗</a><a href="mailto:{{ $engagementSettings['support_email'] }}">{{ $engagementSettings['support_email'] }}</a></div>
        <p>{{ $engagementSettings['public_callback_description'] }}</p>
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
</details>
