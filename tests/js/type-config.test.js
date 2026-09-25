/**
 * Tests de la configuration des types côté carte : libellé et tracé SVG
 * viennent des données de l'API (catalogue des types), avec l'épingle en
 * repli pour un type inconnu.
 *
 * Lancer :  node --test tests/js/*.test.js
 */

const test   = require('node:test');
const assert = require('node:assert');
const fs     = require('node:fs');
const path   = require('node:path');
const { load, SOURCE } = require('./modules.js');

function loadHelpers() {
    return load('types');
}

const { PIN_PATH, typeKey, buildTypeCatalog, resolveTypeConfig } = loadHelpers();

const CATALOG = buildTypeCatalog([
    { slug: 'mecs', name: 'MECS', label: 'MECS', icon: 'home', path: '<path d="M3 9"/>' },
    { slug: 'foyer', name: 'Foyer d\'adolescents', label: 'Foyer d\'adolescents', icon: 'pin', path: '' },
]);

test('la clé d\'un type est son nom en minuscules sans espaces autour', () => {
    assert.strictEqual(typeKey('  MECS '), 'mecs');
    assert.strictEqual(typeKey(null), '');
});

test('un type connu prend son libellé et son tracé dans les données', () => {
    const cfg = resolveTypeConfig(CATALOG, 'MECS', '#123456');
    assert.deepStrictEqual(cfg, { color: '#123456', label: 'MECS', svgPath: '<path d="M3 9"/>' });
});

test('un tracé vide retombe sur l\'épingle', () => {
    assert.strictEqual(resolveTypeConfig(CATALOG, "Foyer d'adolescents", '#000').svgPath, PIN_PATH);
});

test('un type inconnu garde son nom comme libellé et l\'épingle', () => {
    const cfg = resolveTypeConfig(CATALOG, ' Atelier ', '#000');
    assert.strictEqual(cfg.label, 'Atelier');
    assert.strictEqual(cfg.svgPath, PIN_PATH);
});

test('sans type, le libellé est vide', () => {
    assert.strictEqual(resolveTypeConfig(CATALOG, '', '#000').label, '');
});

test('le catalogue conserve l\'ordre reçu et ignore les entrées sans nom', () => {
    const catalog = buildTypeCatalog([{ name: 'B' }, null, { slug: 'x' }, { name: 'A' }]);
    assert.deepStrictEqual(Object.keys(catalog), ['b', 'a']);
});

test('un catalogue absent donne un objet vide', () => {
    assert.deepStrictEqual(buildTypeCatalog(undefined), {});
});

test('plus aucune configuration de type codée en dur', () => {
    assert.ok(!/TYPE_CONFIG|LABEL_OVERRIDES|formatTypeLabel/.test(SOURCE));
});

test('chaque variable CSS posée en ligne par le JS est lue par la feuille de style', () => {
    const css  = fs.readFileSync(path.join(__dirname, '..', '..', 'assets', 'css', 'mapped-places.css'), 'utf8');
    const set  = [...new Set([...SOURCE.matchAll(/style="[^"]*?(--[a-z-]+)\s*:/g)].map((m) => m[1]))];
    const unread = set.filter((name) => !css.includes('var(' + name));
    assert.deepStrictEqual(unread, []);
});
