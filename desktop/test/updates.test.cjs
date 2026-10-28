const { test } = require('node:test');
const assert = require('node:assert/strict');
const { EventEmitter } = require('node:events');
const { FEED, startUpdates } = require('../updates.cjs');

function fixture(options = {}) {
  const updater = new EventEmitter();
  let checks = 0;
  let installs = 0;
  const dialogs = [];
  updater.setFeedURL = value => { updater.feed = value; };
  updater.checkForUpdates = async () => { checks++; return { isUpdateAvailable: false }; };
  updater.quitAndInstall = () => { installs++; };
  const app = Object.assign(new EventEmitter(), { isPackaged: true });
  const window = new EventEmitter();
  const callbacks = [];
  const cleared = [];
  const timers = { setTimeout: fn => (callbacks.push(fn), 1), setInterval: fn => (callbacks.push(fn), 2), clearTimeout: id => cleared.push(id), clearInterval: id => cleared.push(id) };
  const dialog = { showMessageBox: async (_window, config) => { dialogs.push(config); return { response: options.restart ? 1 : 0 }; } };
  const updates = startUpdates({ app, dialog, window, updater, timers, platform: 'win32', ...options });
  return { updater, updates, app, window, callbacks, cleared, dialogs, checks: () => checks, installs: () => installs };
}

test('checks a fixed HTTPS feed and downloads without allowing downgrades', async () => {
  const f = fixture();
  await f.updates.check();
  assert.equal(f.updater.feed.url, FEED);
  assert.equal(f.updater.autoDownload, true);
  assert.equal(f.updater.autoInstallOnAppQuit, true);
  assert.equal(f.updater.allowDowngrade, false);
  assert.equal(f.checks(), 1);
  f.app.emit('before-quit');
  assert.deepEqual(f.cleared, [1, 2]);
});

test('a downloaded update never forces an automatic restart', async () => {
  const f = fixture();
  f.updater.emit('update-downloaded');
  await new Promise(resolve => setImmediate(resolve));
  assert.equal(f.installs(), 0);
  assert.match(f.dialogs[0].detail, /save your work/);
});

test('restart is explicit and offline errors leave the app available for retry', async () => {
  const f = fixture({ restart: true });
  f.updater.checkForUpdates = async () => { throw new Error('Offline'); };
  await f.updates.check(true);
  assert.equal(f.installs(), 0);
  f.updater.emit('error', new Error('Checksum mismatch'));
  f.updater.emit('update-downloaded');
  await new Promise(resolve => setImmediate(resolve));
  assert.equal(f.installs(), 1);
});

test('does not check for updates in development or on other platforms', async () => {
  const f = fixture({ platform: 'darwin' });
  await f.updates.check();
  assert.equal(f.checks(), 0);
  assert.equal(f.callbacks.length, 0);
});

test('does not launch another check while a download is in flight', async () => {
  const f = fixture();
  let finish;
  const downloadPromise = new Promise(resolve => { finish = resolve; });
  let checks = 0;
  f.updater.checkForUpdates = async () => { checks++; return { isUpdateAvailable: true, downloadPromise }; };
  const first = f.updates.check();
  await new Promise(resolve => setImmediate(resolve));
  await f.updates.check();
  assert.equal(checks, 1);
  finish([]);
  await first;
});
