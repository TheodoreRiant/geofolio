<?php
/**
 * Rendu HTML de la carte, commun au shortcode et au widget Elementor.
 *
 * La préparation (types imposés, fond de carte, attributs du conteneur) est
 * séparée du gabarit includes/views/map.php, qui n'affiche que des valeurs
 * déjà prêtes.
 */

namespace Geofolio\Map;

use Geofolio\Admin\SettingsPage;
use Geofolio\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

class Renderer {

    /** Longueur CSS acceptée pour height : nombre entier ou décimal et unité. */
    const CSS_LENGTH_PATTERN = '/^\d+(?:\.\d+)?(?:px|vh|vw|%|rem|em)\z/';

    /** Gabarit HTML de la carte. */
    const VIEW = __DIR__ . '/../../views/map.php';

    /**
     * HTML de la carte.
     *
     * @param array $atts Attributs ; les absents prennent la valeur par défaut.
     * @return string
     */
    public static function render(array $atts) {
        // Les bibliothèques de la carte ne sont chargées que sur les pages
        // qui l'affichent.
        if (class_exists(Plugin::class)) {
            Plugin::enqueue_map_assets();
        }

        $view = self::prepare(array_merge(Defaults::all(), $atts));

        ob_start();
        self::include_view($view);
        return (string) ob_get_clean();
    }

    /**
     * Variables du gabarit, sans aucun effet de bord.
     *
     * @param array $atts Attributs complets.
     * @return array
     */
    public static function prepare(array $atts) {
        $show_search = self::flag($atts['show_search']);
        $show_filter = self::flag(self::filter_flag($atts));
        $show_list   = self::flag($atts['show_list']);
        $map_id      = 'gfo-map-' . uniqid();

        return array(
            'map_id'           => $map_id,
            'container'        => self::container_attributes($atts, $map_id),
            'has_sidebar'      => $show_search || $show_filter || $show_list,
            'show_search'      => $show_search,
            'show_filter'      => $show_filter,
            'show_list'        => $show_list,
            'show_fullscreen'  => self::flag($atts['show_fullscreen']),
            'sidebar_title'    => (string) $atts['sidebar_title'],
            'sidebar_subtitle' => (string) $atts['sidebar_subtitle'],
            'height'           => self::sanitize_css_length($atts['height'], Defaults::HEIGHT),
        );
    }

    /**
     * Attributs du conteneur de la carte, lus par le JS.
     *
     * @param array  $atts   Attributs complets.
     * @param string $map_id Identifiant HTML.
     * @return array<string, string>
     */
    public static function container_attributes(array $atts, $map_id) {
        $classes = 'gfo-map-container';
        if ($atts['sidebar_position'] === 'right') {
            $classes .= ' gfo-sidebar-right';
        }

        // Valeurs injectées dans des attributs : types imposés.
        return array(
            'id'              => (string) $map_id,
            'class'           => $classes,
            'data-center-lat' => (string) floatval($atts['center_lat']),
            'data-center-lng' => (string) floatval($atts['center_lng']),
            'data-zoom'       => (string) absint($atts['zoom']),
            'data-tile-style' => self::resolve_tile_style((string) $atts['tile_style']),
        );
    }

    /**
     * Fond de carte : la page décide, sauf si les réglages du site imposent
     * une valeur (cf. SettingsPage). Sans rien de défini, on retombe
     * sur le fond par défaut du registre.
     *
     * @param string $requested Fond demandé par la page ('' = aucun).
     * @return string
     */
    public static function resolve_tile_style($requested) {
        if ($requested !== '') {
            return $requested;
        }
        $forced = SettingsPage::get_forced_tile_style();
        return $forced !== '' ? $forced : TileProviders::DEFAULT_ID;
    }

    /**
     * Longueur CSS simple (nombre et unité px, vh, vw, %, rem ou em), ou la
     * valeur par défaut : la valeur finit dans un attribut style, où
     * esc_attr() laisserait passer une règle supplémentaire.
     *
     * @param mixed  $value   Valeur saisie.
     * @param string $default Valeur de repli.
     * @return string
     */
    public static function sanitize_css_length($value, $default) {
        $value = trim((string) $value);
        return preg_match(self::CSS_LENGTH_PATTERN, $value) ? $value : $default;
    }

    /**
     * Valeur du filtre par type : show_filters (pluriel, widget v1) est
     * encore compris quand show_filter est vide.
     *
     * @param array $atts
     * @return string
     */
    private static function filter_flag(array $atts) {
        $value = (string) $atts['show_filter'];
        if ($value === '' && (string) $atts['show_filters'] !== '') {
            return (string) $atts['show_filters'];
        }
        return $value;
    }

    /**
     * @param mixed $value 'true', 'yes', '1'…
     * @return bool
     */
    private static function flag($value) {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Inclure le gabarit dans une portée isolée.
     *
     * @param array $view Variables préparées.
     */
    private static function include_view(array $view) {
        include self::VIEW;
    }
}
