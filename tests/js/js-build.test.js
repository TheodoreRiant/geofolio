/**
 * Le script servi (assets/js/mapped-places.js) est l'assemblage exact des modules
 * de assets/js/src/ : un module modifié sans reconstruction fait échouer ce
 * test.
 *
 * Lancer :  node --test tests/js/*.test.js   (après npm ci : esbuild)
 */

const test   = require('node:test');
const assert = require('node:assert');
const fs     = require('node:fs');
const { build, OUTPUT } = require('../../tools/build-js.js');

test('mapped-places.js est à jour avec ses modules (npm run build:js)', () => {
    assert.strictEqual(fs.readFileSync(OUTPUT, 'utf8'), build());
});
