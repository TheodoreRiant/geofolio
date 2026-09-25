/**
 * Mapped Places - Script principal v4.0
 *
 * Architecture cache-first : les donnees sont chargees une seule fois
 * au demarrage, puis filtrees cote client pour un rendu instantane.
 *
 * v4 key change: sidebar shows ESTABLISHMENT CARDS (not category cards)
 * with a DROPDOWN for type filtering. Search + filter are fully client-side.
 */
import { MappedPlacesMap } from './map.mjs';

const $ = window.jQuery;

/* ================================================================ */
/*  INITIALIZATION                                                   */
/* ================================================================ */

$(document).ready(function() {
    $('.mapl-map-container').each(function() {
        var instance = new MappedPlacesMap(this);
        // Store instance on the DOM element for external access
        $(this).data('mapped-places', instance);
    });
});

// Re-init support for Elementor live editor, for every widget name
// declared by PHP (mapped_places_elementor_widget_names).
function onElementorWidgetReady($scope) {
    var $container = $scope.find('.mapl-map-container');
    if (!$container.length) return;

    var existing = $container.data('mapped-places');

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
    $container.data('mapped-places', new MappedPlacesMap($container[0]));
}

// Initialisation d'une carte ajoutée après le chargement (aperçu du bloc
// dans l'éditeur, contenu injecté par un autre script) : toute instance
// existante sur ce conteneur est détruite avant.
window.MappedPlaces = {
    init: function(container) {
        var $container = $(container);
        var existing = $container.data('mapped-places');
        if (existing && typeof existing.destroy === 'function') {
            existing.destroy();
        }
        var instance = new MappedPlacesMap(container);
        $container.data('mapped-places', instance);
        return instance;
    },
};

$(window).on('elementor/frontend/init', function() {
    if (typeof elementorFrontend === 'undefined') return;
    var names = (window.mappedPlacesConfig && mappedPlacesConfig.elementorWidgets) || [];
    names.forEach(function(name) {
        elementorFrontend.hooks.addAction('frontend/element_ready/' + name + '.default', onElementorWidgetReady);
    });
});
