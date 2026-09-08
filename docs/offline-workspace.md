# Offline web workspace

The first offline release supports **daily learner attendance** and **draft examination marks**. Open **Offline workspace** in the web app header, or bookmark `/offline/`.

## Staff workflow

1. Sign in online, open the workspace, and set a device PIN of at least six characters. A longer passphrase gives better protection. The PIN is local and cannot be recovered.
2. Download each class/date or exam paper needed before disconnecting. Only downloaded registers and papers are available offline; choose future attendance dates in advance when needed.
3. Keep using this workspace offline, including after reloading or reopening its bookmarked URL. Unlock with the same PIN. Valid scores save as you type and attendance saves when you select a status; use **Mark all present** for a complete daily register.
4. When the connection returns, leave the workspace unlocked and open. It checks for sync every 30 seconds while you are not editing a field. **Sync now** starts a check immediately. If the session expired, sign in online to the same account and school, then unlock again.
5. If someone changed an edited learner's record online, the batch stays pending. Open **Review online changes**, choose the online value or your value for each pending edit, then retry. A removed learner's edit must be explicitly discarded. Submitted/approved papers must be reopened online; closed terms and revoked permissions require the appropriate school administrator's intervention.
6. Submit and approve marks using the normal online marks page after syncing. Lock the offline workspace when leaving a shared device. Once all edits are synced, **Remove device data** clears the downloads and PIN.

Use **Export pending changes** to keep a human-readable JSON copy if a record cannot be synced. Export files contain unencrypted learner names and values and should be kept private. This is a manual recovery export, not an automatic import format. Clearing browser/site data or losing the PIN can lose unsynced work.

## Deployment

- Deploy `public/offline/`, `app/Http/Controllers/OfflineWorkspaceController.php`, and the route, exception-rendering, and layout changes together. No migration or new npm dependency is needed.
- Serve over HTTPS in production. Localhost/127.0.0.1 supports local development. Use a current browser supporting service workers, IndexedDB, Web Crypto, and Web Locks. These secure-context requirements are described in [MDN's service worker documentation](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API).
- The web server must serve `/offline/index.html` for the `/offline/` directory and serve `.js` files as JavaScript. Apache's normal index handling and `php artisan serve` support this; ensure any custom Nginx config includes `index.html`.
- The service worker scope is `/offline/` only. It caches a fixed set of public HTML/CSS/JS assets and never caches authenticated pages or API responses. Increment its `CACHE` version whenever changing any offline shell file. Keep all files in each release compatible because already-open tabs can finish using the previous version.
- This implementation expects Edlink at the domain root, matching existing root-relative web routes.
- No syncing runs while the browser is closed or the workspace is locked. No offline login, fee payments, receipts, admissions, approvals, or general dashboard browsing is included.

## Data and conflict handling

The browser stores one account/school vault encrypted with AES-GCM in IndexedDB. Its non-exportable key is derived in memory from the PIN with PBKDF2-SHA-256 and a random salt. The PIN/key is never sent to the server or persisted. Web Locks prevents two tabs from editing the same vault concurrently. Changing the online account or branch locks the workspace; syncing also validates the original owner server-side.

Downloads include an authenticated encrypted server snapshot identifying the owner, term, selectors, and per-learner baseline. Sync accepts only changed rows, validates the roster and current access again, and compares row identity, timestamp, and value. Changed online values cause an atomic batch rollback. Identical values make retries safe after lost responses. The server does not silently switch old attendance to a newly active term. Successful writes are audited as `web.offline.attendance.synced` or `web.offline.marks.synced`.

An offline device cannot learn that access has been revoked until it reconnects. Users with the offline PIN can read their previously downloaded data while disconnected.

## Checks

```sh
php artisan test --compact tests/Feature/OfflineWorkspaceTest.php tests/Feature/AttendanceAccessTest.php tests/Feature/TeacherAcademicScopeTest.php
node --test tests/js/offline-storage.test.js
```

Before production rollout, try the full workflow on the school's target devices: download, disconnect, edit, reload/unlock, reconnect, and confirm the online records. Also try a second device changing the same record and an expired login session.
