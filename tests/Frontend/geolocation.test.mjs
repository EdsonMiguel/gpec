import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

const source = await readFile(new URL('../../resources/js/geolocation.js', import.meta.url), 'utf8');
let sequence = 0;

async function setup(handler, stored = null, secure = true) {
    const storage = new Map(stored ? [['campo.geolocation', JSON.stringify(stored)]] : []);
    globalThis.window = Object.assign(new EventTarget(), { isSecureContext: secure });
    globalThis.document = { querySelector: () => null };
    Object.defineProperty(globalThis, 'navigator', { configurable: true, value: { geolocation: { getCurrentPosition: handler } } });
    globalThis.sessionStorage = { getItem: (key) => storage.get(key), setItem: (key, value) => storage.set(key, value) };
    const module = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}#${++sequence}`);
    return { module, storage };
}

test('requests location when the application opens and caches only in the browser session', async () => {
    let calls = 0;
    const { module, storage } = await setup((success, error, options) => {
        calls++;
        assert.equal(options.timeout, 15000);
        success({ coords: { latitude: -19.75, longitude: -47.93 } });
    });
    assert.deepEqual(await module.initializeGeolocation(), { latitude: -19.75, longitude: -47.93 });
    assert.deepEqual(await module.requestLocation(), { latitude: -19.75, longitude: -47.93 });
    assert.equal(calls, 1);
    assert.equal(JSON.parse(storage.get('campo.geolocation')).status, 'granted');
});

test('uses a recent authorized position without requesting it again on navigation', async () => {
    const { module } = await setup(() => { throw new Error('A new request was not expected'); }, { status: 'granted', position: { latitude: -20, longitude: -48 }, timestamp: Date.now() });
    assert.deepEqual(await module.requestLocation(), { latitude: -20, longitude: -48 });
});

test('denial does not cause repeated prompts and allows an explicit retry', async () => {
    let calls = 0;
    const { module } = await setup((success, error) => { calls++; error({ code: 1 }); });
    assert.equal(await module.requestLocation(), null);
    assert.equal(await module.requestLocation(), null);
    assert.equal(calls, 1);
    assert.equal(module.locationState().status, 'denied');
    await module.requestLocation({ retry: true });
    assert.equal(calls, 2);
});

test('timeout resolves without blocking the application', async () => {
    const { module } = await setup((success, error) => error({ code: 3 }));
    assert.equal(await module.requestLocation(), null);
    assert.equal(module.locationState().status, 'unavailable');
});

test('insecure origins do not attempt geolocation and explain the fallback', async () => {
    const { module } = await setup(() => { throw new Error('Geolocation must not run'); }, null, false);
    assert.equal(await module.requestLocation(), null);
    assert.match(module.locationState().message, /HTTPS ou localhost/);
});

test('browser policy exceptions preserve the manual map fallback', async () => {
    const { module } = await setup(() => { throw new Error('SecurityError'); });
    assert.equal(await module.requestLocation(), null);
    assert.equal(module.locationState().status, 'unavailable');
});
