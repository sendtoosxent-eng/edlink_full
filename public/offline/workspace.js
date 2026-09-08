import {storedVault, deriveKey, decryptVault, saveVault, removeVault, pendingChanges, reviewRows} from './storage.js';

const $ = id => document.getElementById(id);
let vault, key, salt, catalog, selected, releaseLock, review;
let chain = Promise.resolve();
let busy = false;
const say = message => { $('message').textContent = message; };
const current = () => vault?.datasets.find(dataset => dataset.key === selected);
const count = () => vault?.datasets.reduce((sum, dataset) => sum + pendingChanges(dataset).length, 0) || 0;

function task(action) {
    chain = chain.then(async () => {
        busy = true;
        try { await action(); }
        catch (error) { say(error.message || 'The operation could not be completed. Your saved changes remain on this device.'); }
        finally { busy = false; }
    });
    return chain;
}

async function api(path, options = {}) {
    let response;
    try {
        response = await fetch(`/offline-data/${path}`, {
            credentials: 'same-origin', cache: 'no-store', redirect: 'error',
            ...options, signal: AbortSignal.timeout(15000),
            headers: {Accept: 'application/json', 'Content-Type': 'application/json', ...options.headers},
        });
    } catch {
        $('connection').textContent = 'Offline · changes stay on this device';
        const error = new Error('Cannot reach Edlink. You can keep working on downloaded records.');
        error.network = true;
        throw error;
    }
    $('connection').textContent = 'Connected';
    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
        const error = new Error(data.message || `Edlink returned an error (${response.status}). Try signing in again.`);
        error.status = response.status;
        if ([401, 419].includes(response.status)) error.message = 'Sign in online again, then unlock this workspace to sync. Your saved changes are preserved.';
        throw error;
    }
    return data;
}

async function identity() {
    const next = await api('catalog');
    if (vault && next.owner !== vault.owner) {
        await lock();
        throw new Error('The online account or school changed. Sign in to the original account and school before unlocking these records.');
    }
    catalog = next;
    if (vault) fillTargets();
    return next;
}

async function commit(next) {
    try { await saveVault(next, key, salt); }
    catch { throw new Error('Could not save this change on the device. Keep this page open, free browser storage, and retry the edit. It has not been queued for sync.'); }
    vault = next;
    $('pending').textContent = `${count()} pending learner changes`;
}

async function lock() {
    vault = key = salt = catalog = selected = review = undefined;
    $('workspace').hidden = true;
    $('gate').hidden = false;
    $('rows').replaceChildren();
    $('datasets').replaceChildren();
    $('identity').textContent = '';
    $('record-title').textContent = '';
    $('record-error').textContent = '';
    $('conflict-rows').replaceChildren();
    $('conflict').close();
    releaseLock?.();
    releaseLock = undefined;
    await gate();
}

async function gate() {
    const exists = !!(await storedVault());
    $('gate-title').textContent = exists ? 'Unlock your offline work' : 'Protect this device';
    $('unlock').textContent = exists ? 'Unlock workspace' : 'Set PIN and prepare device';
    $('gate-help').textContent = exists
        ? 'Enter this device’s offline PIN. Your downloaded work stays encrypted until you unlock it.'
        : 'Set a PIN of at least 6 characters to encrypt your downloaded records. Keep it safe: it cannot be recovered.';
}

async function acquireLock() {
    if (releaseLock) return;
    await new Promise((resolve, reject) => {
        navigator.locks.request('edlink-offline-editor', {ifAvailable: true}, async lease => {
            if (!lease) { reject(new Error('This workspace is open in another tab. Lock or close that tab first.')); return; }
            await new Promise(release => { releaseLock = release; resolve(); });
        }).catch(reject);
    });
}

