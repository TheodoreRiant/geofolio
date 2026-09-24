<?php
/**
 * Registre des fonds de carte (tuiles).
 *
 * Source unique de vérité : les gabarits d'URL vivent ici (PHP) et sont
 * transmis au JS via wp_localize_script. Le JS ne code plus aucune URL en dur.
 *
 * Un fournisseur qui exige une clé (`requires_key`) porte le jeton `{key}`
 * dans son gabarit. La clé est injectée côté serveur au moment du rendu.
 *
 * ATTENTION : une clé de tuiles est forcément visible dans le code source de
 * la page (le navigateur doit l'envoyer au fournisseur). Il faut donc toujours
 * la restreindre par domaine référent chez le fournisseur.
 */

namespace Geofolio\Map;

if (!defined('ABSPATH')) {
    exit;
}

final class TileProviders {

    /**
     * Fond utilisé quand le fond demandé est inconnu ou qu'il lui manque sa clé.
     * Choisi sans clé, officiel et en français.
     */
    const FALLBACK_ID = 'openfreemap-positron';

    /** Identifiant du fond entièrement paramétrable par l'utilisateur. */
    const CUSTOM_ID = 'custom';

    /**
     * Fond utilisé quand ni la page ni les réglages du site n'en imposent un.
     *
     * Reste CARTO Positron, le rendu historique du site. Sans clé CARTO ce
     * n'est PAS un piège : resolve() bascule alors seul sur FALLBACK_ID, donc
     * aucune page ne peut afficher le filigrane « API KEY REQUIRED ».
     *
     * L'avoir mis a FALLBACK_ID en 2.7.0 etait un garde-fou redondant, et il
     * rendait les fonds incoherents : les pages dont le widget Elementor avait
     * enregistre « positron » gardaient Positron, tandis que celles sans valeur
     * explicite basculaient sur le fond de repli.
     */
    const DEFAULT_ID = 'positron';

    /** Zoom maximum commun à tous les fonds. */
    const MAX_ZOOM = 19;

