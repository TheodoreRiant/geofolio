/**
 * Personnes dans le popup : une ligne « Rôle : Nom » par personne, dans
 * l'ordre reçu ; repli sur l'ancien champ manager quand la liste est vide.
 *
 * Lancer :  node --test tests/js/*.test.js
 */

const test   = require('node:test');
const assert = require('node:assert');
const { load } = require('./modules.js');

function render(place) {
    global.mappedPlacesConfig = {
        i18n: { roleSeparator: ' : ', manager: 'Directeur/trice : ', managers: 'Directeurs/trices : ' },
    };
    const { markersMethods } = load('map-markers');
    const context = {
        getTypeConfig: () => ({ color: '#123456', label: 'Bibliothèque', svgPath: '' }),
        renderImagePlaceholder: () => '',
    };
    return markersMethods.createPopupContent.call(context, place);
}

test('chaque personne a sa ligne, rôle en gras puis nom, dans l\'ordre', () => {
    const html = render({
        id: 9, title: 'Harbour Library', types: ['Library'],
        people: [
            { role: 'Directrice', name: 'Marie Beton' },
            { role: 'Secrétaire général', name: 'Antonin Klark' },
        ],
        manager: 'Marie Beton, Antonin Klark',
    });
    const lines = html.match(/<p class="mapl-popup-manager">.*?<\/p>/g);
    assert.strictEqual(lines.length, 2);
    assert.strictEqual(lines[0], '<p class="mapl-popup-manager"><strong>Directrice : </strong>Marie Beton</p>');
    assert.strictEqual(lines[1], '<p class="mapl-popup-manager"><strong>Secrétaire général : </strong>Antonin Klark</p>');
});

test('une personne sans rôle n\'affiche que son nom, et le HTML est échappé', () => {
    const html = render({ id: 1, title: 'X', people: [{ role: '', name: 'Jean <b>Dupont</b>' }] });
    assert.ok(html.includes('<p class="mapl-popup-manager">Jean &lt;b&gt;Dupont&lt;/b&gt;</p>'));
});

test('sans liste de personnes, l\'ancien champ manager garde son libellé', () => {
    const html = render({ id: 1, title: 'X', people: [], manager: 'Jeanne Martin' });
    assert.ok(html.includes('<p class="mapl-popup-manager"><strong>Directeur/trice : </strong>Jeanne Martin</p>'));
});
