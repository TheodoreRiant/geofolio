/**
 * Tests de l'échappement HTML (assets/js/geofolio.js).
 *
 * escHtml() sert aussi dans les attributs entre guillemets : les deux types
 * de guillemets doivent être échappés, sans quoi un texte alternatif piégé
 * peut ajouter un attribut (onload…) à une balise.
 *
 * Lancer :  node --test tests/js/*.test.js
 */

const test   = require('node:test');
const assert = require('node:assert');
const fs     = require('node:fs');
const path   = require('node:path');

function loadEscapers() {
    const source = fs.readFileSync(
        path.join(__dirname, '..', '..', 'assets', 'js', 'geofolio.js'), 'utf8');
    const start = source.indexOf('    /* HTML-escape');
    const end   = source.indexOf('    /* N\'accepte qu\'une URL', start);

    assert.ok(start !== -1 && end > start, 'escHtml / escAttr introuvables dans geofolio.js');

    return new Function(source.slice(start, end) + '; return { escHtml, escAttr };')();
}

test('le guillemet double est échappé', () => {
    assert.strictEqual(loadEscapers().escHtml('"'), '&quot;');
});

test("l'apostrophe est échappée", () => {
    assert.strictEqual(loadEscapers().escHtml("'"), '&#39;');
});

test('les chevrons et l\'esperluette sont échappés', () => {
    assert.strictEqual(loadEscapers().escHtml('<b>&'), '&lt;b&gt;&amp;');
});

test('une valeur absente donne une chaîne vide', () => {
    const { escHtml } = loadEscapers();
    assert.strictEqual(escHtml(undefined), '');
    assert.strictEqual(escHtml(null), '');
});

test('escAttr et escHtml donnent le même résultat', () => {
    const { escHtml, escAttr } = loadEscapers();
    const sample = '" onload="alert(1)\' <x> & y';
    assert.strictEqual(escAttr(sample), escHtml(sample));
});
