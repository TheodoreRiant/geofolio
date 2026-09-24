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

function loadHelpers() {
    const source = fs.readFileSync(
        path.join(__dirname, '..', '..', 'assets', 'js', 'geofolio.js'), 'utf8');
    const start = source.indexOf('    /* ---- TYPES : début ---- */');
    const end   = source.indexOf('    /* ---- TYPES : fin ---- */', start);

    assert.ok(start !== -1 && end > start, 'Fonctions des types introuvables dans geofolio.js');

    return new Function(source.slice(start, end)
        + '; return { PIN_PATH, typeKey, buildTypeCatalog, resolveTypeConfig };')();
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
    const source = fs.readFileSync(
        path.join(__dirname, '..', '..', 'assets', 'js', 'geofolio.js'), 'utf8');
    assert.ok(!/TYPE_CONFIG|LABEL_OVERRIDES|formatTypeLabel/.test(source));
});
