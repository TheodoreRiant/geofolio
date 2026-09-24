/**
 * Carte de localisation de la fiche (assets/js/geofolio-admin.js).
 *
 * Dans l'éditeur de blocs, les meta boxes sont dans un panneau repliable :
 * la carte est créée dans un conteneur trop petit, puis agrandie. Sans
 * invalidateSize() au redimensionnement, Leaflet ne dessine qu'un coin.
 *
 * Lancer :  node --test tests/js/*.test.js
 */

const test   = require('node:test');
const assert = require('node:assert');
const fs     = require('node:fs');
const path   = require('node:path');

const SOURCE = fs.readFileSync(path.join(__dirname, '..', '..', 'assets', 'js', 'geofolio-admin.js'), 'utf8');

test('la carte de la fiche se recalcule quand son conteneur change de taille', () => {
    assert.match(SOURCE, /new ResizeObserver\(/);
    assert.match(SOURCE, /invalidateSize\(\)/);
});
