# Edlink School Management System

Edlink is a multi-school platform for academic administration, attendance,
finance, communications, reporting, and parent/student access. The repository
contains a Laravel web/API application, an Expo mobile application, and a native
SwiftUI iOS application.

## Product areas

- School and licence administration with tenant isolation
- Staff, student, parent, class, subject, and timetable management
- Attendance, homework, examinations, report cards, and PDF exports
- Fee ledgers, receipts, adjustments, approvals, and finance reporting
- Announcements, email/SMS workflows, audit trails, and support tickets
- Role-aware mobile dashboards with secure token storage and biometric unlock
- Verified local backups with optional required off-site object-storage copies
- Health monitoring with in-app and external webhook alerts

## Requirements

- PHP 8.3 (the lock file is resolved for PHP 8.3.35)
- Composer 2
- Node.js 22 and npm
- SQLite for local development or MySQL for production
- `mysqldump` for production database backups
- Xcode/iOS Simulator or Android Studio for native mobile development

## Local setup

```sh
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Start the queue worker and scheduler in separate terminals when testing queued
communications, exports, reminders, backups, or monitoring:

```sh
php artisan queue:work --tries=3
php artisan schedule:work
```

For the mobile app:

```sh
cd mobile
npm ci
npm run ios
```

Set the API base URL from the app's environment or development configuration as
appropriate for the target device. Never embed production credentials in a
mobile build.

For the native Swift iOS app, open `ios-native/Edlink.xcodeproj` in Xcode,
select the **Edlink** scheme and an iPhone simulator, then run. See the
[native iOS guide](ios-native/README.md) for supported workflows, configuration,
checks, and remaining release work.

## Quality checks

```sh
composer validate --strict
composer audit --locked
php -d memory_limit=768M artisan test
./vendor/bin/pint --test path/to/changed.php
npm run build

cd mobile
npx expo install --check
npx tsc --noEmit
npm audit --omit=dev --audit-level=critical
```

The GitHub Actions workflow runs these checks for pushes and pull requests and
applies Pint incrementally to PHP files changed by that push or pull request.
High-severity advisories in transitive Expo build tooling must be reviewed when
Expo publishes compatible fixes; never use `npm audit fix --force` if it
downgrades the supported Expo SDK.

## Production operations

- Follow [the Hostinger deployment guide](docs/hostinger-deployment.md).
- Complete [the school release and compliance checklist](docs/compliance/release-checklist.md)
  before onboarding real learners or staff.
- Configure `BACKUP_OFFSITE_REQUIRED=true`, an S3-compatible disk, and an
  external monitoring webhook in production.
- Keep `APP_DEBUG=false`, use HTTPS, run the queue continuously, and run the
  scheduler every minute.
- Rehearse a database restore into an isolated database before every major
  release and at least quarterly.

## Security reporting

Do not open public issues containing learner data, credentials, or security
details. Report suspected vulnerabilities and privacy incidents to the address
configured by `LEGAL_CONTACT_EMAIL`. Rotate any exposed secret immediately and
record the response in the incident register.

## Data protection and licensing

Deployments handling school and learner records must be reviewed against the
applicable privacy, child-protection, education, tax, and records-retention laws.
The included policy pages and checklist are operational starting points, not
legal advice. Confirm software ownership and distribution terms before a sale;
third-party packages retain their own licences.
