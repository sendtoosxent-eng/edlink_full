const FEED = 'https://edlink.space/updates/windows/';

function startUpdates({ app, dialog, window, updater, platform = process.platform, timers = globalThis }) {
  if (platform !== 'win32' || !app.isPackaged) return { check: async () => {}, stop: () => {} };
  updater.setFeedURL({ provider: 'generic', url: FEED });
  updater.autoDownload = true;
  updater.autoInstallOnAppQuit = true;
  updater.allowDowngrade = false;
  updater.allowPrerelease = false;
  // This server serves full installers, not differential blockmaps.
  updater.disableDifferentialDownload = true;
  let checking = false;
  let downloaded = false;
  let prompting = false;
  let lastCheck = 0;
  async function offerRestart() {
    if (prompting) return;
    prompting = true;
    try {
      const { response } = await dialog.showMessageBox(window, {
        type: 'info', title: 'Edlink update ready', message: 'An Edlink update is ready.',
        detail: 'It will install automatically when you close Edlink. To restart now, save your work and sync any pending offline changes first.',
        buttons: ['Install when I close Edlink', 'Restart now'], defaultId: 0, cancelId: 0
      });
      if (response === 1) updater.quitAndInstall(false, true);
    } finally { prompting = false; }
  }
  updater.on('update-downloaded', () => { downloaded = true; void offerRestart().catch(() => {}); });
  // Offline, checksum and signature failures leave the working app untouched.
  updater.on('error', () => {});
  async function check(manual = false) {
    if (checking) return;
    if (downloaded) { if (manual) await offerRestart(); return; }
    checking = true;
    lastCheck = Date.now();
    try {
      const result = await updater.checkForUpdates();
      if (manual && result && !result.isUpdateAvailable) await dialog.showMessageBox(window, { type: 'info', message: 'Edlink is up to date.', buttons: ['OK'] });
      else if (manual && result?.isUpdateAvailable) await dialog.showMessageBox(window, { type: 'info', message: 'An Edlink update is downloading.', detail: 'You can keep working. Edlink will let you know when it is ready.', buttons: ['OK'] });
      // Keep the in-flight lock until the automatic download completes.
      if (result?.downloadPromise) await result.downloadPromise;
    } catch {
      lastCheck = Date.now() - 4 * 60 * 60 * 1000 + 5 * 60 * 1000;
      if (manual) await dialog.showMessageBox(window, { type: 'info', message: 'Updates are unavailable right now.', detail: 'You can keep using Edlink. We will check again automatically.', buttons: ['OK'] });
    } finally { checking = false; }
  }
  const initial = timers.setTimeout(() => { void check(); }, 15_000);
  const periodic = timers.setInterval(() => { void check(); }, 4 * 60 * 60 * 1000);
  const focus = () => { if (Date.now() - lastCheck > 4 * 60 * 60 * 1000) void check(); };
  window.on('focus', focus);
  const stop = () => { timers.clearTimeout(initial); timers.clearInterval(periodic); window.removeListener('focus', focus); };
  app.once('before-quit', stop);
  return { check, stop };
}

module.exports = { FEED, startUpdates };
