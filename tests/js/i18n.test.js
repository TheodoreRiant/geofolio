/**
 * Tests de l'internationalisation côté carte : formatage des libellés
 * traduits, et aucune chaîne affichable codée en dur dans les modules de la carte.
 *
 * Lancer :  node --test tests/js/*.test.js
 */

const test   = require('node:test');
const assert = require('node:assert');
const fs     = require('node:fs');
const path   = require('node:path');
const { load, SOURCE } = require('./modules.js');

function loadFormatText() {
    return load('i18n').formatText;
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

test('aucun caractère accentué hors commentaires dans les modules de la carte', () => {
    const code = SOURCE.replace(/\/\*[\s\S]*?\*\//g, '').replace(/\/\/.*$/gm, '');
    assert.deepStrictEqual(code.match(/[À-ÿ]/g) || [], []);
});

test('chaque clé t(\'…\') du JS est déclarée dans geofolioConfig.i18n', () => {
    const php  = fs.readFileSync(path.join(__dirname, '..', '..', 'src', 'Plugin.php'), 'utf8');
    const used = [...new Set([...SOURCE.matchAll(/\bt\('([A-Za-z]+)'/g)].map((m) => m[1]))];
    const missing = used.filter((key) => !php.includes("'" + key + "'"));
    assert.deepStrictEqual(missing, []);
});
