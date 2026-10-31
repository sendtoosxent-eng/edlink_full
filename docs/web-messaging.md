# Web messaging

Messages is available at `/messages`, in the sidebar module list, in staff
workbench modules and in the dashboard user profile dropdown. All active,
verified school accounts can use it, independently of announcement permissions.
The mobile application and its API are unchanged.

This first version supports private one-to-one text conversations, name search,
unread counts, sent/read receipts and older message loading. The newest 50
messages load initially; users can load up to 500 recent messages. The contact
list shows up to 50 matching accounts, with recent conversations first.
Search by name to find another contact.

Open message pages use Livewire polling every two seconds while visible.
The unread badge in the profile dropdown refreshes every ten seconds while
visible. Hidden tabs are throttled by Livewire. This is automatic near-real-time
delivery using HTTP, rather than WebSocket push, and does not require a new
server process or third-party chat service. At larger concurrent usage, move
delivery to private broadcast channels with an authenticated WebSocket server.

## Deployment

Deploy the code, then run:

```sh
php artisan migrate --force
php artisan optimize:clear
```

Messages are stored in `chat_messages`. Back up this table with the school
database. No queue worker, extra credentials, asset build or WebSocket daemon
is required for this version.

## Access and limits

Only the sender and recipient can retrieve a conversation through Messages.
Every query includes the active school, and every action rechecks account,
licence and recipient access. A branch change invalidates an already-open chat;
reload Messages to continue in the new school. Users with access to multiple
branches appear in the contact directory for their authorized branches.
Inactive accounts and parents/students without active linked learners are
excluded. This version allows any eligible account to contact another eligible
account in the same school, including student-to-student conversations.

Messages are plain text, escaped when rendered, limited to 5,000 characters,
and limited to 30 sends per user per minute. A unique send token prevents
duplicate messages on retries. Read receipts acknowledge only the incoming
messages included in the open conversation response.

Group chats, attachments, typing indicators, blocking/reporting, video lessons
and mobile chat are outside this first version. Messages are stored in the
school database; they are not end-to-end encrypted.

## Verification

```sh
php artisan test --compact tests/Feature/MessagesTest.php tests/Feature/NotificationCenterTest.php
```
