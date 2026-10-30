# School WhatsApp announcements

This is a Meta Cloud API pilot for individual parent announcements, using each school's own sending number. It does not automate WhatsApp Web. Existing in-app/email/SMS broadcasts remain separate.

## Deployment

- Deploy the code and run `php artisan migrate --force` and clear cached routes/config/views with the normal deployment process.
- Set `QUEUE_CONNECTION=database` (or a supported persistent queue), keep `php artisan queue:work --tries=100 --timeout=30` running, and run Laravel's scheduler every minute. The configured queue retry interval must exceed the job timeout.
- `WHATSAPP_GRAPH_VERSION` defaults to `v23.0`. Select a supported Graph API version for the Meta app when upgrading the integration.
- Use HTTPS on the public Edlink domain; Meta must be able to reach `/api/whatsapp/webhook/{school-key}`.

## First school

1. Open **Settings → Communication → WhatsApp Business connection**. In Meta, configure the school's Business account, sending number, billing and an app with WhatsApp access. Obtain the WABA ID, Phone Number ID, suitable access token and App Secret. Never send credentials in chat or commit them.
2. Save credentials with sending disabled. Credentials are encrypted in the database and stored token/secret values are not hydrated into Livewire properties.
3. Copy the callback URL and reveal the Verify Token into Meta's webhook configuration. Subscribe to `messages`. The App Secret verifies POST signatures over the exact raw request body.
4. Click **Verify & refresh templates**; then enable sending and save. Connection verification makes read-only Meta API requests; it does not send a message. Credentials/number changes invalidate verification.
5. Create announcement templates in WhatsApp Manager and obtain approval. This pilot reads the first 100 templates and supports approved Utility/Marketing text templates with numbered BODY variables. Headers/buttons/carousels/named variables are not offered. Include opt-out instructions in the approved template.
6. Open **Communications → WhatsApp announcements**. Import parent/guardian numbers. Imports never grant consent. Record the parent's explicit permission and its source before sending. Ugandan local numbers beginning with 0 are normalized to 256; other countries need the country calling code.
7. Choose a template, fill its variables, select all opted-in parents/classes/individual contacts, and preview. Confirm only after reviewing the exact message, count and applicable Meta charges. One contact per normalized school/phone number prevents duplicate announcements for siblings.

Settings require `settings.manage`. Sending/history require `parents.communications`. Contact import/consent edits also require `parents.manage`. Livewire actions enforce permissions and school scope again on the server.

## Delivery and safety

- Sending creates immutable broadcast snapshots and per-parent deliveries. Preview fingerprints prevent sending altered content or a changed eligible recipient list without another preview. Preview keys prevent duplicate submission of the same confirmed batch.
- School queues are serialized and initially limited to 30 jobs/minute. Meta 429 responses requeue after 60 seconds. School licensing, enabled configuration, approved template snapshot and current consent are rechecked immediately before each send.
- Delivery status is `queued`, `sending`, `accepted`, `sent`, `delivered`, `read`, `failed`, `unknown`, or `skipped`. Accepted is not proof of delivery. Signed callbacks update statuses without downgrading delivered/read messages.
- Timeouts, ambiguous server responses or interrupted workers are not automatically resent. Meta's opaque callback data can correlate a late receipt even if the original response was lost. Review unknown messages with the provider before any new broadcast.
- The scheduler recovers confirmed deliveries whose initial queue push failed. It never creates a new broadcast or adds new recipients.
- Incoming STOP, UNSUBSCRIBE, OPT OUT and CANCEL withdraw consent for that school. Recording new permission is required before an administrator opts the parent back in. Importing again does not restore consent.
- No automatic SMS fallback or arbitrary free-form WhatsApp sending is included. It is possible to use existing SMS announcements separately.

Meta account setup, template approvals, billing and a real consenting-parent pilot are external activation steps. No WhatsApp messages or read-only verification calls are sent merely by installing the code.
