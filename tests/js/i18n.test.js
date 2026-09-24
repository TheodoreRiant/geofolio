/**
 * Tests de l'internationalisation côté carte : formatage des libellés
 * traduits, et aucune chaîne affichable codée en dur dans geofolio.js.
 *
 * Lancer :  node --test tests/js/*.test.js
 */

const test   = require('node:test');
const assert = require('node:assert');
const fs     = require('node:fs');
const path   = require('node:path');

const SOURCE = fs.readFileSync(path.join(__dirname, '..', '..', 'assets', 'js', 'geofolio.js'), 'utf8');

function loadFormatText() {
    const start = SOURCE.indexOf('    /* ---- I18N : début ---- */');
    const end   = SOURCE.indexOf('    /* ---- I18N : fin ---- */', start);
    assert.ok(start !== -1 && end > start, 'Bloc « I18N » introuvable dans geofolio.js');
    return new Function(SOURCE.slice(start, end) + '; return formatText;')();
}

test('les marqueurs %d et %s sont remplacés dans l\'ordre', () => {
    const formatText = loadFormatText();
    assert.strictEqual(formatText('%d places', [3]), '3 places');
    assert.strictEqual(formatText('%s / %s', ['a', 'b']), 'a / b');
});

test('les marqueurs positionnels suivent leur numéro', () => {
    assert.strictEqual(loadFormatText()('%2$d sur %1$d', [1, 5]), '5 sur 1');
});

test('un modèle absent donne une chaîne vide', () => {
    assert.strictEqual(loadFormatText()(undefined, []), '');
});

test('aucun repli de texte affichable codé en dur', () => {
    // « || 'Texte' » : un repli français (ou anglais) court-circuiterait la traduction.
    const offenders = SOURCE.match(/\|\|\s*'[^']*[A-Za-zÀ-ÿ]{3,}[^']*'/g) || [];
    const allowed   = ["|| 'positron'", "|| 'raster'", "|| 'gfo-ac'"]; // identifiants techniques
    assert.deepStrictEqual(offenders.filter((o) => !allowed.includes(o)), []);
});

test('aucun caractère accentué hors commentaires dans geofolio.js', () => {
    const code = SOURCE.replace(/\/\*[\s\S]*?\*\//g, '').replace(/\/\/.*$/gm, '');
    assert.deepStrictEqual(code.match(/[À-ÿ]/g) || [], []);
});
