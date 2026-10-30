# Public visitor analytics and enquiries

Run `php artisan migrate --force` when deploying. Keep the existing Laravel scheduler running (`php artisan schedule:run` every minute); it removes visitor analytics older than 30 days. No external analytics service or messaging credentials are required.

Landing page and demo registration provide direct WhatsApp/email support and an optional callback form. Support destinations and introductory form text use Platform → Website content. Submitted requests appear in Platform → Visitors & enquiries; Support Centre also links to this screen. Existing contact form conversations remain in Support Centre.

Optional analytics require acceptance and record only landing/login/registration paths, timestamps, encrypted IP, browser and referral hostname. Names, school locations, emails and numbers are voluntarily submitted. Declining analytics does not prevent a support request. Choosing Essential only deletes this browser’s visitor and page-view history and unlinks enquiries, without deleting requested support.

Platform owner, operations administrator and support administrator can manage enquiries. Only owner and operations administrators can see visitor IP addresses. Status and notes are internal; saving them never sends a message. Mark Do not contact when permission is withdrawn. Requests with recorded opt-out cannot be reopened; a new visitor request is required.

Follow-up permission is separate from analytics and covers the submitted request. These leads never populate school parent WhatsApp lists. No messages are sent automatically. Enquiries remain until handled under the operator’s retention/deletion process.

Tests and browser verification have not been run for this feature, per the user’s instruction.
