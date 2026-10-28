# Edlink Windows desktop (internal)

The desktop app and web app use the same hosted Laravel application, accounts,
permissions and cloud records. No PHP server or school database is bundled.
The default address is https://edlink.space/login. The Edlink menu can select an
HTTPS school subdomain of edlink.space. Arbitrary external servers are rejected.

## Packaging

From `desktop`, run `npm ci`, `npm test` and `npm run build:windows`.
The NSIS installer is written to `desktop/dist/` and creates Start menu and
desktop shortcuts. Windows x64 is the initial target. The GitHub Actions
Windows desktop installer workflow builds on a Windows runner and retains the
installer as an artifact; it does not publish a release or deploy the server.

## Landing-page download

The public `/downloads/edlink-windows` endpoint downloads the active Windows
release registered in `desktop_releases`. The database stores version, private
file path, filename, byte size, SHA-256 and SHA-512 checksums, publication date and active
status. The binary is streamed from private server storage, rather than held
as a large database blob. No login is needed to download the installer.

After deploying the migration and application changes, upload the built `.exe`
to the server and register it from the application directory:

```sh
php artisan migrate --force
php artisan edlink:publish-desktop /path/to/Edlink-Setup-1.0.1-x64.exe 1.0.1
```

The command copies the installer to private `desktop-releases/` storage before
publishing its database record. Preserve that storage across deployments; the
binary is not committed to Git. The card shows a download only when an active,
already-published record has an available file. To retire a release, set its
`is_active` column to false. Register newer installers with their own version.
Release records are global platform data, not attached to individual schools.

The device mockup lives at `public/img/edlink-desktop-mockup.png` and can be
replaced through Website Content Management's Desktop app image field. It is
an illustrative product mockup, not a capture of customer records. Its source
and generation prompt are documented in `docs/desktop-mockup.md`.

## Automatic program updates (1.0.1 onward)

The installed Windows app checks `https://edlink.space/updates/windows/` after
launch and every four hours while open, downloads newer stable versions and
installs them when the user closes Edlink. An optional Restart now action asks
the user to save/sync first. No forced restart occurs during school work. The
Check for updates menu can retry manually. Offline failures leave the installed
version usable. School-address settings cannot change the update server.

The server generates `latest.yml` from the release database. Publishing a new
installer with `edlink:publish-desktop` calculates the updater's SHA-512 hash and
enables its feed entry. Installer URLs contain the SHA-256 digest and version,
so a subsequent release cannot change an in-progress download. Range requests
support resuming. Differential downloads are disabled; full installers are used.
The updater retains normal hash/signature verification and refuses downgrades.
Every changed installer must have a higher app version. The publication command
rejects replacing an existing version with a different binary.

Version 1.0.0 had no updater. An existing 1.0.0 installation must first run the
1.0.1 installer over it once; no uninstall is required. Use update-enabled
installers for the initial school rollout. Keep old release files available
while clients may still be downloading them.

Unsigned test builds display an unknown-publisher warning. For signed production
builds set `WIN_CSC_LINK` and `WIN_CSC_KEY_PASSWORD` securely, then run
`npm run build:windows:signed`. This requires signing to succeed and lets
Electron derive the publisher identity for subsequent update verification.
The Windows workflow uses this signed build when the certificate secret is
configured, otherwise producing an unsigned test artifact. No certificate or
password belongs in Git. Signed Windows-to-Windows upgrade verification is a
release check; mock tests cannot verify the real installer lifecycle.

Hosted Edlink UI changes appear on reload without a program update.

## Local data

The dedicated persistent Electron profile retains cookies and browser storage
under the user's Windows application data directory. The hosted `/offline/`
workspace uses its existing PIN-derived encrypted IndexedDB vault. Cloud data
is authoritative; offline support is limited to downloaded daily attendance and
draft marks. Sign in, open the offline workspace and download the required
records while connected before relying on offline access. Each school origin
has separate browser storage. The normal web browser has a separate profile:
downloads prepared in a browser do not automatically appear in the desktop app.

Use Edlink → Offline workspace even when the login cannot load. The cached
service worker serves the workspace after its first successful preparation.
Unlock and keep the workspace open to sync when reconnected. The existing
conflict review, term checks and permission checks apply. No sync runs when
the app is closed or the vault is locked. Removing app data loses unsynced edits;
sync first. Logout does not delete the encrypted vault; lock it on shared
computers and use Remove device data when finished with a device.

## Security and release verification

Remote pages have no preload or Node access. Context isolation, renderer
sandboxing and web security remain enabled. Permissions are denied by default,
navigation stays on the chosen school origin, and external HTTPS popup links
require confirmation before opening the system browser. Certificate errors
are not bypassed. The settings bridge exists only in the local settings window
and validates its calling frame.

Before release on Windows, check installation/uninstallation, school login,
logout/relaunch, school subdomains, receipt/report printing and file downloads.
Prepare offline records, disconnect, close/reopen, unlock, edit and reconnect;
confirm server records and try a concurrent edit from the web browser. Also
check expired sessions, wrong PIN and switching school addresses. Automated
policy/storage tests do not replace this Windows acceptance check.
