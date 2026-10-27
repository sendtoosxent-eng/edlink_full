const { test } = require('node:test');
const assert = require('node:assert/strict');
const { DEFAULT_ORIGIN, schoolOrigin, sameOrigin } = require('../policy.cjs');

test('accepts the cloud server and school subdomains', () => {
  assert.equal(schoolOrigin(DEFAULT_ORIGIN + '/login'), DEFAULT_ORIGIN);
  assert.equal(schoolOrigin('https://school.edlink.space/'), 'https://school.edlink.space');
});

test('rejects impersonation, insecure addresses and credentials', () => {
  for (const address of ['http://edlink.space', 'https://edlink.space.evil.test',
    'https://fakeedlink.space', 'https://edlink.space@evil.test',
    'https://user:pass@edlink.space', 'file:///etc/passwd',
    'https://school.edlink.space:8080', 'javascript:alert(1)', 'invalid']) {
    assert.throws(() => schoolOrigin(address), address);
  }
});

test('keeps navigation within the selected school', () => {
  assert.equal(sameOrigin('https://school.edlink.space/offline/', 'https://school.edlink.space'), true);
  for (const address of ['https://other.edlink.space/login', 'https://evil.test', 'file:///tmp/a', 'invalid']) {
    assert.equal(sameOrigin(address, 'https://school.edlink.space'), false);
  }
});
