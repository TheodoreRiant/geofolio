/**
 * Adresse de la bulle : le code postal et la ville ne sont ajoutés que
 * s'ils ne figurent pas déjà dans le champ adresse. Beaucoup de fichiers
 * importés ont une adresse complète (« 26b rue de l'Oiselet, 38300
 * Bourgoin-Jallieu ») à côté de colonnes code postal et ville remplies.
 *
 * Lancer :  node --test tests/js/*.test.js
 */

const test   = require('node:test');
const assert = require('node:assert');
const { load, SOURCE } = require('./modules.js');

test('adresse déjà complète : pas de doublon', () => {
    const { formatAddress } = load('text');
    assert.strictEqual(
        formatAddress({ address: "26b rue de l'Oiselet, 38300 Bourgoin-Jallieu", postal_code: '38300', city: 'Bourgoin-Jallieu' }),
        "26b rue de l'Oiselet, 38300 Bourgoin-Jallieu"
    );
});

test('la ville est reconnue sans tenir compte des accents ni de la casse', () => {
    const { formatAddress } = load('text');
    assert.strictEqual(
        formatAddress({ address: '11 rue du Père Chevrier, 69007 LYON', postal_code: '69007', city: 'Lyon' }),
        '11 rue du Père Chevrier, 69007 LYON'
    );
    assert.strictEqual(
        formatAddress({ address: '3 route neuve, 69270 Saint-Romain-au-Mont-d’Or', postal_code: '69270', city: "Saint-Romain-au-Mont-d'Or" }),
        '3 route neuve, 69270 Saint-Romain-au-Mont-d’Or'
    );
});

test('adresse sans code postal ni ville : ils sont ajoutés', () => {
    const { formatAddress } = load('text');
    assert.strictEqual(
        formatAddress({ address: '12 rue des Lilas', postal_code: '75011', city: 'Paris' }),
        '12 rue des Lilas, 75011, Paris'
    );
    assert.strictEqual(
        formatAddress({ address: '12 rue des Lilas, 75011', postal_code: '75011', city: 'Paris' }),
        '12 rue des Lilas, 75011, Paris'
    );
});

test('champs vides ou absents', () => {
    const { formatAddress } = load('text');
    assert.strictEqual(formatAddress({ postal_code: '75011', city: 'Paris' }), '75011, Paris');
    assert.strictEqual(formatAddress({ address: '' }), '');
    assert.strictEqual(formatAddress({}), '');
});

test('une ville courte contenue dans un autre mot ne masque pas la ville', () => {
    const { formatAddress } = load('text');
    assert.strictEqual(
        formatAddress({ address: '5 avenue de Nancy', postal_code: '57000', city: 'Metz' }),
        '5 avenue de Nancy, 57000, Metz'
    );
    assert.strictEqual(
        formatAddress({ address: '2 rue de Lyonnais', postal_code: '69001', city: 'Lyon' }),
        '2 rue de Lyonnais, 69001, Lyon'
    );
});

test('la bulle passe par formatAddress', () => {
    assert.ok(/formatAddress\(place\)/.test(SOURCE));
    assert.ok(!/\[place\.address, place\.postal_code, place\.city\]/.test(SOURCE));
});
