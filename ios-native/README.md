# Edlink for iOS

Native Swift / SwiftUI client for Edlink’s existing Laravel API. This is a separate
Xcode app; the Expo client and Laravel application remain available.

## Run

Open `Edlink.xcodeproj`, select the **Edlink** scheme and an iPhone simulator, and
press Run. Requires Xcode 16.2 or newer; supports iOS 17+ and iPad.

For a physical iPhone, select your Apple development team under Signing &
Capabilities and use an available bundle identifier. The checked-in identifier
is `space.edlink.native`; no signing account or credentials are included.

The default server is `https://edlink.space/api/v1`. Change `EdlinkAPIURL` in
`Edlink/Info.plist` for a staging deployment. Use HTTPS; the app does not disable
App Transport Security. Sign in with an active school's number and a verified
teacher, student, or parent account. Server permissions and term restrictions
continue to apply.

## Implemented flows

- School verification, role selection, password sign-in, email OTP/resend,
  password-reset email requests and token-based password reset
- Keychain session restoration, current-device logout, expired-session handling,
  optional Face ID/device-passcode lock and hidden app-switcher previews
- Role-aware home screen, parent learner switching, timetable, school calendar,
  announcements and paginated in-app notifications with mark-read
- Daily and subject attendance registers, stream selection, attendance reasons,
  validation, draft conflict handling and learner attendance history
- Exam-mark drafts, numeric validation and submission for approval; published
  learner results with subject scores
- Homework creation, attachments, student answers/files, submission history,
  teacher feedback/scoring and authenticated file downloads
- Fee balances, posted payments and downloadable parent receipts
- Teacher learner search, learner profiles/editing, leave requests and history
- Permission-based staff workspace populated by the server, including native
  schema-driven forms, filters, pagination, editing, multiselects and repeated
  fields for the existing teacher-tools API
- Teacher exam reports, learner result sharing, attendance report date/status/search filters
- Teacher lesson-absence reporting from today's timetable
- Teacher account name/phone editing

Staff forms expose the actions returned by the API for that teacher. They do
not grant additional access. The server remains authoritative for approvals,
validation, tenancy, closed terms and learner access.

## Verify

```sh
xcodebuild -project ios-native/Edlink.xcodeproj -scheme Edlink \
  -configuration Debug -sdk iphonesimulator \
  -destination 'generic/platform=iOS Simulator' \
  -derivedDataPath /tmp/edlink-ios-build CODE_SIGNING_ALLOWED=NO build

swift test --package-path ios-native --scratch-path /tmp/edlink-core-tests
```

The Swift package tests the app's actual shared networking/session source on
macOS. It is a test harness, not the iOS app entry point. Tests cover response
envelopes, pagination, null values, conflict versions, URL query encoding,
validation errors, bearer-token requests, unauthorized responses and malformed
server responses. They use an isolated mock transport and never contact a school.

## Backend compatibility

Deploy the accompanying `TeacherDirectoryController` update before enabling
learner-profile editing. It includes the learner ID number (`lin`) and admission
date so a profile round trip preserves existing identity data. The Swift app
hides learner editing on older servers whose payload omits `lin`.

## Release work still required

This implementation needs end-to-end testing with staging accounts for all
three roles and each relevant staff permission. A passing build and core tests
do not establish that every backend-driven form works with real school data.
Check accessibility, large text, dark mode, iPad layouts and slow/offline networks
on physical devices. Some reports currently use a general native record/detail
screen rather than a tailored report layout. The app does not yet provide report
PDF/Excel exports, profile-photo uploads, offline write
queues, APNs push delivery, or universal-link password-reset handling. Reset tokens
can be entered in the account-recovery sheet; emailed web reset links still work.

Before TestFlight/App Store distribution, configure signing, review the app icon
and product copy, complete the privacy disclosures based on the deployed
backend, add the public support/privacy URLs and store screenshots, and test an
archive on a physical device. The privacy manifest declares local preference
access; it is not a substitute for App Store data-collection disclosures.

School responses use an ephemeral session with no shared disk cache. Tokens are
stored with `WhenUnlockedThisDeviceOnly`. Files are downloaded into Edlink's own
temporary directory and removed on local sign-out; files a user shares or saves
outside the app are controlled by that destination.

## Design and flow shared with the TypeScript app

The SwiftUI interface now reuses the original Edlink logo files, all three
onboarding photos, role illustrations, dashboard SVG artwork, Poppins fonts and
Ionicons glyphs. The app icon comes from the same `Edlink 1logo.png` configured in
`mobile/app.json`. Font licences are bundled in `Edlink/Fonts`.

The entry sequence follows `mobile/src/screens/auth/AuthFlow.tsx`: animated splash,
three introductions with Skip/Back, swipeable teacher/student/parent roles,
school lookup, email/account lookup, password, OTP and password recovery. Account
lookup must succeed before the password screen is shown. OTP resend has the same
60-second countdown. School branding and the account photo are supplied by the API.

The authenticated interface follows the original role-specific four-tab layouts,
workspace sections, cards, typography, search destinations, charts, homework
filters, results, fees, profile and settings. Native date/file pickers and sheet
presentations still use iOS controls.

`Scripts/sync-artwork.cjs` rasterizes the original SVG components for the asset
catalogue using `@resvg/resvg-js`; it does not generate replacement artwork. All
runtime assets are checked in, so the app needs no Node dependency to build.

Debug builds support screenshot-only launch arguments:

```sh
xcrun simctl launch booted space.edlink.native --edlink-auth-step=connect
xcrun simctl launch booted space.edlink.native --edlink-auth-step=role
xcrun simctl launch booted space.edlink.native --edlink-auth-step=school
xcrun simctl launch booted space.edlink.native --edlink-auth-step=password
xcrun simctl launch booted space.edlink.native --edlink-design-role=teacher
```

Terminate the app between preview launches. Dashboard previews and the later
sign-in preview steps use fictional data. They do not create or save a session;
normal sign-in still uses the real API. Preview launch arguments are excluded
from Release builds. Launch without arguments for the real onboarding flow.
