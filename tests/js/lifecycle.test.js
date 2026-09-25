/**
 * Cycle de vie de la carte (modules assets/js/src/) : destruction complète
 * et retour visible en cas d'échec du chargement.
 *
 * Lancer :  node --test tests/js/*.test.js
 */

const test   = require('node:test');
const assert = require('node:assert');
const { SOURCE } = require('./modules.js');

/** Corps d'une méthode de classe, de sa signature à la méthode suivante. */
function methodBody(name) {
    const start = SOURCE.indexOf('\n    ' + name + '(') + 1;
    assert.ok(start !== 0, name + '() introuvable');
    const next = SOURCE.slice(start + 1).search(/\n {4}(?:\/\*\*|[a-zA-Z_]+\([^)]*\) \{)/);
    return SOURCE.slice(start, next === -1 ? undefined : start + 1 + next);
}

test('la classe ne définit destroy() qu\'une fois', () => {
    // Une seconde définition écrase silencieusement la première.
    const definitions = SOURCE.match(/^ {4}destroy\(\) \{/gm) || [];
    assert.strictEqual(definitions.length, 1);
});

test('destroy() détache aussi les écouteurs de l\'autocomplétion', () => {
    assert.match(methodBody('destroy'), /\.off\('\.maplAC_' \+ this\.mapId\)/);
});

test('un échec du chargement affiche un message à l\'utilisateur', () => {
    const load = methodBody('loadAllData');
    const errorHandler = load.slice(load.indexOf('error:'));
    assert.match(errorHandler, /showToast\(t\('loadError'\)\)/);
});

test('le message du toast est échappé', () => {
    assert.match(methodBody('showToast'), /escHtml\(message\)/);
});

test('l\'ajustement automatique de la vue respecte le réglage fit_bounds', () => {
    assert.match(SOURCE, /fitBounds:\s*String\(this\.\$container\.data\('fit-bounds'\)\) !== 'false'/);
    const fit = SOURCE.indexOf('this.map.fitBounds(bounds');
    const guard = SOURCE.lastIndexOf('this.config.fitBounds', fit);
    assert.ok(guard !== -1 && fit - guard < 200, 'fitBounds() doit être conditionné par this.config.fitBounds');
});
