# School designations

Each school receives nine editable access presets:

| Designation | Initial access |
| --- | --- |
| Super Administrator | All listed school permissions, including accounting approvals and controls |
| Administrator | School operations, staff and designation management, finance and accounting preparation; excludes journal approval/posting/reversal, reopening periods and opening balance management |
| Head Teacher | Academics, admissions, staff management, attendance, leave, payroll, communication and reports |
| Class Teacher | Class attendance, marks, results, events, parent communication and reports |
| Subject Teacher | Subject attendance, subjects, timetable, marks, results and reports |
| Bursar | Payments, adjustments, expenses, payroll, ledger, accounting preparation, assets and reconciliation |
| Dean of Studies | Admissions, academic planning, attendance, examinations, staff directory and reports |
| Head of Department | Subject and timetable coordination, events, examinations, staff directory, communication and reports |
| Head of Subject | Subject and timetable coordination, attendance reports, marks, results and reports |

Permissions apply when a designation is assigned; saving permission changes
updates access for its assigned staff on subsequent requests. Existing account
role privileges and teaching assignment access continue to apply. Department
and subject head presets grant module permissions; they do not create department
or subject assignments or introduce new organisational data scopes.

Administrators and staff with **Manage designations and access** can create
custom designations and edit defaults. Assigning a designation that itself
grants designation management requires that same access management permission.
Defaults can be renamed and edited but cannot be deleted. Unused custom
designations can be deleted after staff are reassigned.

Stable default identifiers preserve behaviour after renaming. Existing
customised permissions are retained, and legacy DOS/Director of Studies
designations are adopted as Dean of Studies without changing their staff links.
The school Super Administrator designation grants school permissions and does
not create a platform administrator account.

Run `php artisan migrate --force` on deployment to add default identifiers and
initialise defaults for existing schools. New school onboarding and the staff
and designation screens also ensure that defaults are available.