$('unlock-form').addEventListener('submit', event => {
    event.preventDefault();
    const pin = $('pin').value;
    $('pin').value = '';
    task(async () => {
        $('unlock').disabled = true;
        try {
            await acquireLock();
            const saved = await storedVault();
            salt = saved?.salt || crypto.getRandomValues(new Uint8Array(16));
            key = await deriveKey(pin, salt);
            if (saved) {
                try { vault = await decryptVault(saved, key); }
                catch { throw new Error('That PIN could not unlock this device. Try again.'); }
                try { await identity(); }
                catch (error) { if (!error.network) throw error; }
            } else {
                const account = await identity();
                await commit({owner: account.owner, name: account.name, school: account.school, datasets: []});
                await navigator.storage?.persist?.();
            }
            $('gate').hidden = true;
            $('workspace').hidden = false;
            $('date').value = catalog?.today || new Date().toLocaleDateString('en-CA');
            selected = vault.datasets[0]?.key;
            fillTargets();
            render();
            say('Workspace unlocked. Changes are saved on this device as you edit.');
            await syncAll();
        } catch (error) {
            await lock();
            throw error;
        } finally { $('unlock').disabled = false; }
    });
});

function fillTargets() {
    const previous = $('target').value;
    const attendance = $('kind').value === 'attendance';
    $('date-field').hidden = !attendance;
    $('date').required = attendance;
    $('target').replaceChildren();
    for (const item of (attendance ? catalog?.classes : catalog?.papers) || []) {
        $('target').add(new Option(item.name, item.id));
    }
    if ([...$('target').options].some(option => option.value === previous)) $('target').value = previous;
    if (!$('target').options.length) $('target').add(new Option(catalog ? 'No available classes or papers' : 'Connect to download more work', ''));
}
$('kind').addEventListener('change', fillTargets);

$('download-form').addEventListener('submit', event => {
    event.preventDefault();
    const selectors = {kind: $('kind').value, target: $('target').value};
    if (selectors.kind === 'attendance') selectors.date = $('date').value;
    task(async () => {
        if (!vault) return;
        await identity();
        const downloaded = await api(`download?${new URLSearchParams(selectors)}`);
        if (downloaded.owner !== vault.owner) { await lock(); throw new Error('The account or school changed during download. Unlock using the correct account.'); }
        const existing = vault.datasets.find(dataset => dataset.key === downloaded.key);
        if (existing && pendingChanges(existing).length) throw new Error('This record has pending edits. Sync or review them before downloading it again.');
        const next = structuredClone(vault);
        next.datasets = next.datasets.filter(dataset => dataset.key !== downloaded.key);
        next.datasets.push({...downloaded, edits: {}});
        await commit(next);
        selected = downloaded.key;
        render();
        say('Downloaded and saved for offline use.');
    });
});

