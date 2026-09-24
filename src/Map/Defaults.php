<?php
/**
 * Valeurs par défaut de la carte, source unique pour le shortcode, le widget
 * Elementor et l'API. Les valeurs du cœur sont neutres ; un préréglage ou un
 * plugin tiers les adapte par les filtres geofolio_defaults et
 * geofolio_default_color.
 */

namespace Geofolio\Map;

if (!defined('ABSPATH')) {
    exit;
}

class Defaults {

    /** Hauteur de la carte. */
    const HEIGHT = '600px';

    /** Centre de la France métropolitaine, zoom national. */
    const CENTER_LAT = '46.6034';
    const CENTER_LNG = '1.8883';
    const ZOOM       = '6';

    /** Couleur des marqueurs et badges sans entité colorée. */
    const COLOR = '#1F4E79';

    /** Couleur hexadécimale à 3 ou 6 chiffres. */
    const COLOR_PATTERN = '/^#(?:[0-9a-fA-F]{3}){1,2}$/';

    /**
     * Attributs de la carte et leur valeur par défaut. Un filtre peut
     * modifier des valeurs, pas retirer de clé.
     *
     * @return array<string, string>
     */
    public static function all() {
        $defaults = self::base();
        $filtered = apply_filters('geofolio_defaults', $defaults);

        return is_array($filtered)
            ? array_map('strval', array_merge($defaults, array_intersect_key($filtered, $defaults)))
            : $defaults;
    }

    /**
     * Couleur de repli des marqueurs et badges.
     *
     * @return string
     */
    public static function color() {
        $color = apply_filters('geofolio_default_color', self::COLOR);
        return is_string($color) && preg_match(self::COLOR_PATTERN, $color) ? $color : self::COLOR;
    }

    /**
     * Valeurs du cœur, avant filtre.
     *
     * @return array<string, string>
     */
    private static function base() {
        return array(
            'height'           => self::HEIGHT,
            'center_lat'       => self::CENTER_LAT,
            'center_lng'       => self::CENTER_LNG,
            'zoom'             => self::ZOOM,
            'show_search'      => 'true',
            'show_filter'      => 'true',
            'show_filters'     => '',  // ancien nom (widget v1), toujours compris
            'show_list'        => 'true',
            'show_fullscreen'  => 'false',
            'sidebar_position' => 'left',
            'sidebar_title'    => __('Our locations', 'geofolio'),
            'sidebar_subtitle' => '',
            'tile_style'       => '',
        );
    }
}
