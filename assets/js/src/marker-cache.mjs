/* Cache des marqueurs Leaflet, par identifiant de lieu : un filtre ou une
   frappe de recherche réaffiche les marqueurs existants au lieu de refaire
   icône, popup et écouteurs pour chaque lieu. */

/**
 * Marqueurs des lieux à afficher, en réutilisant ceux du cache.
 *
 * @param {Object}   cache  Marqueurs déjà construits, par id (non modifié).
 * @param {Array}    places Lieux à afficher.
 * @param {Function} create Construit le marqueur d'un lieu.
 * @returns {{cache: Object, markers: Array}} Nouveau cache (anciens marqueurs
 *          conservés) et marqueurs à afficher, dans l'ordre des lieux.
 */
function syncMarkerCache(cache, places, create) {
    var next    = Object.assign({}, cache);
    var markers = [];
    places.forEach(function(place) {
        if (!place.lat || !place.lng) return;
        if (!next[place.id]) {
            next[place.id] = create(place);
        }
        markers.push(next[place.id]);
    });
    return { cache: next, markers: markers };
}

export { syncMarkerCache };
