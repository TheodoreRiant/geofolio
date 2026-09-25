/**
 * Tests de la résolution du fond de carte côté navigateur.
 *
 * resolveTile() (assets/js/src/tiles.mjs) est importé et exécuté sans DOM,
 * jQuery ni Leaflet, avec un objet window contrôlé.
 *
 * Lancer :  node --test tests/js/*.test.js
 */

const test   = require('node:test');
const assert = require('node:assert');
const { load } = require('./modules.js');

/**
 * Charger resolveTile() avec un objet `window` contrôlé.
 *
 * @param {object} tiles Contenu de mappedPlacesConfig.tiles, ou null.
 * @returns {Function} resolveTile
 */
function loadResolver(tiles) {
    // resolveTile() lit window.mappedPlacesConfig à chaque appel.
    global.window = tiles ? { mappedPlacesConfig: { tiles } } : {};
    return load('tiles').resolveTile;
}

/** Table de fonds représentative du site : CARTO sans clé, IGN disponible. */
function tilesFixture(forced = '') {
    return {
        fallback: 'ign-plan',
        forced,
        providers: {
            'ign-plan':  { type: 'raster', url: 'https://ign/{z}/{x}/{y}',   attribution: 'IGN', subdomains: '',    maxZoom: 19, available: true },
            'positron':  { type: 'raster', url: '',                          attribution: '',    subdomains: '',    maxZoom: 19, available: false },
            'osm-fr':    { type: 'raster', url: 'https://osm/{z}/{x}/{y}',   attribution: 'OSM', subdomains: 'abc', maxZoom: 19, available: true },
            'ign-epure': { type: 'vector', url: 'https://ign/style.json',    attribution: 'IGN', subdomains: '',    maxZoom: 19, available: true },
        },
    };
}

test('sans table serveur, on retombe sur un fond sans clé', () => {
    const resolveTile = loadResolver(null);
    assert.strictEqual(resolveTile('positron', true).id, 'osm');
});

test('une page restée sur CARTO bascule sur le fond de repli du serveur', () => {
    const resolveTile = loadResolver(tilesFixture());
    assert.strictEqual(resolveTile('positron', true).id, 'ign-plan');
});

test('un fond disponible est servi tel quel, sous-domaines compris', () => {
    const resolveTile = loadResolver(tilesFixture());
    const tile = resolveTile('osm-fr', true);

    assert.strictEqual(tile.id, 'osm-fr');
    assert.strictEqual(tile.subdomains, 'abc');
    assert.strictEqual(tile.url, 'https://osm/{z}/{x}/{y}');
});

test('un fond vectoriel sans son moteur de rendu retombe sur le raster', () => {
    const resolveTile = loadResolver(tilesFixture());
    assert.strictEqual(resolveTile('ign-epure', false).id, 'ign-plan');
});

test('un fond vectoriel avec son moteur est servi', () => {
    const resolveTile = loadResolver(tilesFixture());
    assert.strictEqual(resolveTile('ign-epure', true).type, 'vector');
});

test('un fond inconnu ou vide retombe sur le repli', () => {
    const resolveTile = loadResolver(tilesFixture());
    assert.strictEqual(resolveTile('fournisseur-fantaisiste', true).id, 'ign-plan');
    assert.strictEqual(resolveTile('', true).id, 'ign-plan');
});

test('le fond imposé dans les réglages prime sur celui de la page', () => {
    const resolveTile = loadResolver(tilesFixture('osm-fr'));
    assert.strictEqual(resolveTile('positron', true).id, 'osm-fr');
});
