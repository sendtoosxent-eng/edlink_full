const CACHE = 'edlink-offline-shell-v1';
const FILES = ['./', './index.html', './workspace.css', './workspace.js', './storage.js'];
self.addEventListener('install', event => {
    event.waitUntil(caches.open(CACHE).then(cache => cache.addAll(FILES)).then(() => self.skipWaiting()));
});
self.addEventListener('activate', event => {
    event.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(key => key.startsWith('edlink-offline-shell-') && key !== CACHE).map(key => caches.delete(key)))).then(() => self.clients.claim()));
});
self.addEventListener('fetch', event => {
    // Only public shell files are cached. Authenticated pages and data never enter Cache Storage.
    const allowed = FILES.map(file => new URL(file, self.registration.scope).href);
    if (event.request.method !== 'GET' || !allowed.includes(event.request.url)) return;
    event.respondWith(caches.open(CACHE).then(async cache => (await cache.match(event.request)) || fetch(event.request)));
});
