/**
 * Tests de la sélection des pastilles d'entités (VAD-9).
 *
 * Un objet vide est l'état neutre (toutes les entités visibles). Le premier
 * clic isole l'entité cliquée, les suivants ajoutent ou retirent une entité ;
 * retirer la dernière ramène à l'état neutre.
 *
 * Lancer :  node --test tests/js/*.test.js
 */

const test   = require('node:test');
const assert = require('node:assert');
const { load } = require('./modules.js');

function loadToggle() {
    return load('filters').toggleEntitySelection;
}

test('le premier clic isole l\'entité cliquée', () => {
    assert.deepStrictEqual(loadToggle()({}, 'north'), { north: true });
});

test('un clic sur une autre entité l\'ajoute à la sélection', () => {
    assert.deepStrictEqual(loadToggle()({ north: true }, 'ase'), { north: true, ase: true });
});

test('un clic sur une entité sélectionnée la retire', () => {
    assert.deepStrictEqual(loadToggle()({ north: true, ase: true }, 'north'), { ase: true });
});

test('retirer la dernière entité ramène à l\'état neutre', () => {
    assert.deepStrictEqual(loadToggle()({ north: true }, 'north'), {});
});

test('la sélection d\'origine n\'est jamais modifiée', () => {
    const toggle   = loadToggle();
    const original = { north: true };
    toggle(original, 'ase');
    toggle(original, 'north');
    assert.deepStrictEqual(original, { north: true });
});

test('les entrées à false héritées de l\'ancien état sont ignorées', () => {
    assert.deepStrictEqual(loadToggle()({ north: false, ase: true }, 'mecs'), { ase: true, mecs: true });
});
