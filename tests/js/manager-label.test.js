/**
 * Tests du libellé « Directeur/trice » du popup (VAD-8).
 *
 * Le libellé passe au pluriel dès que le champ contient une virgule
 * (plusieurs noms), et vient de geofolioConfig.i18n pour rester traduisible.
 *
 * Lancer :  node --test tests/js/*.test.js
 */

const test   = require('node:test');
const assert = require('node:assert');
const fs     = require('node:fs');
const path   = require('node:path');

function loadDirecteurLabel() {
    const source = fs.readFileSync(
        path.join(__dirname, '..', '..', 'assets', 'js', 'geofolio.js'), 'utf8');
    const start = source.indexOf('    function managerLabel');
    const end   = source.indexOf('    /* ====', start);

    assert.ok(start !== -1 && end > start, 'managerLabel introuvable dans geofolio.js');

    return new Function(source.slice(start, end) + '; return managerLabel;')();
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
