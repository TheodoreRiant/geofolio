/**
 * Cache des marqueurs : un filtre ne recrée pas les marqueurs déjà
 * construits (icône, popup, écouteurs).
 *
 * Lancer :  node --test tests/js/*.test.js
 */

const test   = require('node:test');
const assert = require('node:assert');
const { load, SOURCE } = require('./modules.js');

const { syncMarkerCache } = load('marker-cache');

const A = { id: 1, lat: 45, lng: 4 };
const B = { id: 2, lat: 46, lng: 5 };
const NO_COORDS = { id: 3, lat: 0, lng: 0 };

function counter() {
    const calls = [];
    return { calls, create: (place) => { calls.push(place.id); return { marker: place.id }; } };
}

test('les marqueurs manquants sont créés, dans l\'ordre des lieux', () => {
    const { calls, create } = counter();
    const { markers } = syncMarkerCache({}, [B, A], create);

    assert.deepStrictEqual(markers.map((m) => m.marker), [2, 1]);
    assert.deepStrictEqual(calls, [2, 1]);
});

test('un marqueur déjà en cache est réutilisé, pas recréé', () => {
    const { calls, create } = counter();
    const first  = syncMarkerCache({}, [A, B], create);
    const second = syncMarkerCache(first.cache, [A], create);

    assert.strictEqual(second.markers[0], first.markers[0]);
    assert.deepStrictEqual(calls, [1, 2]);
});

test('le cache garde les marqueurs masqués par un filtre', () => {
    const { calls, create } = counter();
    const first    = syncMarkerCache({}, [A, B], create);
    const filtered = syncMarkerCache(first.cache, [A], create);
    syncMarkerCache(filtered.cache, [A, B], create);

    assert.deepStrictEqual(calls, [1, 2]);
});

test('un lieu sans coordonnées n\'a pas de marqueur', () => {
    const { create } = counter();
    assert.deepStrictEqual(syncMarkerCache({}, [NO_COORDS], create).markers, []);
});

test('le cache fourni n\'est pas modifié', () => {
    const { create } = counter();
    const cache = {};
    syncMarkerCache(cache, [A], create);

    assert.deepStrictEqual(cache, {});
});

test('la liste des fiches est insérée en une fois', () => {
    const start = SOURCE.indexOf('\n    renderPlaceList() {');
    const body  = SOURCE.slice(start, SOURCE.indexOf('\n    }', start));
    assert.ok(!/\.append\(/.test(body), 'renderPlaceList() ne doit plus ajouter les fiches une par une');
});
