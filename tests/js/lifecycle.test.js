/**
 * Cycle de vie de la carte (assets/js/geofolio.js) : destruction complète
 * et retour visible en cas d'échec du chargement.
 *
 * Lancer :  node --test tests/js/*.test.js
 */

const test   = require('node:test');
const assert = require('node:assert');
const fs     = require('node:fs');
const path   = require('node:path');

const SOURCE = fs.readFileSync(path.join(__dirname, '..', '..', 'assets', 'js', 'geofolio.js'), 'utf8');

/** Corps d'une méthode de classe, de sa signature à la méthode suivante. */
function methodBody(name) {
    const start = SOURCE.indexOf('        ' + name + '(');
    assert.ok(start !== -1, name + '() introuvable');
    const next = SOURCE.slice(start + 1).search(/\n        (?:\/\*\*|[a-zA-Z_]+\([^)]*\) \{)/);
    return SOURCE.slice(start, next === -1 ? undefined : start + 1 + next);
}

test('la classe ne définit destroy() qu\'une fois', () => {
    // Une seconde définition écrase silencieusement la première.
    const definitions = SOURCE.match(/^ {8}destroy\(\) \{/gm) || [];
    assert.strictEqual(definitions.length, 1);
});

test('destroy() détache aussi les écouteurs de l\'autocomplétion', () => {
    assert.match(methodBody('destroy'), /\.off\('\.gfoAC_' \+ this\.mapId\)/);
});

test('un échec du chargement affiche un message à l\'utilisateur', () => {
    const load = methodBody('loadAllData');
    const errorHandler = load.slice(load.indexOf('error:'));
    assert.match(errorHandler, /showToast\(t\('loadError'\)\)/);
});

test('le message du toast est échappé', () => {
    assert.match(methodBody('showToast'), /escHtml\(message\)/);
});
