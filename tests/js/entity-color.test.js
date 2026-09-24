/**
 * Couleur d'un lieu : celle de son entité, sinon celle de son type.
 *
 * Lancer :  node --test tests/js/*.test.js
 */

const test   = require('node:test');
const assert = require('node:assert');
const { load, SOURCE } = require('./modules.js');

const { resolveEntityColor } = load('colors');

test('la couleur de l\'entité l\'emporte', () => {
    assert.strictEqual(resolveEntityColor({ entity: { color: '#123456' } }, '#abcdef'), '#123456');
});

test('sans entité ni couleur d\'entité, la couleur du type s\'applique', () => {
    assert.strictEqual(resolveEntityColor({}, '#abcdef'), '#abcdef');
    assert.strictEqual(resolveEntityColor({ entity: { name: 'A' } }, '#abcdef'), '#abcdef');
});

test('une couleur d\'entité invalide retombe sur celle du type', () => {
    assert.strictEqual(resolveEntityColor({ entity: { color: 'red;background:url(x)' } }, '#abcdef'), '#abcdef');
});

test('la formule n\'est plus recopiée dans la carte', () => {
    // Avant : quatre copies de « place.entity.color ? … : config.color ».
    assert.strictEqual((SOURCE.match(/place\.entity\.color\) \? place\.entity\.color/g) || []).length, 0);
});