function render() {
    if (!vault) return;
    $('identity').textContent = `${vault.name} · ${vault.school}`;
    $('pending').textContent = `${count()} pending learner changes`;
    $('datasets').replaceChildren();
    for (const dataset of vault.datasets) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `dataset${dataset.key === selected ? ' active' : ''}`;
        button.textContent = dataset.title;
        const detail = document.createElement('small');
        detail.textContent = `${dataset.kind === 'marks' ? 'Draft marks' : 'Attendance'} · ${pendingChanges(dataset).length} pending${dataset.error ? ' · Needs attention' : ''}`;
        button.append(detail);
        button.addEventListener('click', () => task(async () => { selected = dataset.key; render(); }));
        $('datasets').append(button);
    }
    const dataset = current();
    $('empty').hidden = !!dataset;
    $('record').hidden = !dataset;
    if (!dataset) return;
    $('record-title').textContent = dataset.title;
    $('record-meta').textContent = `Downloaded ${new Date(dataset.downloaded_at).toLocaleString()} · ${dataset.rows.length} learners${dataset.kind === 'marks' ? ` · Maximum ${dataset.maximum}` : ''}`;
    $('record-error').textContent = dataset.error || '';
    $('review').hidden = !dataset.error;
    $('present').hidden = dataset.kind !== 'attendance';
    $('value-heading').textContent = dataset.kind === 'attendance' ? 'Attendance' : 'Score';
    $('rows').replaceChildren();
    for (const row of dataset.rows) {
        const tr = document.createElement('tr');
        const name = document.createElement('td');
        name.textContent = row.name;
        const admission = document.createElement('small');
        admission.textContent = row.admission_no || '';
        name.append(admission);
        const cell = document.createElement('td');
        const input = document.createElement(dataset.kind === 'attendance' ? 'select' : 'input');
        input.setAttribute('aria-label', `${dataset.kind === 'attendance' ? 'Attendance' : 'Score'} for ${row.name}`);
        if (dataset.kind === 'attendance') {
            for (const [value, label] of [['', 'Not recorded'], ['present', 'Present'], ['absent', 'Absent'], ['late', 'Late'], ['excused', 'Excused']]) input.add(new Option(label, value));
        } else { input.type = 'number'; input.min = '0'; input.max = dataset.maximum; input.step = '0.01'; }
        input.value = Object.hasOwn(dataset.edits || {}, row.id) ? dataset.edits[row.id] ?? '' : row.value ?? '';
        input.addEventListener(dataset.kind === 'attendance' ? 'change' : 'input', () => {
            if (!input.checkValidity()) { say(`Enter a score from 0 to ${dataset.maximum}, with at most two decimal places. This value has not been saved.`); return; }
            if (dataset.kind === 'attendance' && !input.value) { input.value = dataset.edits?.[row.id] ?? row.value ?? ''; return; }
            const value = dataset.kind === 'attendance' ? input.value : input.value === '' ? null : Number(input.value);
            task(async () => {
                if (!vault) return;
                const next = structuredClone(vault);
                const target = next.datasets.find(item => item.key === dataset.key);
                target.edits ||= {};
                if (target.rows.find(item => item.id === row.id)?.value === value) delete target.edits[row.id];
                else target.edits[row.id] = value;
                await commit(next);
                if (!input.isConnected) render();
                say('Saved on this device. Pending changes will sync when connected.');
            });
        });
        cell.append(input);
        tr.append(name, cell);
        $('rows').append(tr);
    }
}

async function syncAll() {
    if (!vault) return;
    try { await identity(); }
    catch (error) {
        if ([401, 419].includes(error.status)) await lock();
        if (!error.network) throw error;
        return;
    }
    for (const dataset of [...vault.datasets]) {
        const changes = pendingChanges(dataset);
        if (!changes.length || dataset.error) continue;
        try {
            const fresh = await api('sync', {method: 'POST', headers: {'X-CSRF-TOKEN': catalog.csrf}, body: JSON.stringify({token: dataset.token, changes})});
            const next = structuredClone(vault);
            next.datasets = next.datasets.map(item => item.key === dataset.key ? {...fresh, edits: {}} : item);
            await commit(next);
        } catch (error) {
            if (error.network) return;
            if ([401, 419].includes(error.status)) { await lock(); throw error; }
            if ([403, 404, 409, 422].includes(error.status)) {
                const next = structuredClone(vault);
                next.datasets.find(item => item.key === dataset.key).error = error.message;
                await commit(next);
            } else { throw error; }
        }
    }
    render();
    say(count() ? 'Some changes need attention. Open the record to review them.' : 'All saved changes are synced.');
}

$('sync').addEventListener('click', () => task(syncAll));
$('lock').addEventListener('click', () => task(async () => { await lock(); say('Workspace locked. Saved work is encrypted on this device.'); }));
$('present').addEventListener('click', () => task(async () => {
    const next = structuredClone(vault);
    const dataset = next.datasets.find(item => item.key === selected);
    dataset.edits ||= {};
    for (const row of dataset.rows) {
        if (row.value !== 'present') dataset.edits[row.id] = 'present';
        else delete dataset.edits[row.id];
    }
    await commit(next);
    render();
    say('Attendance saved on this device.');
}));

