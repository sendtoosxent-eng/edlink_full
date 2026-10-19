# School production release checklist

This checklist is a release gate for a real-school deployment. It is not legal
advice; the school and vendor should have it reviewed for the countries in which
they operate.

## Contract and governance

- [ ] Identify the school as data controller and Edlink/vendor as processor, or
      document a different lawful allocation of responsibilities.
- [ ] Sign a service agreement and data-processing addendum covering purpose,
      instructions, confidentiality, security, subprocessors, incident notice,
      deletion/return, audit rights, support, uptime, and exit assistance.
- [ ] Confirm company registration, tax treatment, intellectual-property
      ownership, third-party licences, warranty limits, and insurance.
- [ ] Complete any required data-controller/processor registration with the
      relevant data-protection authority.
- [ ] Name the privacy contact and security incident owner.

## Learners, parents, and staff

- [ ] Document the lawful basis for every category of learner, parent, staff,
      finance, attendance, health, and communications data.
- [ ] Obtain parental/guardian consent where required and provide age-appropriate
      notices to learners.
- [ ] Restrict health, safeguarding, disciplinary, and financial data to the
      minimum roles that need it.
- [ ] Validate that exports, report cards, receipts, notifications, and support
      messages cannot cross school or family boundaries.
- [ ] Train administrators on access reviews, secure exports, phishing, and
      incident reporting.

## Privacy operations

- [ ] Publish approved privacy and terms text with a fixed effective date and a
      monitored contact address.
- [ ] Approve a retention schedule for each data class; configure deletion and
      archival jobs to match it.
- [ ] Rehearse access, correction, portability, objection, and deletion requests
      and record response deadlines.
- [ ] Maintain a subprocessor and international-transfer register for hosting,
      email, SMS, object storage, analytics, and support providers.
- [ ] Document breach triage, containment, evidence preservation, regulator and
      affected-person notification, and post-incident review.

## Security and resilience

- [ ] Use unique production secrets, HTTPS, `APP_DEBUG=false`, encrypted secure
      cookies, least-privilege database credentials, and MFA for hosting accounts.
- [ ] Configure `BACKUP_OFFSITE_REQUIRED=true` with a private, encrypted,
      versioned S3-compatible bucket in a separate failure domain.
- [ ] Run `php artisan edlink:backup --restore-test` and complete a full restore
      rehearsal into an isolated database; record date, operator, duration, and
      result.
- [ ] Configure `SYSTEM_ALERT_WEBHOOK_URL` to an externally monitored channel and
      test a deliberate alert.
- [ ] Enable hosting, database, mail, SMS, and object-storage audit logs where
      available; document log retention and access.
- [ ] Run the automated test suite, dependency audits, mobile type check, and
      production asset build from a clean checkout.
- [ ] Review all unresolved dependency advisories and record accepted risk,
      compensating controls, owner, and review date.

## Acceptance and launch

- [ ] Complete role-by-role user acceptance testing with anonymised or synthetic
      data on representative phones, tablets, browsers, printers, and networks.
- [ ] Confirm fee opening balances and totals, grading rules, report templates,
      terms, classes, subjects, timetable, sender identities, and school branding.
- [ ] Agree support hours, response targets, escalation contacts, maintenance
      windows, training, onboarding, and renewal terms.
- [ ] Approve a migration reconciliation report and retain the source-to-target
      mapping without exposing credentials or unnecessary personal data.
- [ ] Prepare and rehearse launch rollback, including database compatibility and
      mobile release rollback limitations.
- [ ] Obtain written school acceptance before enabling production accounts.

## Recurring controls

- [ ] Daily: verify scheduler, queue, backup, and health-monitor results.
- [ ] Monthly: review privileged users, failed logins, audit events, incidents,
      dependency advisories, storage growth, and restore-point availability.
- [ ] Quarterly: perform an isolated restore, access recertification, and privacy
      request drill.
- [ ] Annually or after material change: refresh risk assessment, contracts,
      policies, training, penetration testing, and disaster-recovery exercise.
