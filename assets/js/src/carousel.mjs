import { escAttr } from './escape.mjs';
import { formatText } from './i18n.mjs';

/**
 * Construire le HTML d'un carrousel multi-photos. Fallback single
 * image si la galerie n'a qu'une entrée. URL et texte alternatif
 * passent par escAttr() : l'alt est modifiable par un compte Auteur.
 *
 * @param {Array}  images Array d'objets {id, alt, medium, large, srcset, sizes}
 * @param {Object} labels Libelles traduits (slideOf, goToSlide, gallery,
 *                        carousel, previousPhoto, nextPhoto).
 * @returns {string}
 */
function buildCarouselHtml(images, labels) {
    var l = labels || {};
    if (!images || images.length === 0) return '';

    if (images.length === 1) {
        var img = images[0];
        var url = (img.large && img.large.url) || (img.medium && img.medium.url) || '';
        return '<div class="gfo-popup-image"><img src="' + escAttr(url) + '" alt="' + escAttr(img.alt) + '" loading="lazy" /></div>';
    }

    var total = images.length;
    var slidesHtml = images.map(function(img, i) {
        var url = (img.large && img.large.url) || (img.medium && img.medium.url) || '';
        var srcAttr = (i === 0) ? 'src="' + escAttr(url) + '"' : 'src="" data-src="' + escAttr(url) + '"';
        return '<div class="gfo-carousel-slide" role="group" aria-roledescription="slide" ' +
               'aria-label="' + escAttr(formatText(l.slideOf, [i + 1, total])) + '" aria-hidden="' + (i !== 0) + '">' +
                   '<img ' + srcAttr + ' alt="' + escAttr(img.alt) + '" loading="lazy" />' +
               '</div>';
    }).join('');

    var dotsHtml = images.map(function(_, i) {
        return '<button type="button" class="gfo-carousel-dot' + (i === 0 ? ' is-active' : '') + '" ' +
               'role="tab" aria-selected="' + (i === 0) + '" data-slide="' + i + '" ' +
               'aria-label="' + escAttr(formatText(l.goToSlide, [i + 1])) + '"></button>';
    }).join('');

    return '<div class="gfo-carousel" role="region" aria-label="' + escAttr(l.gallery) + '" aria-roledescription="' + escAttr(l.carousel) + '" tabindex="0">' +
        '<div class="gfo-carousel-track" data-current="0" style="transform: translateX(0%)">' + slidesHtml + '</div>' +
        '<button type="button" class="gfo-carousel-prev" aria-label="' + escAttr(l.previousPhoto) + '">' +
            '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>' +
        '</button>' +
        '<button type="button" class="gfo-carousel-next" aria-label="' + escAttr(l.nextPhoto) + '">' +
            '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>' +
        '</button>' +
        '<div class="gfo-carousel-counter" aria-live="polite">1 / ' + total + '</div>' +
        '<div class="gfo-carousel-dots" role="tablist">' + dotsHtml + '</div>' +
        '</div>';
}

export { buildCarouselHtml };
