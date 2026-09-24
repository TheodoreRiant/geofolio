/**
 * Geofolio - Script principal v4.0
 *
 * Architecture cache-first : les donnees sont chargees une seule fois
 * au demarrage, puis filtrees cote client pour un rendu instantane.
 *
 * v4 key change: sidebar shows ESTABLISHMENT CARDS (not category cards)
 * with a DROPDOWN for type filtering. Search + filter are fully client-side.
 */
import { GeofolioMap } from './map.mjs';

const $ = window.jQuery;

/* ================================================================ */
/*  INITIALIZATION                                                   */
/* ================================================================ */

$(document).ready(function() {
    $('.gfo-map-container').each(function() {
        var instance = new GeofolioMap(this);
        // Store instance on the DOM element for external access
        $(this).data('geofolio', instance);
    });
});

// Re-init support for Elementor live editor, for every widget name
// declared by PHP (geofolio_elementor_widget_names).
function onElementorWidgetReady($scope) {
    var $container = $scope.find('.gfo-map-container');
    if (!$container.length) return;

    var existing = $container.data('geofolio');

    // Hors editeur, l'instance creee au chargement est deja
    // operationnelle : la detruire pour la recreer doublait la
    // requete REST sur chaque page portant la carte.
    var enEdition = (typeof elementorFrontend.isEditMode === 'function')
        && elementorFrontend.isEditMode();
    if (existing && !enEdition) {
        return;
    }

    // Dans l'editeur, on repart d'une instance neuve a chaque
    // rendu pour refleter les reglages modifies.
    if (existing && typeof existing.destroy === 'function') {
        existing.destroy();
    }
    $container.data('geofolio', new GeofolioMap($container[0]));
}

$(window).on('elementor/frontend/init', function() {
    if (typeof elementorFrontend === 'undefined') return;
    var names = (window.geofolioConfig && geofolioConfig.elementorWidgets) || [];
    names.forEach(function(name) {
        elementorFrontend.hooks.addAction('frontend/element_ready/' + name + '.default', onElementorWidgetReady);
    });
});