    /**
     * Définition brute des fournisseurs.
     *
     * Retourne un nouveau tableau à chaque appel : aucun état partagé mutable.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all() {
        return array(
            'ign-plan' => array(
                'id'           => 'ign-plan',
                'label'        => __('IGN map (France, no key)', 'geofolio'),
                'type'         => 'raster',
                'url'          => 'https://data.geopf.fr/wmts?SERVICE=WMTS&VERSION=1.0.0&REQUEST=GetTile&LAYER=GEOGRAPHICALGRIDSYSTEMS.PLANIGNV2&STYLE=normal&TILEMATRIXSET=PM&TILEMATRIX={z}&TILEROW={y}&TILECOL={x}&FORMAT=image/png',
                'attribution'  => '&copy; <a href="https://www.ign.fr/">IGN-F</a> / <a href="https://geoservices.ign.fr/">Géoplateforme</a>',
                'subdomains'   => '',
                'requires_key' => false,
                'key_url'      => '',
            ),
            'osm-fr' => array(
                'id'           => 'osm-fr',
                'label'        => __('OpenStreetMap France (no key)', 'geofolio'),
                'type'         => 'raster',
                'url'          => 'https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png',
                'attribution'  => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> France',
                'subdomains'   => 'abc',
                'requires_key' => false,
                'key_url'      => '',
            ),
            // CARTO exige une clé API depuis 2026 : sans clé, leurs serveurs
            // renvoient bien une tuile en HTTP 200, mais estampillée
            // « API KEY REQUIRED » en travers de la carte. D'où requires_key.
            'voyager' => array(
                'id'           => 'voyager',
                'label'        => __('CARTO Voyager — light (key required)', 'geofolio'),
                'type'         => 'raster',
                'url'          => 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png?key={key}',
                'attribution'  => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>',
                'subdomains'   => 'abcd',
                'requires_key' => true,
                'key_url'      => 'https://carto.com/basemaps/apikey',
            ),
            'positron' => array(
                'id'           => 'positron',
                'label'        => __('CARTO Positron — minimal (key required)', 'geofolio'),
                'type'         => 'raster',
                'url'          => 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png?key={key}',
                'attribution'  => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>',
                'subdomains'   => 'abcd',
                'requires_key' => true,
                'key_url'      => 'https://carto.com/basemaps/apikey',
            ),
            'darkmatter' => array(
                'id'           => 'darkmatter',
                'label'        => __('CARTO Dark Matter — dark (key required)', 'geofolio'),
                'type'         => 'raster',
                'url'          => 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png?key={key}',
                'attribution'  => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/">CARTO</a>',
                'subdomains'   => 'abcd',
                'requires_key' => true,
                'key_url'      => 'https://carto.com/basemaps/apikey',
            ),
            'osm' => array(
                'id'           => 'osm',
                'label'        => __('OpenStreetMap standard (no key)', 'geofolio'),
                'type'         => 'raster',
                'url'          => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                'attribution'  => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                'subdomains'   => 'abc',
                'requires_key' => false,
                'key_url'      => '',
            ),
            'ign-epure' => array(
                'id'           => 'ign-epure',
                'label'        => __('IGN clean map — vector, experimental (no key)', 'geofolio'),
                'type'         => 'vector',
                'url'          => 'https://data.geopf.fr/annexes/ressources/vectorTiles/styles/PLAN.IGN/epure.json',
                'attribution'  => '&copy; <a href="https://www.ign.fr/">IGN-F</a> / <a href="https://geoservices.ign.fr/">Géoplateforme</a>',
                'subdomains'   => '',
                'requires_key' => false,
                'key_url'      => '',
            ),
            'ign-gris' => array(
                'id'           => 'ign-gris',
                'label'        => __('IGN grey map — vector, experimental (no key)', 'geofolio'),
                'type'         => 'vector',
                'url'          => 'https://data.geopf.fr/annexes/ressources/vectorTiles/styles/PLAN.IGN/gris.json',
                'attribution'  => '&copy; <a href="https://www.ign.fr/">IGN-F</a> / <a href="https://geoservices.ign.fr/">Géoplateforme</a>',
                'subdomains'   => '',
                'requires_key' => false,
                'key_url'      => '',
            ),
            'openfreemap-positron' => array(
                'id'           => 'openfreemap-positron',
                'label'        => __('Positron via OpenFreeMap — vector, no key, worldwide', 'geofolio'),
                'type'         => 'vector',
                'url'          => 'https://tiles.openfreemap.org/styles/positron',
                'attribution'  => '&copy; <a href="https://openfreemap.org/" target="_blank">OpenFreeMap</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                'subdomains'   => '',
                'requires_key' => false,
                'key_url'      => '',
            ),
            'jawg-light' => array(
                'id'           => 'jawg-light',
                'label'        => __('Jawg Light — clean, French labels (key required)', 'geofolio'),
                'type'         => 'raster',
                'url'          => 'https://tile.jawg.io/jawg-light/{z}/{x}/{y}{r}.png?access-token={key}&lang=fr',
                'attribution'  => '<a href="https://jawg.io" target="_blank">&copy; Jawg</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                'subdomains'   => '',
                'requires_key' => true,
                'key_url'      => 'https://www.jawg.io/lab/access-tokens',
            ),
            'maptiler-streets' => array(
                'id'           => 'maptiler-streets',
                'label'        => __('MapTiler Streets (key required)', 'geofolio'),
                'type'         => 'raster',
                'url'          => 'https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key={key}',
                'attribution'  => '<a href="https://www.maptiler.com/copyright/" target="_blank">&copy; MapTiler</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                'subdomains'   => '',
                'requires_key' => true,
                'key_url'      => 'https://cloud.maptiler.com/account/keys/',
            ),
            'stadia-smooth' => array(
                'id'           => 'stadia-smooth',
                'label'        => __('Stadia Alidade Smooth (key required)', 'geofolio'),
                'type'         => 'raster',
                'url'          => 'https://tiles.stadiamaps.com/tiles/alidade_smooth/{z}/{x}/{y}{r}.png?key={key}',
                'attribution'  => '&copy; <a href="https://stadiamaps.com/" target="_blank">Stadia Maps</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                'subdomains'   => '',
                'requires_key' => true,
                'key_url'      => 'https://client.stadiamaps.com/dashboard/',
            ),
            'thunderforest-atlas' => array(
                'id'           => 'thunderforest-atlas',
                'label'        => __('Thunderforest Atlas (key required)', 'geofolio'),
                'type'         => 'raster',
                'url'          => 'https://{s}.tile.thunderforest.com/atlas/{z}/{x}/{y}.png?apikey={key}',
                'attribution'  => '&copy; <a href="https://www.thunderforest.com/" target="_blank">Thunderforest</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                'subdomains'   => 'abc',
                'requires_key' => true,
                'key_url'      => 'https://manage.thunderforest.com/dashboard',
            ),
            self::CUSTOM_ID => array(
                'id'           => self::CUSTOM_ID,
                'label'        => __('Custom URL (other provider)', 'geofolio'),
                'type'         => 'raster',
                'url'          => '',   // fourni par les réglages
                'attribution'  => '',   // fournie par les réglages
                'subdomains'   => 'abc',
                'requires_key' => false,
                'key_url'      => '',
            ),
        );
    }

    /**
     * Liste id => libellé, pour les <select> de l'admin et d'Elementor.
     *
     * @return array<string, string>
     */
    public static function labels() {
        $labels = array();
        foreach (self::all() as $id => $provider) {
            $labels[$id] = $provider['label'];
        }
        return $labels;
    }

    /**
     * Le fournisseur existe-t-il ?
     *
     * @param string $id Identifiant.
     * @return bool
     */
    public static function exists($id) {
        $all = self::all();
        return is_string($id) && isset($all[$id]);
    }

