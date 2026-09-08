import test from 'node:test';
import assert from 'node:assert/strict';
import {deriveKey, encryptVault, decryptVault, pendingChanges, reviewRows} from '../../public/offline/storage.js';

test('encrypted offline records survive unlocking with the original PIN and reject a wrong PIN', async () => {
    const salt = crypto.getRandomValues(new Uint8Array(16));
    const key = await deriveKey('test-passphrase-only', salt);
    const vault = {owner: '1:2', datasets: [{rows: [{id: 1, name: 'Test learner', value: 72}], edits: {1: 83}}]};
    const record = await encryptVault(vault, key, salt);
    assert.equal(key.extractable, false);
    assert.equal(new TextDecoder().decode(record.ciphertext).includes('Test learner'), false);
    assert.deepEqual(await decryptVault(record, await deriveKey('test-passphrase-only', record.salt)), vault);
    await assert.rejects(decryptVault(record, await deriveKey('wrong-test-passphrase', salt)));
});

test('vault ciphertext tampering is detected and each save uses a new nonce', async () => {
    const salt = crypto.getRandomValues(new Uint8Array(16));
    const key = await deriveKey('test-passphrase-only', salt);
    const first = await encryptVault({owner: '1:2'}, key, salt);
    const second = await encryptVault({owner: '1:2'}, key, salt);
    assert.notDeepEqual(first.iv, second.iv);
    new Uint8Array(first.ciphertext)[0] ^= 1;
    await assert.rejects(decryptVault(first, key));
});

test('the sync queue preserves zero and deliberately cleared scores without sending untouched rows', () => {
    const dataset = {rows: [{id: 1}, {id: 2}, {id: 3}], edits: {1: 0, 2: null}};
    assert.deepEqual(pendingChanges(dataset), [{id: 1, value: 0}, {id: 2, value: null}]);
});

test('conflict review retains local values and identifies learners removed online', () => {
    const dataset = {rows: [{id: 1, name: 'First'}, {id: 2, name: 'Second'}], edits: {1: 25, 2: 50}};
    const fresh = {rows: [{id: 1, value: 90}]};
    assert.deepEqual(reviewRows(dataset, fresh), [
        {id: 1, name: 'First', value: 25, online: 90, removed: false},
        {id: 2, name: 'Second', value: 50, online: undefined, removed: true},
    ]);
    assert.deepEqual(dataset.edits, {1: 25, 2: 50});
});
