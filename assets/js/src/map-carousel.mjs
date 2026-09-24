/**
 * Méthodes de GeofolioMap : Photos du popup : carrousel et repli.
 * Ajoutées au prototype par map.mjs.
 */
import { escAttr } from './escape.mjs';
import { t } from './i18n.mjs';
import { buildCarouselHtml } from './carousel.mjs';

const $ = window.jQuery;

const carouselMethods = {
    /**
     * Construire le slot image « placeholder » affiché lorsqu'un
     * établissement n'a AUCUNE photo (ni galerie ni image à la une).
     * Réutilise la classe .gfo-popup-image avec un modificateur
     * pour styler le rendu générique.
     *
     * @returns {string}
     */
    renderImagePlaceholder() {
        var base = (window.geofolioConfig && geofolioConfig.pluginUrl) ? geofolioConfig.pluginUrl : '';
        var src  = base + 'assets/images/placeholder.svg';
        return '<div class="gfo-popup-image gfo-popup-image--placeholder">' +
                   '<img src="' + src + '" alt="' + escAttr(t('photoPlaceholder')) + '" loading="lazy" />' +
               '</div>';
    },

    /**
     * Construire le HTML d'un carrousel (voir buildCarouselHtml).
     *
     * @param {Array} images
     * @returns {string}
     */
    renderCarousel(images) {
        return buildCarouselHtml(images, {
            slideOf:       t('slideOf'),
            goToSlide:     t('goToSlide'),
            gallery:       t('gallery'),
            carousel:      t('carousel'),
            previousPhoto: t('previousPhoto'),
            nextPhoto:     t('nextPhoto'),
        });
    },

    /**
     * Initialiser les interactions d'un carrousel inséré dans un popup
     * Leaflet : navigation clavier, swipe tactile, lazy-load de la slide
     * courante et suivante.
     *
     * @param {HTMLElement} rootNode Conteneur du popup
     */
    initCarousel(rootNode) {
        var $carousel = $(rootNode).find('.gfo-carousel');
        if (!$carousel.length) return;

        var $track   = $carousel.find('.gfo-carousel-track');
        var $slides  = $carousel.find('.gfo-carousel-slide');
        var $dots    = $carousel.find('.gfo-carousel-dot');
        var $counter = $carousel.find('.gfo-carousel-counter');
        var total    = $slides.length;
        if (total <= 1) return;

        var state = { index: 0 };

        function ensureImageLoaded(idx) {
            var $img = $slides.eq(idx).find('img');
            if ($img.data('src') && !$img.attr('src')) {
                $img.attr('src', $img.data('src')).removeAttr('data-src');
            }
        }

        function goTo(i) {
            i = (i + total) % total;
            state.index = i;
            $track.css('transform', 'translateX(-' + (i * 100) + '%)').attr('data-current', i);
            $slides.attr('aria-hidden', 'true').eq(i).attr('aria-hidden', 'false');
            $dots.removeClass('is-active').attr('aria-selected', 'false')
                .eq(i).addClass('is-active').attr('aria-selected', 'true');
            $counter.text((i + 1) + ' / ' + total);
            ensureImageLoaded(i);
            ensureImageLoaded((i + 1) % total);
        }

        $carousel.off('.carousel');
        $carousel.on('click.carousel', '.gfo-carousel-prev', function(e) { e.stopPropagation(); goTo(state.index - 1); });
        $carousel.on('click.carousel', '.gfo-carousel-next', function(e) { e.stopPropagation(); goTo(state.index + 1); });
        $carousel.on('click.carousel', '.gfo-carousel-dot',  function(e) { e.stopPropagation(); goTo(parseInt($(this).data('slide'), 10)); });

        $carousel.on('keydown.carousel', function(e) {
            if (e.key === 'ArrowLeft')      { e.preventDefault(); goTo(state.index - 1); }
            else if (e.key === 'ArrowRight') { e.preventDefault(); goTo(state.index + 1); }
        });

        var trackEl = $track[0];
        if (trackEl) {
            var startX = 0, startTime = 0;
            // stopPropagation pour que le swipe horizontal du carrousel
            // ne déclenche pas le drag/pan de la carte Leaflet sous-jacente.
            trackEl.addEventListener('touchstart', function(e) {
                e.stopPropagation();
                startX    = e.changedTouches[0].clientX;
                startTime = Date.now();
            }, { passive: true });
            trackEl.addEventListener('touchmove', function(e) {
                e.stopPropagation();
            }, { passive: true });
            trackEl.addEventListener('touchend', function(e) {
                e.stopPropagation();
                var dx = e.changedTouches[0].clientX - startX;
                var dt = Date.now() - startTime;
                if (Math.abs(dx) > 40 && dt < 500) {
                    goTo(state.index + (dx < 0 ? 1 : -1));
                }
            }, { passive: true });
        }
    },

    /**
     * Injecter le carrousel dans le popup ouvert : remplace le
     * placeholder shimmer par le markup carrousel et l'initialise.
     * Fallback sur thumbnail si la galerie est vide ou fetch échoué.
     *
     * @param {jQuery} $node    Le contenu du popup
     * @param {Array}  gallery  Array d'images (peut être vide)
     */
    injectGalleryIntoPopup($node, gallery) {
        var $placeholder = $node.find('.gfo-popup-image-placeholder');
        if (!$placeholder.length) return;

        if (gallery && gallery.length > 0) {
            $placeholder.replaceWith(this.renderCarousel(gallery));
            this.initCarousel($node[0]);
            return;
        }

        // Fallback : essayer le thumbnail (featured image) depuis le cache liste
        var placeId = parseInt($node.find('.gfo-popup-content').data('place-id'), 10);
        var place   = this.allPlaces.find(function(e) { return e.id === placeId; });
        if (place && place.thumbnail) {
            $placeholder.replaceWith('<div class="gfo-popup-image"><img src="' + escAttr(place.thumbnail) + '" alt="' + escAttr(place.title) + '" loading="lazy" /></div>');
        } else {
            // Galerie vide et pas d'image à la une : placeholder générique
            $placeholder.replaceWith(this.renderImagePlaceholder());
        }
    },
};

export { carouselMethods };
