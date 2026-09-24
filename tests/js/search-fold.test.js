/**
 * Tests de la recherche tolérante aux accents et aux apostrophes (VAD-12).
 *
 * « saveurs d'elise » doit trouver « Saveurs d’Élise », et le surlignage
 * doit rester aligné sur le texte d'origine, échappé.
 *
 * Lancer :  node --test tests/js/*.test.js
 */

const test   = require('node:test');
const assert = require('node:assert');
const { load } = require('./modules.js');

function loadHelpers() {
    const { foldText, highlightMatch } = load('text');
    const { matchesSearch } = load('filters');
    return { foldText, highlightMatch, matchesSearch };
}

test('le repli retire accents, casse et apostrophes typographiques', () => {
    const { foldText } = loadHelpers();
    assert.strictEqual(foldText('Saveurs d’Élise'), "saveurs d'elise");
    assert.strictEqual(foldText('ÉCOLE Ç‘a’'), "ecole c'a'");
    assert.strictEqual(foldText(null), '');
});

test('la recherche trouve le titre quelle que soit la saisie', () => {
    const { foldText, matchesSearch } = loadHelpers();
    const place = { title: 'Food Truck Les Saveurs d’Élise', city: 'Lyon' };
    ["saveurs d'elise", 'saveurs d’élise', 'SAVEURS D’ELISE', 'élise'].forEach((saisie) => {
        assert.ok(matchesSearch(place, foldText(saisie)), saisie);
    });
    assert.ok(!matchesSearch(place, foldText('marseille')));
});

test('la recherche reste insensible aux accents sur la ville et les types', () => {
    const { foldText, matchesSearch } = loadHelpers();
    assert.ok(matchesSearch({ title: 'X', city: 'Villeurbanne', types: ['Centre éducatif fermé'] }, foldText('educatif')));
    assert.ok(matchesSearch({ title: 'X', city: 'Écully' }, foldText('ecully')));
});

test('le surlignage vise le texte d\'origine, accents compris', () => {
    const { highlightMatch } = loadHelpers();
    assert.strictEqual(
        highlightMatch('Saveurs d’Élise', "d'elise"),
        'Saveurs <mark>d’Élise</mark>');
});

test('le surlignage échappe le HTML autour et dans la correspondance', () => {
    const { highlightMatch } = loadHelpers();
    assert.strictEqual(highlightMatch('<b>Lyon</b> & co', 'lyon'), '&lt;b&gt;<mark>Lyon</mark>&lt;/b&gt; &amp; co');
    assert.strictEqual(highlightMatch('<b>X</b>', 'zzz'), '&lt;b&gt;X&lt;/b&gt;');
});

test('un accent décomposé reste attaché à sa lettre dans le surlignage', () => {
    const { highlightMatch } = loadHelpers();
    // « é » écrit e + accent combinant (U+0301)
    assert.strictEqual(highlightMatch('Café Lyon', 'cafe'), '<mark>Café</mark> Lyon');
});
