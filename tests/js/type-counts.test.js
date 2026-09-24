/**
 * Tests des compteurs à facettes du filtre « Types » (VAD-10).
 *
 * Les compteurs appliquent la recherche et la sélection d'entités, mais pas
 * le filtre de type lui-même. Un type à 0 est masqué, sauf s'il est
 * sélectionné.
 *
 * Lancer :  node --test tests/js/*.test.js
 */

const test   = require('node:test');
const assert = require('node:assert');
const { load } = require('./modules.js');

function loadHelpers() {
    return load('filters');
}

const ETABS = [
    { title: 'Foyer A', city: 'Lyon',     types: ['Foyer'],          entity: { slug: 'north' } },
    { title: 'Foyer B', city: 'Grenoble', types: ['Foyer'],          entity: { slug: 'south' } },
    { title: 'Chantier', city: 'Lyon',    types: ['Chantier', 'ESAT'], entity: { slug: 'south' } },
    { title: 'Sans entité', city: 'Vienne', types: ['Foyer'] },
];

test('les types sont comptés sur les données reçues', () => {
    const { countTypes } = loadHelpers();
    assert.deepStrictEqual(countTypes(ETABS), { foyer: 3, chantier: 1, esat: 1 });
});

test('les compteurs suivent la sélection d\'entités', () => {
    const { countTypes, matchesEntities } = loadHelpers();
    const base = ETABS.filter((e) => matchesEntities(e, { south: true }));
    // Les établissements sans entité restent visibles (comportement existant).
    assert.deepStrictEqual(countTypes(base), { foyer: 2, chantier: 1, esat: 1 });
});

test('une sélection d\'entités vide ne filtre rien', () => {
    const { matchesEntities } = loadHelpers();
    assert.ok(ETABS.every((e) => matchesEntities(e, {})));
});

test('les compteurs suivent la recherche texte', () => {
    const { countTypes, matchesSearch } = loadHelpers();
    const base = ETABS.filter((e) => matchesSearch(e, 'lyon'));
    assert.deepStrictEqual(countTypes(base), { foyer: 1, chantier: 1, esat: 1 });
});

test('le filtre de type compare sans casse ni espaces', () => {
    const { matchesType } = loadHelpers();
    assert.ok(matchesType({ types: [' Foyer '] }, 'foyer'));
    assert.ok(!matchesType({ types: ['ESAT'] }, 'foyer'));
    assert.ok(matchesType({ types: [] }, ''));
});

test('les types à 0 sont masqués, dans l\'ordre connu puis les autres', () => {
    const { visibleTypeKeys } = loadHelpers();
    const keys = visibleTypeKeys({ esat: 2, foyer: 1, chantier: 0 }, ['foyer', 'chantier'], '');
    assert.deepStrictEqual(keys, ['foyer', 'esat']);
});

test('le type sélectionné reste affiché même à 0', () => {
    const { visibleTypeKeys } = loadHelpers();
    const keys = visibleTypeKeys({ foyer: 1 }, ['foyer', 'chantier'], 'chantier');
    assert.deepStrictEqual(keys, ['foyer', 'chantier']);
});

test('un type sélectionné inconnu et absent des données reste affiché', () => {
    const { visibleTypeKeys } = loadHelpers();
    assert.deepStrictEqual(visibleTypeKeys({}, ['foyer'], 'esat'), ['esat']);
});

test('les types hors liste connue gardent un ordre alphabétique stable', () => {
    const { visibleTypeKeys } = loadHelpers();
    const a = visibleTypeKeys({ 'maison': 1, 'accueil': 2 }, ['mecs'], '');
    const b = visibleTypeKeys({ 'accueil': 1, 'maison': 5 }, ['mecs'], '');
    assert.deepStrictEqual(a, ['accueil', 'maison']);
    assert.deepStrictEqual(b, a);
    // Le type sélectionné à 0 prend sa place alphabétique, sans sauter en fin de liste.
    assert.deepStrictEqual(visibleTypeKeys({ 'maison': 1 }, [], 'accueil'), ['accueil', 'maison']);
});
