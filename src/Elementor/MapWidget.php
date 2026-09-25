<?php
/**
 * Elementor Widget: Mapped Places
 *
 * Provides extensive customization of the interactive map
 * via 3 Content sections and 15 Style sections.
 *
 * @package MappedPlacesMap
 * @since   3.0.0
 */

namespace MappedPlaces\Elementor;


use MappedPlaces\Map\Defaults;
use MappedPlaces\Plugin;
use MappedPlaces\Map\Renderer;

if (!defined('ABSPATH')) {
    exit;
}

class MapWidget extends \Elementor\Widget_Base {

    use ContentControls;
    use LayoutStyleControls;
    use CardStyleControls;
    use MapStyleControls;
    use HeaderStyleControls;

    /** Widget identifier. */
    const NAME = Integration::WIDGET_NAME;

    /** Settings passed as-is to the renderer. */
    const PLAIN_SETTINGS = [
        'center_lat', 'center_lng', 'show_search', 'show_filter', 'show_list', 'show_fullscreen',
        'sidebar_position', 'sidebar_title', 'sidebar_subtitle', 'tile_style',
    ];

    /** Hauteurs par défaut par appareil, alignées sur le CSS (tablette 600px, mobile 85vh). */
    const TABLET_HEIGHT = ['unit' => 'px', 'size' => 600];
    const MOBILE_HEIGHT = ['unit' => 'vh', 'size' => 85];

    /**
     * Widget identifier used internally by Elementor.
     */
    public function get_name(): string {
        return self::NAME;
    }

    /**
     * Human-readable title shown in the Elementor panel.
     */
    public function get_title(): string {
        return __('Mapped Places', 'mapped-places');
    }

    /**
     * Icon class displayed in the Elementor panel.
     */
    public function get_icon(): string {
        return 'eicon-google-maps';
    }

    /**
     * Widget categories for the Elementor panel.
     *
     * @return string[]
     */
    public function get_categories(): array {
        return ['mapped-places', 'general'];
    }

    /**
     * Search keywords for the Elementor panel.
     *
     * @return string[]
     */
    /**
     * Feuilles de style de la carte : Elementor les charge avec le widget,
     * y compris dans l'éditeur et les modèles globaux.
     *
     * @return string[]
     */
    public function get_style_depends(): array {
        return Plugin::MAP_STYLE_HANDLES;
    }

    /**
     * Scripts de la carte (config JS comprise, via Plugin::enqueue_map_assets
     * au rendu).
     *
     * @return string[]
     */
    public function get_script_depends(): array {
        return Plugin::MAP_SCRIPT_HANDLES;
    }

    public function get_keywords(): array {
        return ['map', 'carte', 'places', 'lieux', 'leaflet'];
    }

    // ------------------------------------------------------------------
    //  Controls Registration
    // ------------------------------------------------------------------

    protected function register_controls(): void {
        $this->register_content_controls();
        $this->register_style_controls();
    }

    /**
     * Register all Content tab controls (3 sections).
     */
    private function register_content_controls(): void {
        $this->register_section_map_parameters();
        $this->register_section_display_options();
        $this->register_section_sidebar_content();
    }

    /**
     * Register all Style tab controls (14 sections).
     */
    private function register_style_controls(): void {
        $this->register_style_layout();
        $this->register_style_sidebar();
        $this->register_style_sidebar_heading();
        $this->register_style_entity_pills();
        $this->register_style_search_bar();
        $this->register_style_filter_dropdown();
        $this->register_style_establishment_cards();
        $this->register_style_type_badge();
        $this->register_style_card_title();
        $this->register_style_card_meta();
        $this->register_style_phone_link();
        $this->register_style_map_colors();
        $this->register_style_clusters();
        $this->register_style_popup();
        $this->register_style_results_header();
        $this->register_style_fullscreen_btn();
    }

    // ------------------------------------------------------------------
    //  Render
    // ------------------------------------------------------------------

    /**
     * Render the map from Elementor settings, through the shared renderer
     * (no shortcode string: a "]" in a title would break it).
     */
    protected function render(): void {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML produit par views/map.php, où chaque valeur est échappée ; wp_kses_post() retirerait les SVG inline.
        echo Renderer::render(self::settings_to_atts($this->get_settings_for_display()));
    }

    /**
     * Map attributes from widget settings; missing settings keep the
     * shared defaults.
     *
     * @param array $settings Elementor settings.
     * @return array<string, string>
     */
    public static function settings_to_atts(array $settings): array {
        $atts = Defaults::all();

        // La hauteur est écrite par Elementor sur le conteneur, par appareil.
        $atts['height'] = Renderer::HEIGHT_FROM_CONTAINER;
        if (array_key_exists('fit_bounds', $settings)) {
            $atts['fit_bounds'] = $settings['fit_bounds'] === 'true' ? 'true' : 'false';
        }
        if (isset($settings['zoom']['size']) && $settings['zoom']['size'] !== '') {
            $atts['zoom'] = (string) $settings['zoom']['size'];
        }
        foreach (self::PLAIN_SETTINGS as $key) {
            if (isset($settings[$key]) && !is_array($settings[$key])) {
                $atts[$key] = (string) $settings[$key];
            }
        }
        return $atts;
    }

    /**
     * Elementor slider default from a CSS length ("600px" → 600 / px).
     *
     * @param string $length
     * @return array{unit: string, size: float|int}
     */
    private static function slider_default(string $length): array {
        if (!preg_match('/^(\d+(?:\.\d+)?)(px|vh|%)$/', $length, $parts)) {
            return ['unit' => 'px', 'size' => 600];
        }
        return ['unit' => $parts[2], 'size' => $parts[1] + 0];
    }

    /**
     * Elementor switcher default ('true' when on, '' when off).
     *
     * @param string $flag
     * @return string
     */
    private static function switcher_default(string $flag): string {
        return filter_var($flag, FILTER_VALIDATE_BOOLEAN) ? 'true' : '';
    }

    // ------------------------------------------------------------------
    //  Editor Preview Template
    // ------------------------------------------------------------------

    /**
     * Provide a simple placeholder for the Elementor live editor.
     * The real map renders only in preview / frontend.
     */
    protected function content_template(): void {
        // Empty: forces Elementor to use server-side render() for live preview.
        // The JS Elementor hook (elementor/frontend/init) reinitializes the map
        // after each server-side re-render in the editor.
    }
}
