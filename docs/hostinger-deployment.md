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

Replace the account path below with the absolute path shown by Hostinger. Add
these cron entries in hPanel:

```cron
* * * * * /usr/bin/php /home/ACCOUNT/domains/DOMAIN/public_html/artisan schedule:run >> /dev/null 2>&1
* * * * * /usr/bin/php /home/ACCOUNT/domains/DOMAIN/public_html/artisan queue:work --stop-when-empty --tries=3 >> /dev/null 2>&1
```

On a VPS, use a process supervisor for `queue:work` instead of the second cron
entry. The scheduler runs expiry, backups, monitoring, renewal reminders, and
model pruning.

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
