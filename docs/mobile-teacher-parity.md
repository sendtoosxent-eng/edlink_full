# Teacher web and mobile parity

Reviewed 2026-10-22. The web app is the behavior reference. Access must follow the
same school, designation, class, subject, term, and learner subject-selection
rules. A visible menu entry or generic list does not establish feature parity.

## Core teacher workflows

| Workflow | Mobile implementation | Status / remaining work |
| --- | --- | --- |
| Teacher dashboard and assigned classes | `DashboardScreens`, `/dashboard` | Assignment summaries, today's lessons and pending registers exist. Pending subject attendance now tracks each scheduled lesson separately. |
| Daily attendance | `TeacherRegisterScreen`, `/attendance` | Class, date, stream, statuses, reasons, late minutes, learner photos, search and mark-all-present. Stream metadata now persists. |
| Subject attendance | `TeacherRegisterScreen`, `SubjectAttendanceRegisters` | Web and mobile share scheduled lessons, stream restrictions, fallback assignments, learner subject selection and session keys. Each lesson is a separate register. |
| Learner attendance performance | `TeacherAttendancePerformance`, `/attendance/performance` | Uses the web calculation and CSV export, with class, stream and date filters. Includes enrolled learners with no records and treats late as attended. |
| Attendance summaries | `TeacherAttendanceReportScreen`, `/teacher/attendance-summary` | Existing shared web class/category summaries, date/class/stream/subject/status/search filters and Excel export. |
| Homework creation | `AddHomeworkScreen`, `/homework` | Assigned class/subject, instructions, due time, maximum score and attachments exist. Added class stream selection; assignment discovery no longer depends on attendance permission. |
| Homework review | `TeacherHomeworkScreen` | Existing assignment details, learner answers/attachments, score and feedback. Further UI comparison needed for review counts and submission metadata. |
| Marks | `TeacherMarksScreen`, `/exam-papers` | Existing drafts, complete-sheet submission, read-only submitted marks and conflict responses. Recheck every web validation and closed-term history behavior. Teacher web access does not grant publishing or marks approval merely because results are visible. |
| Exam results | `NativeReportsScreen`, `/teacher/exams` | Existing calculated results/readiness and text sharing. Native summaries do not replace the full web report-card layout or its PDF/print behavior. |
| Timetable | `TeacherScheduleScreen`, `/timetable` | Existing assigned weekly lessons. Lesson attendance now uses the same session identifiers as web. |
| Learner directory/profile | `TeacherDirectoryScreen`, `NativeStudentProfile` | Existing scoped search, profiles and permission-controlled edits. Compare all guardian, enrolment, status and file actions individually. |
| Leave | `LeaveRequestScreen`, `/leave-requests` | Existing personal request/history. Delegated approval/replacement flows require a separate comparison. |
| Notices/calendar/notifications | Native teacher tools and calendar | Existing reading, read state and attachments. Compare filters, audience rules and delegated management actions individually. |
| Houses and clubs | `NativeModuleScreen`, native teacher tools | Existing scoped membership actions and school services. Compare patron views, house details and member lists with web. |

## Delegated tools that still need action-level parity

These tools are exposed according to permissions, but their current generic forms
and lists need a fuller comparison before they can be called equivalent to web:

- Student ID cards: a learner list is not the web card preview, selection and PDF.
- Graduates/alumni: the list lacks the complete graduation/external-results workflow.
- Portal access: linking forms exist; compare account lists, unlinking and reset actions.
- Promotions: compare the native binding to the active web V2 workflow and its validations.
- Full student/term/bulk reports: verify filters, assessment grouping, remarks and PDF output.
- Subjects, exam setup, grading, classes/streams and timetable management: verify every action and dependent field.
- Any additional staff, parent, finance or administration permissions assigned to a teacher: compare the authorized actions independently rather than assuming a shared generic form covers them.

## Compatibility and verification

The new app uses `/attendance/registers?attendance_date=YYYY-MM-DD` to discover
registers. Subject sessions use the web's `subject:ID:slot:ID` or
`subject:ID:class:ID:assignment` keys. Earlier app versions can continue sending
their legacy subject sessions. Existing historical rows are retained; they are
not assigned to a particular scheduled lesson automatically.

The server validates lesson ownership and daily-register streams. The mobile app
shows lesson times, keeps separate reminder keys for repeated lessons, and uses
the server's current-term availability to disable saving in a closed term.

Regression coverage added to `MobileApiTest.php` exercises repeated lessons,
shared session keys, unassigned lesson rejection, parent rejection, stream
filtering, cross-stream write rejection, stream metadata, shared attendance rates
and CSV downloads. Device interaction still needs a signed-in teacher review.

Verification results for this batch:

- Mobile TypeScript check passed.
- New lesson, stream and performance regression tests passed.
- Broader mobile API suite: 36/38 passed before the final stream test was added.
  Receipt authorization (expected 404, received 403) and unpublished-mark dashboard
  averages (expected 75, received 0) also fail on committed baseline `7c0c02d`.
- Teacher scope/workspace/subject-selection suites: 12/13 passed; the learner-directory
  test expects an unenrolled learner to be shown. This failure also reproduces on
  committed baseline `7c0c02d`.
