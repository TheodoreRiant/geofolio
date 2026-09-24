/**
 * Tests des aides de lien du popup (assets/js/src/escape.mjs).
 *
 * Le champ « Site web » est saisi a la main dans l'admin : il doit etre
 * assaini avant de devenir un href, et affiche sous forme compacte.
 *
 * Lancer :  node --test tests/js/*.test.js
 */

const test   = require('node:test');
const assert = require('node:assert');
const { load } = require('./modules.js');

function loadHelpers() {
    return load('escape');
}

test('une URL http(s) est conservée telle quelle', () => {
    const { safeUrl } = loadHelpers();
    assert.strictEqual(safeUrl('https://example.org'), 'https://example.org');
    assert.strictEqual(safeUrl('http://a.fr/'), 'http://a.fr/');
});

test('un domaine saisi sans schéma reçoit https://', () => {
    const { safeUrl } = loadHelpers();
    assert.strictEqual(safeUrl('example.org'), 'https://example.org');
});

test('un schéma dangereux ne produit jamais de lien', () => {
    const { safeUrl } = loadHelpers();
    assert.strictEqual(safeUrl('javascript:alert(1)'), '');
    assert.strictEqual(safeUrl('data:text/html,<script>'), '');
    assert.strictEqual(safeUrl('  JavaScript:alert(1)'), '');
});

test('une saisie vide ou insensée ne produit pas de lien', () => {
    const { safeUrl } = loadHelpers();
    assert.strictEqual(safeUrl(''), '');
    assert.strictEqual(safeUrl(null), '');
    assert.strictEqual(safeUrl('pas une url'), '');
});

test("l'affichage retire le schéma et le / final", () => {
    const { prettyUrl } = loadHelpers();
    assert.strictEqual(prettyUrl('https://example.org/'), 'example.org');
    assert.strictEqual(prettyUrl('http://a.fr/page'), 'a.fr/page');
});

test('les guillemets sont échappés dans un attribut', () => {
    const { escAttr } = loadHelpers();
    assert.ok(!escAttr('a"b').includes('"'));
});
