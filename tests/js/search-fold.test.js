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
const fs     = require('node:fs');
const path   = require('node:path');

const SOURCE = fs.readFileSync(
    path.join(__dirname, '..', '..', 'assets', 'js', 'geofolio.js'), 'utf8');

function slice(from, to) {
    const start = SOURCE.indexOf(from);
    const end   = SOURCE.indexOf(to, start);
    assert.ok(start !== -1 && end > start, 'Bloc introuvable : ' + from);
    return SOURCE.slice(start, end);
}

/* Échappement équivalent à escHtml (qui passe par jQuery dans la page). */
const escHtml = (s) => String(s === undefined || s === null ? '' : s)
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

function loadHelpers() {
    const fold      = slice('    function foldChar', '    /* Wrap the first');
    const highlight = slice('    /* Wrap the first', '    /* Libelle de la ligne');
    const filters   = slice('    function matchesSearch', '    function matchesType');
    return new Function('escHtml', fold + highlight + filters
        + '; return { foldText, highlightMatch, matchesSearch };')(escHtml);
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