$('review').addEventListener('click', () => task(async () => {
    await identity();
    const dataset = current();
    const selectors = {kind: dataset.kind, target: dataset.target};
    if (dataset.date) selectors.date = dataset.date;
    const fresh = await api(`download?${new URLSearchParams(selectors)}`);
    if (fresh.owner !== vault.owner) { await lock(); throw new Error('The account or school changed during review. Unlock using the correct account.'); }
    review = {key: dataset.key, fresh, rows: reviewRows(dataset, fresh)};
    $('conflict-rows').replaceChildren();
    for (const [index, row] of review.rows.entries()) {
        const section = document.createElement('div');
        section.className = 'conflict-row';
        const label = document.createElement('label');
        label.htmlFor = `choice-${index}`;
        label.textContent = row.name;
        const select = document.createElement('select');
        select.id = label.htmlFor;
        select.add(new Option('Choose a value…', ''));
        if (!row.removed) {
            select.add(new Option(`Use online value: ${row.online ?? 'Not recorded'}`, 'online'));
            select.add(new Option(`Use my value: ${row.value ?? 'Blank'}`, 'mine'));
        } else select.add(new Option('Learner removed from this roster — discard this edit', 'online'));
        section.append(label, select);
        $('conflict-rows').append(section);
    }
    $('conflict').showModal();
}));
$('cancel-review').addEventListener('click', () => { $('conflict').close(); review = undefined; });
$('resolve').addEventListener('click', () => task(async () => {
    if (!review || !vault) return;
    const edits = {};
    review.rows.forEach((row, index) => {
        const choice = $(`choice-${index}`).value;
        if (!choice) throw new Error('Choose a value for each learner in the review.');
        if (choice === 'mine' && row.value !== row.online) edits[row.id] = row.value;
    });
    const next = structuredClone(vault);
    next.datasets = next.datasets.map(dataset => dataset.key === review.key ? {...review.fresh, edits} : dataset);
    await commit(next);
    $('conflict').close();
    review = undefined;
    await syncAll();
}));

$('export').addEventListener('click', () => task(async () => {
    if (!vault) return;
    const exportData = {name: vault.name, school: vault.school, exported_at: new Date().toISOString(), records: vault.datasets.filter(dataset => pendingChanges(dataset).length).map(dataset => ({title: dataset.title, kind: dataset.kind, date: dataset.date, changes: pendingChanges(dataset).map(change => ({...change, name: dataset.rows.find(row => row.id === change.id)?.name}))}))};
    const url = URL.createObjectURL(new Blob([JSON.stringify(exportData, null, 2)], {type: 'application/json'}));
    const link = document.createElement('a');
    link.href = url; link.download = 'edlink-pending-changes.json'; link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
    say('Pending changes exported. This downloaded file is unencrypted; keep it private.');
}));
$('clear').addEventListener('click', () => task(async () => {
    if (count()) throw new Error('Sync or resolve your pending changes before removing device data.');
    if (!confirm('Remove all downloaded records and the offline PIN from this device?')) return;
    await removeVault(); await lock(); say('Offline data removed from this device.');
}));

function autoSync() {
    if (vault && !busy && !$('conflict').open && !['INPUT', 'SELECT'].includes(document.activeElement?.tagName)) task(syncAll);
}
window.addEventListener('online', autoSync);
window.addEventListener('offline', () => { $('connection').textContent = 'Offline · changes stay on this device'; });
window.addEventListener('pageshow', event => { if (event.persisted) task(lock); });
window.addEventListener('beforeunload', event => { if (busy) { event.preventDefault(); event.returnValue = ''; } });
setInterval(autoSync, 30000);

task(async () => {
    if (!window.isSecureContext || !navigator.serviceWorker || !crypto.subtle || !navigator.locks) {
        $('unlock').disabled = true;
        throw new Error('Offline mode needs HTTPS (or localhost) and a current browser with secure storage and Web Locks support.');
    }
    await navigator.serviceWorker.register('./sw.js', {scope: './', updateViaCache: 'none'});
    await navigator.serviceWorker.ready;
    await gate();
    try { await identity(); } catch (error) { if (!error.network) say(error.message); }
});
