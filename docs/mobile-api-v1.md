# Edlink Mobile API v1

Base path: `/api/v1`. All successful responses use `{ "data": ..., "meta": ... }`. Validation uses Laravel's structured `422` error envelope. Authentication is a device-specific Sanctum bearer token.

## Authentication

`POST /auth/login` accepts `school_number`, `email`, `password`, and `device_name`. `GET /auth/me` restores a session. `POST /auth/logout` revokes only the current device token.

## Read endpoints

- `/dashboard`, `/timetable`, `/events`, `/announcements`
- `/attendance`, `/homework`, `/homework/{id}`
- `/exam-papers`, `/exam-papers/{id}/marks`, `/results`
- `/children`, `/activities`

Parents pass `student_id` only for a learner returned by `/children`. Students are resolved through their portal link. Unlinked and cross-school identifiers return `404`. Teachers are constrained by `TeacherAcademicScope`.

## Writes and conflicts

- `POST /attendance` sends a class/session and learner statuses.
- `PUT /exam-papers/{id}/marks` sends draft marks.
- `POST /exam-papers/{id}/submit` locks a paper into the existing approval workflow.
- `POST /homework` creates a published assignment.
- `POST /homework/{id}/submit` creates or updates the linked learner submission.

Draft writes may include `base_version`. If the server has newer data it returns `409` with `code: "conflict"`; clients must refresh and let the user reconcile.

Every route is rate-limited, authenticated writes are audited, and all queries are school-scoped. The API intentionally excludes finance, medical notes, addresses, payroll, and unrelated administration.
