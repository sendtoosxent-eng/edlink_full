# Teacher workspace

The mobile dashboard uses `dashboard.data.teacher_workspace`. The server computes class responsibility from `school_classes.class_teacher_user_id`, subject scope from `TeacherAcademicScope`, and additional tools from the same permission keys used by the website. A class teacher retains subject tools when assigned to subjects. School designation permissions can add further tools.

Native workflows include daily/subject attendance, assigned exam papers, draft marks and submission for approval, homework creation, submission scoring/feedback, notifications, and leave requests/history. Published performance averages match exact class–subject pairs. Mark submission requires a complete sheet and an open term; approved/submitted sheets remain protected in mobile.

My Classes, Teaching, Reports and My School open native access screens. Every permission-granted catalog tool has a native destination; teacher screens contain no browser or WebView fallback. Dedicated screens cover learner profiles, exam results and assigned timetables. Other modules use native searchable records, selectors, forms and paginated PDF exports through the allowlisted teacher tools API. The server checks tool permissions, school and assignment scope for each request and reuses existing domain validation for writes.

Homework supports authenticated attachment downloads and uploads. Images and text have in-app previews; PDFs use the native print interface and other file types use the operating system sharing interface. PDF exports contain the displayed records, not every page of the result set.

Remaining website parity work: specialized ID-card and graduation document layouts, additional staff/parent profile editing, event reminders/deletion, leave approvals and some advanced accounting/asset administration. Native catalog coverage does not establish full action parity with every website module. These tools must not silently reopen the browser.

Deploy the Laravel changes before reviewing the full dashboard against the production API. No database migration is required. Existing app sessions remain usable; refresh Home after deployment. The mobile bundle must include the corresponding UI changes. Run `npm ci` in `mobile` when updating the checkout; document picker, filesystem, print and sharing packages were added.

Validation: `php artisan test --compact tests/Feature/MobileApiTest.php tests/Feature/TeacherWorkspaceTest.php tests/Feature/TeacherAcademicScopeTest.php`, and `npx tsc --noEmit` in `mobile`. Coverage includes every permission-granted catalog destination, cross-school access, role scope, approved marks protection, shared report calculations and attachment authorization. Android bundled successfully and the dashboard was visually checked on the Pixel 8 emulator; full new-screen visual verification still requires the deployed API or a local authenticated test session.
