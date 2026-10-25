# Deploying Edlink to Hostinger shared hosting

Edlink requires PHP 8.3 or newer, MySQL, Composer, cron jobs, and Apache rewrite
support. The frontend assets must be built before deployment.

## Recommended layout

If hPanel allows the domain document root to be changed, upload the repository
outside `public_html` and set the domain document root to the repository's
`public` directory. The existing `public/.htaccess` is the only rewrite file
needed in this layout.

If the document root cannot be changed, upload the repository contents into
`public_html`, then copy `deployment/hostinger-root.htaccess` to
`public_html/.htaccess`. Keep `public/.htaccess` in place. The root rule forwards
requests to Laravel's public directory.

Never upload a real `.env` file to Git or include it in a public download.

## School login subdomains

Set the tenant root domain in production:

```env
APP_URL=https://edlink.space
EDLINK_ROOT_DOMAIN=edlink.space
```

Create a wildcard DNS record for `*.edlink.space` that points to the same server
as `edlink.space`, configure the wildcard host to use this application's `public`
directory, and enable an SSL certificate that covers `*.edlink.space`. If the
hosting plan does not support wildcard hosts, each school subdomain must be added
to hPanel when it is assigned in the platform dashboard.

The platform school-onboarding form stores only the subdomain label, such as
`godisable`. A request to `https://godisable.edlink.space/` then redirects to
`https://godisable.edlink.space/login`. The normal school-number field remains
part of the login process.

## First deployment

1. In hPanel, select PHP 8.3 or newer and create a MySQL database and user.
2. Upload or clone the project, including the locally generated `public/build`
   directory.
3. Copy `.env.hostinger.example` to `.env` on the server. Fill in the real
   domain, database, and SMTP settings. `MAIL_USERNAME` must be the complete
   Hostinger mailbox address, `MAIL_PASSWORD` must be that mailbox's password
   (not the hPanel account password), and `MAIL_FROM_ADDRESS` should match the
   authenticated mailbox.
4. From the project root, run:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

Generate `APP_KEY` only on the first deployment. Keep that same key during
future deployments or encrypted data and sessions will become unreadable.

Ensure `storage` and `bootstrap/cache` are writable by PHP. Do not grant write
permission to the entire project.

## Cron jobs

Payroll rollover requires `php artisan migrate --force` to create the monthly
salary balance table. The existing daily `edlink:payroll-reminders` task at 09:00
creates reminders for administrators and bursars, including earlier unpaid months.
Opening payroll also updates monthly balances, so missed scheduler runs do not
lose salary months. Reminders require the scheduler cron entry below.

Initial balances start at the staff member's joining month (account creation
month if no joining date exists). Existing posted salary and advance payments
reduce their assigned month's balance; pending payments do not. Historical
monthly salary amounts use an existing posted payment's salary snapshot when
available, otherwise the current base salary. Check joining dates, salary history,
and historical payments before relying on backfilled arrears. Once saved, a
month's salary amount is preserved when the staff member's base salary changes.

Replace the account path below with the absolute path shown by Hostinger. Add
these cron entries in hPanel:

```cron
* * * * * /usr/bin/php /home/ACCOUNT/domains/DOMAIN/public_html/artisan schedule:run >> /dev/null 2>&1
* * * * * /usr/bin/php /home/ACCOUNT/domains/DOMAIN/public_html/artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1
```

On a VPS, use a process supervisor for `queue:work` instead of the second cron
entry. The scheduler runs expiry, MySQL backups, monitoring, renewal reminders,
and model pruning. Backups are compressed into
`storage/app/private/backups`, verified by checksum and dump-header validation,
and pruned after `BACKUP_RETENTION_DAYS` (30 by default). Production must set
`BACKUP_OFFSITE_REQUIRED=true`, `BACKUP_OFFSITE_DISK=s3`, and valid S3-compatible
credentials. Each verified backup is then copied to private object storage and
the command fails if the required remote copy cannot be confirmed. Use a bucket
in a separate account or failure domain, with encryption, versioning, retention,
and least-privilege credentials enabled.

Test the production backup manually after confirming that `mysqldump` is
available on the account:

```sh
command -v mysqldump
php artisan edlink:backup --restore-test --keep-days=30
ls -lh storage/app/private/backups
```

Confirm the same backup exists in the configured off-site bucket. Also configure
`SYSTEM_ALERT_WEBHOOK_URL` to a channel monitored outside the hosting account;
the scheduled health check reports database, queue, scheduler, disk, backup, and
off-site configuration failures there.

The MySQL `--restore-test` verifies the compressed dump and checksum; a full
restore should still be rehearsed against a separate database, never the live
database.

## Testing email

After changing any mail setting, rebuild Laravel's cached configuration:

```sh
php artisan optimize:clear
php artisan config:cache
```

Send a direct SMTP test from the project root, replacing the recipient:

```sh
php artisan tinker --execute="Mail::raw('Edlink SMTP test', fn (\$message) => \$message->to('you@example.com')->subject('Edlink SMTP test'));"
```

Verification, password-reset, announcement, and renewal emails are queued. Run
the queue once manually while diagnosing them:

```sh
php artisan queue:work --stop-when-empty --tries=3 -v
php artisan queue:failed
```

If SMTP port 465 is unavailable, use Hostinger's alternative STARTTLS settings:
`MAIL_SCHEME=smtp` and `MAIL_PORT=587`. Never use `MAIL_SCHEME=tls`; the scheme
is a mail transport (`smtp` or `smtps`), while TLS is selected by that transport.
Check `storage/logs/laravel.log` for the exact SMTP or queue exception.

## Updating

Enable maintenance mode only for the short migration window:

```sh
php artisan down
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan up
```

If an update fails before `artisan up`, run it manually after fixing the error.
