# Late October and November demo trends

From the application directory:

```sh
php artisan edlink:seed-autumn-demo
```

The command targets the four public demo schools (EDL-KINDER, EDL-TEACH,
EDL-SECOND and EDL-VOCAT), and requires each selected school to be explicitly
marked `is_demo`. It refuses real schools and validates prerequisites before
writing. To target one school:

```sh
php artisan edlink:seed-autumn-demo --school=EDL-TEACH
```

The default range is **October 20–November 30, 2026**, inclusive. An optional
`--year=2026` selects the calendar year; each school's current term must be open,
unlocked and in that year. The command does not switch or rename terms.

It adds daily attendance on weekdays for active learners with classes, with
varying present/late/absent figures. Existing daily registers are preserved.
There are three to five synthetic fee collections per weekday, across cash,
mobile money and bank, plus varied operating expenses on Mondays, Wednesdays
and Fridays. Fee collections populate the income/payment trend charts. Finance
records have posted ledger entries, cash-pool entries and balanced accounting
journals with separate demo maker/checker users. Fee receipts follow existing
receivable/advance accounting rules rather than inventing extra revenue.

References and notes identify the records as synthetic demo data. Future-dated
November records are intentional for display, including November 30. No money
was actually collected or spent. Receipt emails and app activity notifications
are not sent. Reruns preserve existing transactions and do not duplicate the
command's records; rejected/reversed transactions are not silently overwritten.

Local execution only updates the local database. For the hosted demo, deploy
this command and run it on the server against the hosted database. Refresh the
web dashboard afterwards; select the year/month/date in chart filters as needed.
