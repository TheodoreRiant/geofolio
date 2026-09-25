/**
 * La feuille servie (assets/css/mapped-places.css) est l'assemblage exact des
 * partiels de assets/css/src/ : un partiel modifié sans reconstruction
 * fait échouer ce test.
 *
 * Lancer :  node --test tests/js/*.test.js
 */

const test   = require('node:test');
const assert = require('node:assert');
const fs     = require('node:fs');
const { build, OUTPUT } = require('../../tools/build-css.js');

test('mapped-places.css est à jour avec ses partiels (npm run build:css)', () => {
    assert.strictEqual(fs.readFileSync(OUTPUT, 'utf8'), build());
});
