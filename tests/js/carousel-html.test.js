/**
 * Tests du HTML du carrousel du popup (assets/js/geofolio.js).
 *
 * Le texte alternatif d'un média est modifiable par un compte Auteur sans
 * droit unfiltered_html : il ne doit jamais pouvoir sortir de son attribut.
 *
 * Lancer :  node --test tests/js/*.test.js
 */

const test   = require('node:test');
const assert = require('node:assert');
const fs     = require('node:fs');
const path   = require('node:path');

function loadBuilder() {
    const source = fs.readFileSync(
        path.join(__dirname, '..', '..', 'assets', 'js', 'geofolio.js'), 'utf8');
    const escStart = source.indexOf('    /* HTML-escape');
    const escEnd   = source.indexOf('    /* N\'accepte qu\'une URL', escStart);
    const start    = source.indexOf('    /* ---- CARROUSEL DU POPUP : début ---- */');
    const end      = source.indexOf('    /* ---- CARROUSEL DU POPUP : fin ---- */', start);

    assert.ok(escStart !== -1 && escEnd > escStart, 'escHtml introuvable dans geofolio.js');
    assert.ok(start !== -1 && end > start, 'Bloc « CARROUSEL DU POPUP » introuvable dans geofolio.js');

    const i18nStart = source.indexOf('    /* ---- I18N : début ---- */');
    const i18nEnd   = source.indexOf('    /* ---- I18N : fin ---- */', i18nStart);
    assert.ok(i18nStart !== -1 && i18nEnd > i18nStart, 'Bloc « I18N » introuvable dans geofolio.js');

    return new Function(
        source.slice(escStart, escEnd) + source.slice(i18nStart, i18nEnd) + source.slice(start, end)
        + '; return buildCarouselHtml;')();
}

const PAYLOAD = '" onload="alert(1)';

function image(alt, url) {
    return { id: 1, alt: alt, large: { url: url || 'https://a.fr/1.jpg' } };
}

/* `onload=` hors d'une valeur d'attribut : l'attribut aurait été créé.
   Les valeurs entre guillemets sont retirées avant la recherche. */
function hasInjectedAttribute(html) {
    return /onload=/.test(html.replace(/="[^"]*"/g, '=""'));
}

test('un texte alternatif piégé reste dans son attribut (image unique)', () => {
    const html = loadBuilder()([image(PAYLOAD)]);
    assert.ok(!hasInjectedAttribute(html), html);
    assert.ok(html.includes('&quot;'));
});

test('un texte alternatif piégé reste dans son attribut (plusieurs images)', () => {
    const html = loadBuilder()([image(PAYLOAD), image(PAYLOAD)]);
    assert.ok(!hasInjectedAttribute(html), html);
    assert.ok(html.includes('&quot;'));
});

test('une URL contenant un guillemet reste dans son attribut, data-src compris', () => {
    const url  = 'https://a.fr/x.jpg' + PAYLOAD;
    const one  = loadBuilder()([image('ok', url)]);
    const many = loadBuilder()([image('ok', url), image('ok', url)]);
    assert.ok(!hasInjectedAttribute(one), one);
    assert.ok(!hasInjectedAttribute(many), many);
    assert.ok(many.includes('data-src="https://a.fr/x.jpg&quot;'));
});

test('une image unique produit une balise img sans carrousel', () => {
    const html = loadBuilder()([image('Façade')]);
    assert.ok(html.includes('<img '));
    assert.ok(!html.includes('gfo-carousel'));
});

test('plusieurs images produisent autant de slides que d\'images', () => {
    const html   = loadBuilder()([image('a'), image('b'), image('c')]);
    const slides = html.match(/class="gfo-carousel-slide"/g) || [];
    assert.strictEqual(slides.length, 3);
});

test('une galerie vide ne produit rien', () => {
    assert.strictEqual(loadBuilder()([]), '');
    assert.strictEqual(loadBuilder()(null), '');
});

test('les libellés du carrousel viennent de la traduction fournie', () => {
    const build  = loadBuilder();
    const images = [{ alt: 'a', large: { url: 'https://x/1.jpg' } }, { alt: 'b', large: { url: 'https://x/2.jpg' } }];
    const html   = build(images, {
        slideOf: '%1$d sur %2$d', goToSlide: 'Aller à la photo %d', gallery: 'Galerie photos',
        carousel: 'carrousel', previousPhoto: 'Photo précédente', nextPhoto: 'Photo suivante',
    });

    assert.ok(html.includes('aria-label="2 sur 2"'));
    assert.ok(html.includes('aria-label="Aller à la photo 1"'));
    assert.ok(html.includes('aria-roledescription="carrousel"'));
    assert.ok(html.includes('aria-label="Photo suivante"'));
});
