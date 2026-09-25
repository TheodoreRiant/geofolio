/**
 * Tests du HTML du carrousel du popup (assets/js/src/carousel.mjs).
 *
 * Le texte alternatif d'un média est modifiable par un compte Auteur sans
 * droit unfiltered_html : il ne doit jamais pouvoir sortir de son attribut.
 *
 * Lancer :  node --test tests/js/*.test.js
 */

const test   = require('node:test');
const assert = require('node:assert');
const { load } = require('./modules.js');

function loadBuilder() {
    return load('carousel').buildCarouselHtml;
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
    assert.ok(!html.includes('mapl-carousel'));
});

test('plusieurs images produisent autant de slides que d\'images', () => {
    const html   = loadBuilder()([image('a'), image('b'), image('c')]);
    const slides = html.match(/class="mapl-carousel-slide"/g) || [];
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
