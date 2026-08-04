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
   domain, database, and SMTP settings.
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