    /**
     * Le fournisseur exige-t-il une clé ?
     *
     * @param string $id Identifiant.
     * @return bool
     */
    public static function requires_key($id) {
        $all = self::all();
        return isset($all[$id]) && !empty($all[$id]['requires_key']);
    }

    /**
     * Un gabarit d'URL de tuiles est-il exploitable ?
     *
     * Exigences : HTTPS (les pages du site le sont) et présence des trois
     * jetons de coordonnées. Évite d'enregistrer une URL qui ne produira
     * silencieusement aucune tuile.
     *
     * @param string $url Gabarit saisi.
     * @return bool
     */
    public static function is_valid_url_template($url) {
        if (!is_string($url) || $url === '') {
            return false;
        }
        if (stripos($url, 'https://') !== 0) {
            return false;
        }
        foreach (array('{z}', '{x}', '{y}') as $token) {
            if (strpos($url, $token) === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * Résoudre un fond de carte en une définition prête pour le JS.
     *
     * Retourne TOUJOURS une définition affichable : si le fond demandé est
     * inconnu, mal configuré, ou qu'il lui manque sa clé, on retombe sur
     * FALLBACK_ID (sans clé) plutôt que d'afficher une carte en erreur.
     *
     * @param string $id         Identifiant du fond demandé.
     * @param string $api_key    Clé API disponible (peut être vide).
     * @param array  $custom     Réglages du fond personnalisé : url, attribution, subdomains.
     * @return array{id:string,type:string,url:string,attribution:string,subdomains:string,maxZoom:int,requestedId:string,fallbackReason:string}
     */
    public static function resolve($id, $api_key = '', array $custom = array()) {
        $all       = self::all();
        $requested = is_string($id) ? $id : '';
        $api_key   = is_string($api_key) ? trim($api_key) : '';

        if (!isset($all[$requested])) {
            return self::fallback($requested, 'unknown');
        }

        $provider = $all[$requested];

        if ($requested === self::CUSTOM_ID) {
            $url = isset($custom['url']) ? (string) $custom['url'] : '';
            if (!self::is_valid_url_template($url)) {
                return self::fallback($requested, 'invalid_custom_url');
            }
            if (strpos($url, '{key}') !== false && $api_key === '') {
                return self::fallback($requested, 'missing_key');
            }
            $provider['url']         = $url;
            $provider['attribution'] = isset($custom['attribution']) ? (string) $custom['attribution'] : '';
            if (isset($custom['subdomains'])) {
                $provider['subdomains'] = (string) $custom['subdomains'];
            }
        } elseif (!empty($provider['requires_key']) && $api_key === '') {
            return self::fallback($requested, 'missing_key');
        }

        return self::to_definition($provider, $api_key, $requested, '');
    }

    /**
     * Construire la définition de repli.
     *
     * @param string $requested Identifiant initialement demandé.
     * @param string $reason    Motif du repli.
     * @return array
     */
    private static function fallback($requested, $reason) {
        $all = self::all();
        return self::to_definition($all[self::FALLBACK_ID], '', $requested, $reason);
    }

    /**
     * Normaliser un fournisseur en définition consommable par le JS.
     *
     * @param array  $provider  Fournisseur brut.
     * @param string $api_key   Clé à injecter.
     * @param string $requested Identifiant demandé à l'origine.
     * @param string $reason    Motif de repli ('' si aucun).
     * @return array
     */
    private static function to_definition(array $provider, $api_key, $requested, $reason) {
        return array(
            'id'             => $provider['id'],
            'type'           => $provider['type'],
            'url'            => str_replace('{key}', rawurlencode($api_key), $provider['url']),
            'attribution'    => self::add_noopener($provider['attribution']),
            'subdomains'     => $provider['subdomains'],
            'maxZoom'        => self::MAX_ZOOM,
            'requestedId'    => $requested,
            'fallbackReason' => $reason,
        );
    }

    /**
     * Ajouter noopener au rel de chaque lien qui porte un attribut target :
     * sans lui, la page ouverte accède à window.opener. Remplace
     * wp_targeted_link_rel(), dépréciée depuis WordPress 6.7.
     *
     * @param string $html Attribution HTML.
     * @return string
     */
    public static function add_noopener($html) {
        return (string) preg_replace_callback('/<a\s[^>]*>/i', static function ($match) {
            $tag = $match[0];
            if (stripos($tag, 'target=') === false || stripos($tag, 'noopener') !== false) {
                return $tag;
            }
            if (preg_match('/\srel=(["\'])/i', $tag)) {
                return preg_replace('/\srel=(["\'])/i', ' rel=$1noopener ', $tag, 1);
            }
            return preg_replace('/\s*\/?>$/', ' rel="noopener"$0', $tag, 1);
        }, (string) $html);
    }
}
