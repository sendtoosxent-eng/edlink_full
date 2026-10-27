const { contextBridge, ipcRenderer } = require('electron');
contextBridge.exposeInMainWorld('school', {
  get: () => ipcRenderer.invoke('school:get'),
  set: value => ipcRenderer.invoke('school:set', value)
});
