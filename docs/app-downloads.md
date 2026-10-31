# Landing page app downloads

Publish the existing installers from the application directory:

```sh
php artisan edlink:publish-desktop desktop/dist/Edlink-Setup-1.0.1-x64.exe 1.0.1
php artisan edlink:publish-android mobile/builds/Edlink-1.0.0-Preview-OTA.apk
```

The public download endpoints are `/downloads/edlink-windows` and
`/downloads/edlink-android`. Both return attachments without requiring login.
The landing page enables each button when its installer is available.
A published APK takes priority over the optional Android download URL in
Website Content Management; that URL remains a fallback when no APK is stored.

For production, deploy the application changes, upload the installers to the
server and run these commands there using their uploaded paths. Preserve
`storage/app/private` and the Windows release database records across deployments.
Installer binaries are not committed to Git. The current APK is the existing
1.0.0 preview build with OTA support; publishing does not rebuild the mobile app.
