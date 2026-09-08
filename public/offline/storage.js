const encoder = new TextEncoder();
const decoder = new TextDecoder();
let database;

function openDatabase() {
    if (!database) database = new Promise((resolve, reject) => {
        const request = indexedDB.open('edlink-offline-v1', 1);
        request.onupgradeneeded = () => request.result.createObjectStore('vault');
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
    return database;
}

export async function storedVault() {
    const db = await openDatabase();
    return new Promise((resolve, reject) => {
        const request = db.transaction('vault').objectStore('vault').get('device');
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function writeVault(value) {
    const db = await openDatabase();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('vault', 'readwrite');
        if (value) tx.objectStore('vault').put(value, 'device');
        else tx.objectStore('vault').delete('device');
        tx.oncomplete = resolve;
        tx.onerror = () => reject(tx.error);
        tx.onabort = () => reject(tx.error || new Error('Device storage was interrupted.'));
    });
}

export async function deriveKey(pin, salt) {
    const material = await crypto.subtle.importKey('raw', encoder.encode(pin), 'PBKDF2', false, ['deriveKey']);
    return crypto.subtle.deriveKey({name: 'PBKDF2', hash: 'SHA-256', salt, iterations: 600000}, material, {name: 'AES-GCM', length: 256}, false, ['encrypt', 'decrypt']);
}

export async function decryptVault(record, key) {
    return JSON.parse(decoder.decode(await crypto.subtle.decrypt({name: 'AES-GCM', iv: record.iv}, key, record.ciphertext)));
}

export async function encryptVault(vault, key, salt) {
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const ciphertext = await crypto.subtle.encrypt({name: 'AES-GCM', iv}, key, encoder.encode(JSON.stringify(vault)));
    return {salt, iv, ciphertext};
}

export async function saveVault(vault, key, salt) {
    await writeVault(await encryptVault(vault, key, salt));
}

export const removeVault = () => writeVault(null);

export function pendingChanges(dataset) {
    return dataset.rows.filter(row => Object.hasOwn(dataset.edits || {}, row.id)).map(row => ({id: row.id, value: dataset.edits[row.id]}));
}

export function reviewRows(dataset, fresh) {
    return pendingChanges(dataset).map(change => {
        const original = dataset.rows.find(row => row.id === change.id);
        const online = fresh.rows.find(row => row.id === change.id);
        return {...change, name: original.name, online: online?.value, removed: !online};
    });
}
