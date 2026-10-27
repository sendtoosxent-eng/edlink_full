const DEFAULT_ORIGIN = 'https://edlink.space';

function schoolOrigin(value) {
  const url = new URL(value);
  if (url.protocol !== 'https:' || url.username || url.password || url.port ||
      !(url.hostname === 'edlink.space' || url.hostname.endsWith('.edlink.space'))) {
    throw new Error('Enter an HTTPS Edlink school address, such as https://school.edlink.space.');
  }
  return url.origin;
}

function sameOrigin(value, origin) {
  try { return new URL(value).origin === origin; } catch { return false; }
}

module.exports = { DEFAULT_ORIGIN, schoolOrigin, sameOrigin };
