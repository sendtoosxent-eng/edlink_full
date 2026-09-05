# Teacher workspace

The mobile dashboard uses `dashboard.data.teacher_workspace`. The server computes class responsibility from `school_classes.class_teacher_user_id`, subject scope from `TeacherAcademicScope`, and additional tools from the same permission keys used by the website. A class teacher retains subject tools when assigned to subjects. School designation permissions can add further tools.

Native workflows include daily/subject attendance, assigned exam papers, draft marks and submission for approval, homework creation, submission scoring/feedback, notifications, and leave requests/history. Published performance averages match exact class–subject pairs. Mark submission requires a complete sheet and an open term; approved/submitted sheets remain protected in mobile.

Tools marked Website open the existing web pages: student administration, subject selection, report cards, attendance reports, timetable management, clubs/houses, and other permission-granted modules. Browser sign-in is separate. Attachments and advanced mark/leave actions also have website links. This is access parity via native and web tools, not a complete native rebuild of the website.

Deploy the Laravel changes before reviewing the full dashboard against the production API. No database migration is required. Existing app sessions remain usable; refresh Home after deployment. The mobile bundle must include the corresponding UI changes.

Validation: `php artisan test --compact tests/Feature/MobileApiTest.php`, and `tsc --noEmit` in `mobile`. Emulator verification covered the native assigned-paper list; full role-aware dashboard visual verification requires the deployed API or a local authenticated test session.
