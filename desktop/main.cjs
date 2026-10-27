const { app, BrowserWindow, Menu, dialog, ipcMain, shell } = require('electron');
const fs = require('node:fs');
const path = require('node:path');
const { pathToFileURL } = require('node:url');
const { DEFAULT_ORIGIN, schoolOrigin, sameOrigin } = require('./policy.cjs');

let mainWindow;
let settingsWindow;
let origin = DEFAULT_ORIGIN;
const localURL = name => pathToFileURL(path.join(__dirname, name)).href;
const preferences = () => path.join(app.getPath('userData'), 'school.json');

function openPage(route) {
  mainWindow.loadURL(origin + route).catch(() => {});
}

function showSettings() {
  if (settingsWindow) return settingsWindow.focus();
  settingsWindow = new BrowserWindow({
    width: 540, height: 390, resizable: false, parent: mainWindow, modal: true,
    autoHideMenuBar: true,
    webPreferences: { preload: path.join(__dirname, 'settings-preload.cjs'), nodeIntegration: false, contextIsolation: true, sandbox: true }
  });
  settingsWindow.webContents.setWindowOpenHandler(() => ({ action: 'deny' }));
  settingsWindow.webContents.on('will-navigate', event => event.preventDefault());
  settingsWindow.loadFile(path.join(__dirname, 'settings.html'));
  settingsWindow.on('closed', () => { settingsWindow = null; });
}

function trustedSettings(event) {
  return settingsWindow && event.sender === settingsWindow.webContents &&
    event.senderFrame === settingsWindow.webContents.mainFrame &&
    event.senderFrame.url === localURL('settings.html');
}

if (!app.requestSingleInstanceLock()) app.quit();
else {
  app.on('second-instance', () => {
    if (mainWindow) { if (mainWindow.isMinimized()) mainWindow.restore(); mainWindow.focus(); }
  });
  app.whenReady().then(() => {
    try { origin = schoolOrigin(JSON.parse(fs.readFileSync(preferences(), 'utf8')).origin); } catch {}
    const { session } = require('electron');
    const profile = session.fromPartition('persist:edlink');
    profile.setPermissionRequestHandler((_contents, _permission, callback) => callback(false));
    profile.setPermissionCheckHandler(() => false);
    mainWindow = new BrowserWindow({
      title: 'Edlink', width: 1280, height: 850, minWidth: 800, minHeight: 600,
      backgroundColor: '#f8fafc',
      webPreferences: { partition: 'persist:edlink', nodeIntegration: false, contextIsolation: true, sandbox: true, webSecurity: true }
    });
    const contents = mainWindow.webContents;
    contents.setWindowOpenHandler(({ url }) => {
      if (sameOrigin(url, origin)) {
        // Keep reports and printable pages inside the secured app window.
        contents.loadURL(url).catch(() => {});
      } else if (url.startsWith('https://')) {
        dialog.showMessageBox(mainWindow, { type: 'question', message: 'Open this link in your browser?', detail: url, buttons: ['Cancel', 'Open browser'], defaultId: 0, cancelId: 0 })
          .then(({ response }) => { if (response === 1) shell.openExternal(url).catch(() => {}); });
      }
      return { action: 'deny' };
    });
    const guard = (event, url) => {
      if (!sameOrigin(url, origin)) event.preventDefault();
    };
    contents.on('will-navigate', guard);
    contents.on('will-redirect', guard);
    contents.on('will-attach-webview', event => event.preventDefault());
    contents.on('did-fail-load', (_event, code, _description, url, isMainFrame) => {
      if (isMainFrame && code !== -3 && sameOrigin(url, origin)) {
        mainWindow.loadFile(path.join(__dirname, 'connection.html')).catch(() => {});
      }
    });
    Menu.setApplicationMenu(Menu.buildFromTemplate([
      { label: 'Edlink', submenu: [
        { label: 'School login', click: () => openPage('/login') },
        { label: 'Offline workspace', accelerator: 'CmdOrCtrl+Shift+O', click: () => openPage('/offline/') },
        { label: 'School address…', click: showSettings },
        { type: 'separator' }, { role: 'quit' }
      ] },
      { label: 'Edit', submenu: [{ role: 'undo' }, { role: 'redo' }, { type: 'separator' }, { role: 'cut' }, { role: 'copy' }, { role: 'paste' }, { role: 'selectAll' }] },
      { label: 'View', submenu: [
        { label: 'Back', accelerator: 'Alt+Left', click: () => { if (contents.navigationHistory.canGoBack()) contents.navigationHistory.goBack(); } },
        { label: 'Reload', accelerator: 'CmdOrCtrl+R', click: () => sameOrigin(contents.getURL(), origin) ? contents.reload() : openPage('/login') },
        { label: 'Print', accelerator: 'CmdOrCtrl+P', click: () => contents.print({}) },
        { role: 'resetZoom' }, { role: 'zoomIn' }, { role: 'zoomOut' }, { role: 'togglefullscreen' }
      ] }
    ]));
    ipcMain.handle('school:get', event => {
      if (!trustedSettings(event)) throw new Error('Unavailable');
      return origin;
    });
    ipcMain.handle('school:set', async (event, value) => {
      if (!trustedSettings(event)) throw new Error('Unavailable');
      try {
        const next = schoolOrigin(value);
        fs.writeFileSync(preferences(), JSON.stringify({ origin: next }), { mode: 0o600 });
        origin = next;
        openPage('/login');
        settingsWindow.close();
        return { ok: true };
      } catch (error) { return { ok: false, error: error.message }; }
    });
    openPage('/login');
  });
  app.on('window-all-closed', () => app.quit());
}
