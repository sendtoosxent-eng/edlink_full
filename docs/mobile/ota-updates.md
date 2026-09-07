# Edlink over-the-air updates

The mobile project includes SDK-compatible `expo-updates`, fingerprint runtime compatibility, and separate preview/production build channels. It checks for updates at launch without delaying startup; a downloaded update is applied on a later cold start. It does not interrupt a lesson, payment screen, or homework submission to reload.

## Linked Expo project

Edlink is linked to `@osxent1/edlink-mobile` (project ID `f35e35a0-b8bb-48a9-90ec-ed078f75c1da`). The update URL is configured in app.json.

## Account setup on another development machine

Run on your development machine from `mobile/`, not on the Laravel SSH server:

```sh
npm run eas:login
npm run updates:configure
```

Select the Expo account/project that owns Edlink. Configuration must add a real `extra.eas.projectId` and matching `updates.url` to app.json. Preserve `runtimeVersion.policy: fingerprint`, and commit the resulting project linkage. Those fields are already configured for this checkout.

Set the API address in both EAS environments so builds and updates use the same backend:

```sh
npx eas-cli@latest env:create --environment preview --name EXPO_PUBLIC_API_URL --value https://edlink.space/api/v1 --visibility plaintext
npx eas-cli@latest env:create --environment production --name EXPO_PUBLIC_API_URL --value https://edlink.space/api/v1 --visibility plaintext
```

If a variable already exists, inspect it and use `eas env:update` instead. This URL is public configuration, not a secret.

## Install the first update-enabled Android build

```sh
npm run build:preview
```

Install the resulting APK on the emulator or a test phone. Expo Go cannot validate the installed app's OTA lifecycle. Existing installations built before this setup need a new binary. The new build also includes the configured Edlink launcher icon.

For the Play Store build:

```sh
npm run build:production
```

Upload the resulting AAB through the normal Play Store release process. The profiles currently provide Android commands; iOS additionally needs an Apple bundle identifier and signing setup.

## Publish a compatible app change

```sh
npm run update:preview -- --message "Describe the change"
```

Open the preview app online, allow the download, then close and reopen it. Confirm the change and that login, navigation, and homework still work. Then publish the reviewed source to production:

```sh
npm run update:production -- --message "Describe the change"
```

Publishing is explicit: a Git push or Laravel deployment does not publish an OTA update. Native dependencies, native configuration, and icon changes can change the runtime fingerprint and require another build; do not override the fingerprint to force compatibility. Use the same environment when building and publishing to each channel.

If a published update has a problem, run `npx eas-cli@latest update:republish` and select the previous working update and intended channel. If no compatible prior update exists, use `npx eas-cli@latest update:roll-back-to-embedded` for the affected channel/runtime. Check the selections before publishing the rollback.

References: https://docs.expo.dev/eas-update/getting-started/ and https://docs.expo.dev/eas-update/runtime-versions/
