@once
<div data-public-engagement data-choice="{{ request()->cookie('edlink_public_consent','') }}" data-consent-url="{{ route('public.consent') }}" data-visit-url="{{ route('public.visit') }}">
    <section class="edlink-cookie-banner" data-cookie-banner aria-label="Visitor privacy choices" hidden>
        <strong>Your privacy, your choice</strong><p>Essential cookies keep Edlink working. With your permission, optional analytics record your IP address, browser, referral website and public page visits for 30 days. If you submit a help request, we can associate it with those visits. We only receive your name, email or phone when you choose to provide them. Analytics consent does not give permission to contact you.</p>
        <div class="edlink-cookie-actions"><button type="button" data-consent="accept">Accept analytics</button><button type="button" data-consent="reject">Essential only</button><a href="{{ route('privacy') }}#public-website-visitors">Privacy details</a></div><p class="engagement-status" data-cookie-status role="status"></p>
    </section>
    <button class="edlink-privacy-preferences" type="button" data-privacy-preferences aria-expanded="false">Privacy preferences</button>
</div>
@endonce
