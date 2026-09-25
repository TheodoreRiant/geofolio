/**
 * Tests du libellé « Directeur/trice » du popup (VAD-8).
 *
 * Le libellé passe au pluriel dès que le champ contient une virgule
 * (plusieurs noms), et vient de mappedPlacesConfig.i18n pour rester traduisible.
 *
 * Lancer :  node --test tests/js/*.test.js
 */

const test   = require('node:test');
const assert = require('node:assert');
const { load } = require('./modules.js');

function loadDirecteurLabel() {
    return load('text').managerLabel;
}

const I18N = { manager: 'Directeur/trice : ', managers: 'Directeurs/trices : ' };

test('un seul nom donne le libellé au singulier', () => {
    assert.strictEqual(loadDirecteurLabel()('Jeanne Martin', I18N), 'Directeur/trice : ');
});

test('plusieurs noms séparés par une virgule donnent le pluriel', () => {
    assert.strictEqual(loadDirecteurLabel()('Jeanne Martin, Paul Durand', I18N), 'Directeurs/trices : ');
});

test('sans traduction fournie, aucun libellé codé en dur', () => {
    const managerLabel = loadDirecteurLabel();
    assert.strictEqual(managerLabel('Jeanne Martin'), '');
    assert.strictEqual(managerLabel('A, B', {}), '');
});
