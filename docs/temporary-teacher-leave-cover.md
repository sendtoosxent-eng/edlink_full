# Temporary teacher leave cover

Approved leave uses inclusive start and end dates. The staff directory displays
On leave during that interval and Available afterward for active staff. This is
computed from approved leave records on each request; it does not depend on cron.
Employment status remains separate from leave availability.

## Approval

Approvers must select a replacement when the requester has a class-teacher role,
subject assignments, or timetable lessons. This also applies to academic
administrators who teach or hold class duties. A replacement must belong to the
same school, be active and available throughout the leave, and have no overlapping
lessons. Class-teacher-only duties cannot bypass the handover step.

A handover records the original teacher, replacement, duty snapshot and approver.
Permanent class ownership, staff subject rows and timetable ownership remain
unchanged. The effective academic scope includes the original teacher's duties
for the temporary replacement during the approved dates.

## Access and reminders

During cover, the replacement sees assigned classes and subjects, the relevant
student directory and profiles, results, mark sheets, attendance registers,
timetable lessons, and pending attendance reminders. The web teacher workspace
shows a temporary-cover notice. Mobile APIs use the same effective scope and
include temporary cover details and an on-leave flag in the workspace response.
Subject-only cover does not grant a class-teacher role or daily-register access.
Cover does not grant finance, HR or administrative permissions.

Original teachers do not receive pending attendance reminders while on leave.
Their account is not deactivated, and their permanent assignments are retained.

## Return and replacement changes

On the day after the end date, the original teacher becomes available and the
replacement loses the temporary scope automatically. The replacement's own
permanent assignments remain. An approver can Record return to end leave cover
early, or Change cover to switch replacements immediately. Records and audit
history remain after access ends.

A teacher who is covering another approved leave cannot have overlapping leave
approved until that existing cover is reassigned. Likewise, the replacement's
account cannot be deactivated while they have an ongoing or future cover
commitment. New class, subject and timetable assignments reject teachers
currently on leave both in the UI and on the server.

## Deployment and compatibility

Run `php artisan migrate --force` before serving the updated code. Existing
historical handovers made by the previous permanent-transfer workflow retain
`temporary=false`; they are not silently rewritten or treated as new overlays.
New approvals use date-limited handovers. No ongoing legacy transfers existed in
the local database when this feature was checked.

## Verification

`LeaveCoverTest.php` verifies mandatory class-only handover, unchanged permanent
ownership, scoped student/result/register/timetable access, reminders, future
activation, inclusive end dates, automatic expiry, early return, unavailable or
foreign replacements, partial time overlaps, blocked assignments, cover changes,
and prevention of replacement deactivation.
