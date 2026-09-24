/* ================================================================ */
/*  FONDS DE CARTE                                                   */
/* ================================================================ */

/* Les gabarits d'URL sont definis cote PHP (includes/class-tile-providers.php)
   et transmis via geofolioConfig.tiles, cles API deja injectees. Le JS ne
   code plus aucune URL de fournisseur en dur.

   Seule exception : ce repli de derniere chance, utilise si le JS est servi
   depuis un cache plus recent que le PHP (geofolioConfig.tiles absent).
   Il est volontairement SANS CLE pour ne jamais afficher d'erreur
   d'authentification a la place de la carte. */
const FALLBACK_TILE = {
    id:          'osm',
    type:        'raster',
    url:         'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    subdomains:  'abc',
    maxZoom:     19,
};

/**
 * Construire la definition d'un fond a partir de la table serveur.
 * Retourne null si le fond est absent ou indisponible (clé manquante).
 */
function readTileProvider(config, id) {
    if (!config || !config.providers || !id) return null;

    const provider = config.providers[id];
    if (!provider || !provider.available || !provider.url) return null;

    return {
        id:          id,
        type:        provider.type || 'raster',
        url:         provider.url,
        attribution: provider.attribution || '',
        subdomains:  provider.subdomains || '',
        maxZoom:     provider.maxZoom || FALLBACK_TILE.maxZoom,
    };
}

/**
 * Resoudre le fond a afficher pour un conteneur.
 *
 * Ordre de priorite : fond impose dans les reglages du site > fond de la
 * page (Elementor / shortcode) > fond de repli. On ne retourne jamais un
 * fond dont la cle API manque : le visiteur verrait le message d'erreur du
 * fournisseur a la place de la carte.
 *
 * @param {string} pageStyle     Fond demande par la page.
 * @param {boolean} vectorReady  maplibre-gl-leaflet est-il disponible ?
 */
function resolveTile(pageStyle, vectorReady) {
    const config = (window.geofolioConfig && window.geofolioConfig.tiles) || null;
    if (!config) return FALLBACK_TILE;

    const wanted = config.forced || pageStyle;
    let tile = readTileProvider(config, wanted);

    // Un fond vectoriel sans son moteur de rendu ne peut pas s'afficher.
    if (tile && tile.type === 'vector' && !vectorReady) {
        tile = null;
    }
    if (!tile) {
        tile = readTileProvider(config, config.fallback);
    }
    return tile || FALLBACK_TILE;
}

export { FALLBACK_TILE, readTileProvider, resolveTile };
