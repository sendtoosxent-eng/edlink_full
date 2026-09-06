# EDL-TEACH current-term app demo

From the deployed Laravel project directory (the directory containing `artisan`):

```sh
git pull --ff-only origin main
php artisan db:seed --class=EdlTeachCurrentTermDemoSeeder --force
php artisan view:cache
```

The seeder requires school EDL-TEACH to be marked as a demo and its current term to be open and unlocked. It does not switch terms, reset accounts, or change student names. It adds data for every active learner with a class: two synthetic payments totaling UGX 150,000, seven days of attendance, and a published assessment with Mathematics, English and Science scores. Payments have synthetic posted ledger entries for mobile visibility; this is demonstration data, not actual receipts or reconciled accounting. No receipt notifications are sent.

Rerunning does not duplicate payments or assessment papers. Attendance is added for the latest seven days, preserving existing demo dates. The command reports the actual current term used. These records supplement existing records.

Refresh or reopen the app's Home, School fees, Results, and Attendance screens after running the command. The default mobile API is https://edlink.space/api/v1, so local database seeding alone does not populate the hosted app.

To add three class-wide lessons (Mathematics 08:00–08:40, English 09:00–09:40, Science 10:00–10:40) every Monday through Sunday for every class, including empty classes, run:

```sh
php artisan db:seed --class=EdlTeachTimetableDemoSeeder --force
```

This separate seeder targets only EDL-TEACH's open current term, preserves existing lessons, and does not duplicate its own lessons on reruns. These class-wide demo slots are visible to students in every stream; they do not assign teachers.

For three published homework tasks per class (Mathematics number practice, English writing, and Science healthy habits), run:

```sh
php artisan db:seed --class=EdlTeachHomeworkDemoSeeder --force
```

Each task is worth 20 marks and is due at 17:00 in 3, 4, or 5 days from its initial creation. These are simple app-testing exercises shared across class levels. The seeder selects the existing class.teacher@edlink.local teacher, falling back to another teacher in the school. It preserves existing assignments, submissions, and deadlines on reruns. Reopen My homework to load the published tasks.
