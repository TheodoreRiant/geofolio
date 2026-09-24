<?php
/**
 * Shortcode [geofolio] : attributs complétés par les valeurs par défaut
 * partagées, rendu délégué à Renderer.
 */

namespace Geofolio\Map;

if (!defined('ABSPATH')) {
    exit;
}

class Shortcode {

    /** Nom du shortcode. */
    const TAG = 'geofolio';

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode(self::TAG, array($this, 'render_shortcode'));
    }

    /**
     * Rendu du shortcode [geofolio].
     *
     * @param array|string $atts Attributs du shortcode.
     * @return string HTML de la carte.
     */
    public function render_shortcode($atts) {
        return Renderer::render(shortcode_atts(Defaults::all(), $atts, self::TAG));
    }
}
