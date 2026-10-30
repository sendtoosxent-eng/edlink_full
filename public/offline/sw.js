const CACHE = 'edlink-offline-shell-v4';
const FILES = ['./', './index.html', './workspace.css', './workspace.js', './storage.js', '../css/edlink-ui.css', '../css/edlink-skeleton.css', '../js/edlink-skeleton.js', '../fonts/poppins/Poppins-Black.ttf', '../fonts/poppins/Poppins-Bold.ttf', '../fonts/poppins/Poppins-BoldItalic.ttf', '../fonts/poppins/Poppins-ExtraBold.ttf', '../fonts/poppins/Poppins-ExtraLight.ttf', '../fonts/poppins/Poppins-Italic.ttf', '../fonts/poppins/Poppins-Light.ttf', '../fonts/poppins/Poppins-Medium.ttf', '../fonts/poppins/Poppins-Regular.ttf', '../fonts/poppins/Poppins-SemiBold.ttf', '../fonts/poppins/Poppins-Thin.ttf'];
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
