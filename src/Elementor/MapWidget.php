<?php
/**
 * Elementor Widget: Geofolio
 *
 * Provides extensive customization of the interactive map
 * via 3 Content sections and 15 Style sections.
 *
 * @package GeofolioMap
 * @since   3.0.0
 */

namespace Geofolio\Elementor;

use Geofolio\Domain\Schema;

use Geofolio\Map\Defaults;
use Geofolio\Plugin;
use Geofolio\Map\Renderer;
use Geofolio\Map\TileProviders;

if (!defined('ABSPATH')) {
    exit;
}

class MapWidget extends \Elementor\Widget_Base {

    use HeaderStyleControls;

    /** Widget identifier. */
    const NAME = 'geofolio_map';

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
        return __('Geofolio', 'geofolio');
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
        return ['geofolio', 'general'];
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

    // ==================================================================
    //  CONTENT TAB
    // ==================================================================

    /**
     * Section: Map Parameters
     */
    private function register_section_map_parameters(): void {
        $defaults = Defaults::all();
        $this->start_controls_section(
            'section_map_parameters',
            [
                'label' => __('Map parameters', 'geofolio'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // Responsive, sur le seul conteneur : le wrapper le remplit
        // (height="container"). Les défauts tablette et mobile reprennent
        // ceux du CSS, qu'une valeur ordinateur n'écrase plus.
        $this->add_responsive_control(
            'map_height',
            [
                'label'          => __('Map height', 'geofolio'),
                'type'           => \Elementor\Controls_Manager::SLIDER,
                'size_units'     => ['px', 'vh', '%'],
                'range'          => [
                    'px' => ['min' => 200, 'max' => 1200, 'step' => 10],
                    'vh' => ['min' => 20, 'max' => 100, 'step' => 5],
                    '%'  => ['min' => 20, 'max' => 100, 'step' => 5],
                ],
                'default'        => self::slider_default($defaults['height']),
                'tablet_default' => self::TABLET_HEIGHT,
                'mobile_default' => self::MOBILE_HEIGHT,
                'selectors'      => [
                    '{{WRAPPER}} .gfo-map-container' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'map_full_bleed',
            [
                'label'        => __('Full width (break out of the container)', 'geofolio'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'geofolio'),
                'label_off'    => __('No', 'geofolio'),
                'return_value' => 'yes',
                'default'      => '',
                'selectors'    => [
                    '{{WRAPPER}}' => 'width: 100vw; max-width: 100vw; margin-left: calc(50% - 50vw); margin-right: calc(50% - 50vw);',
                    '{{WRAPPER}} .gfo-map-container' => 'width: 100vw; max-width: 100vw; border-radius: 0;',
                ],
                'description'  => __('Makes the widget span the full window width, even when the parent section is constrained (e.g. max-width 1200px).', 'geofolio'),
            ]
        );

        $this->add_control(
            'center_lat',
            [
                'label'       => __('Centre latitude', 'geofolio'),
                'type'        => \Elementor\Controls_Manager::NUMBER,
                'default'     => (float) $defaults['center_lat'],
                'step'        => 0.0001,
                'description' => __('Used when "Fit the view to the places" is off, or when the map contains no place.', 'geofolio'),
            ]
        );

        $this->add_control(
            'center_lng',
            [
                'label'       => __('Centre longitude', 'geofolio'),
                'type'        => \Elementor\Controls_Manager::NUMBER,
                'default'     => (float) $defaults['center_lng'],
                'step'        => 0.0001,
                'description' => __('Used when "Fit the view to the places" is off, or when the map contains no place.', 'geofolio'),
            ]
        );

        $this->add_control(
            'zoom',
            [
                'label'   => __('Initial zoom', 'geofolio'),
                'type'    => \Elementor\Controls_Manager::SLIDER,
                'range'   => [
                    'px' => ['min' => 1, 'max' => 18, 'step' => 1],
                ],
                'default' => ['size' => (int) $defaults['zoom']],
            ]
        );

        $this->add_control(
            'fit_bounds',
            [
                'label'        => __('Fit the view to the places', 'geofolio'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'geofolio'),
                'label_off'    => __('No', 'geofolio'),
                'return_value' => 'true',
                'default'      => self::switcher_default($defaults['fit_bounds']),
                'description'  => __('On: the map zooms to show every place, and again after each filter. Off: it keeps the centre and zoom above.', 'geofolio'),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Section: Display Options
     */
    private function register_section_display_options(): void {
        $defaults = Defaults::all();
        $this->start_controls_section(
            'section_display_options',
            [
                'label' => __('Display options', 'geofolio'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_search',
            [
                'label'        => __('Show the search', 'geofolio'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'geofolio'),
                'label_off'    => __('No', 'geofolio'),
                'return_value' => 'true',
                'default'      => self::switcher_default($defaults['show_search']),
            ]
        );

        $this->add_control(
            'show_filter',
            [
                'label'        => __('Show the type filter', 'geofolio'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'geofolio'),
                'label_off'    => __('No', 'geofolio'),
                'return_value' => 'true',
                'default'      => self::switcher_default($defaults['show_filter']),
            ]
        );

        $this->add_control(
            'show_list',
            [
                'label'        => __('Show the results list', 'geofolio'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'geofolio'),
                'label_off'    => __('No', 'geofolio'),
                'return_value' => 'true',
                'default'      => self::switcher_default($defaults['show_list']),
            ]
        );

        $this->add_control(
            'show_fullscreen',
            [
                'label'        => __('Full screen button', 'geofolio'),
                'type'         => \Elementor\Controls_Manager::SWITCHER,
                'label_on'     => __('Yes', 'geofolio'),
                'label_off'    => __('No', 'geofolio'),
                'return_value' => 'true',
                'default'      => self::switcher_default($defaults['show_fullscreen']),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Section: Sidebar Content
     */
    private function register_section_sidebar_content(): void {
        $defaults = Defaults::all();
        $this->start_controls_section(
            'section_sidebar_content',
            [
                'label' => __('Sidebar content', 'geofolio'),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'sidebar_position',
            [
                'label'   => __('Sidebar position', 'geofolio'),
                'type'    => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'left'  => __('Left', 'geofolio'),
                    'right' => __('Right', 'geofolio'),
                ],
                'default' => $defaults['sidebar_position'],
            ]
        );

        $this->add_control(
            'sidebar_title',
            [
                'label'   => __('Sidebar title', 'geofolio'),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => $defaults['sidebar_title'],
                'label_block' => true,
            ]
        );

        $this->add_control(
            'sidebar_subtitle',
            [
                'label'   => __('Sidebar subtitle', 'geofolio'),
                'type'    => \Elementor\Controls_Manager::TEXTAREA,
                'default' => $defaults['sidebar_subtitle'],
                'rows'    => 3,
            ]
        );

        // Les fonds disponibles viennent du registre PHP (source unique de
        // verite, cf. includes/class-tile-providers.php) : un fond ajoute la-bas
        // apparait automatiquement ici, avec sa mention « cle requise ».
        $this->add_control(
            'tile_style',
            [
                'label'       => __('Tile style', 'geofolio'),
                'type'        => \Elementor\Controls_Manager::SELECT,
                'options'     => array_merge(
                    ['' => __('— Use the site setting —', 'geofolio')],
                    TileProviders::labels()
                ),
                'default'     => '',
                'description' => __('The API key for “key required” basemaps is set in Places → Map settings.', 'geofolio'),
            ]
        );

        $this->end_controls_section();
    }

    // ==================================================================
    //  STYLE TAB
    // ==================================================================

    /**
     * Style Section 1: Layout
     */
    private function register_style_layout(): void {
        $this->start_controls_section(
            'style_layout',
            [
                'label' => __('Layout', 'geofolio'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'sidebar_width',
            [
                'label'      => __('Sidebar width', 'geofolio'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => ['min' => 200, 'max' => 500, 'step' => 5],
                    '%'  => ['min' => 15, 'max' => 50, 'step' => 1],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .gfo-map-container' => '--gfo-sidebar-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'container_padding',
            [
                'label'      => __('Container padding', 'geofolio'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .gfo-map-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'container_border_radius',
            [
                'label'      => __('Container radius', 'geofolio'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .gfo-map-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'container_shadow',
                'label'    => __('Container shadow', 'geofolio'),
                'selector' => '{{WRAPPER}} .gfo-map-container',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style Section 2: Sidebar
     */
    private function register_style_sidebar(): void {
        $this->start_controls_section(
            'style_sidebar',
            [
                'label' => __('Sidebar', 'geofolio'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'sidebar_bg',
            [
                'label'     => __('Sidebar background', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-map-sidebar' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'sidebar_border_color',
            [
                'label'     => __('Sidebar border colour', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-map-sidebar' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'header_bg',
            [
                'label'     => __('Sidebar header background', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-sidebar-header' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style Section 3: Search Bar
     */
    private function register_style_search_bar(): void {
        $this->start_controls_section(
            'style_search_bar',
            [
                'label' => __('Search bar', 'geofolio'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'search_bg',
            [
                'label'     => __('Search field background', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-search-input' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'search_border',
                'label'    => __('Field border', 'geofolio'),
                'selector' => '{{WRAPPER}} .gfo-search-input',
            ]
        );

        $this->add_control(
            'search_radius',
            [
                'label'     => __('Radius', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::SLIDER,
                'range'     => [
                    'px' => ['min' => 0, 'max' => 30, 'step' => 1],
                ],
                'selectors' => [
                    '{{WRAPPER}} .gfo-search-input, {{WRAPPER}} .gfo-search-btn' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'search_typography',
                'label'    => __('Field typography', 'geofolio'),
                'selector' => '{{WRAPPER}} .gfo-search-input',
            ]
        );

        $this->add_control(
            'heading_search_btn',
            [
                'label'     => __('Search button', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->start_controls_tabs('search_btn_tabs');

        /* --- Onglet Normal --- */
        $this->start_controls_tab(
            'search_btn_tab_normal',
            ['label' => __('Normal', 'geofolio')]
        );

        $this->add_control(
            'search_btn_bg',
            [
                'label'     => __('Background', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-search-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'search_btn_icon_color',
            [
                'label'     => __('Pictogram colour', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'description' => __('Colour of the search icon (magnifier).', 'geofolio'),
                'selectors' => [
                    // L'icône est tracée avec stroke="currentColor" → on pilote via color.
                    '{{WRAPPER}} .gfo-search-btn'     => 'color: {{VALUE}};',
                    '{{WRAPPER}} .gfo-search-btn svg' => 'stroke: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'search_btn_border',
                'label'    => __('Border', 'geofolio'),
                'selector' => '{{WRAPPER}} .gfo-search-btn',
            ]
        );

        $this->end_controls_tab();

        /* --- Onglet Survol --- */
        $this->start_controls_tab(
            'search_btn_tab_hover',
            ['label' => __('Hover', 'geofolio')]
        );

        $this->add_control(
            'search_btn_hover_bg',
            [
                'label'     => __('Background', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-search-btn:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'search_btn_icon_color_hover',
            [
                'label'     => __('Pictogram colour', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-search-btn:hover'     => 'color: {{VALUE}};',
                    '{{WRAPPER}} .gfo-search-btn:hover svg' => 'stroke: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'search_btn_border_hover',
                'label'    => __('Border', 'geofolio'),
                'selector' => '{{WRAPPER}} .gfo-search-btn:hover',
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    /**
     * Style Section 4: Filter Dropdown
     */
    private function register_style_filter_dropdown(): void {
        $this->start_controls_section(
            'style_filter_dropdown',
            [
                'label' => __('Dropdown filter', 'geofolio'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'filter_bg',
            [
                'label'     => __('Select background', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-filter-select' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'filter_border',
                'label'    => __('Select border', 'geofolio'),
                'selector' => '{{WRAPPER}} .gfo-filter-select',
            ]
        );

        $this->add_control(
            'filter_radius',
            [
                'label'     => __('Select radius', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::SLIDER,
                'range'     => [
                    'px' => ['min' => 0, 'max' => 30, 'step' => 1],
                ],
                'selectors' => [
                    '{{WRAPPER}} .gfo-filter-select' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'filter_typography',
                'label'    => __('Select typography', 'geofolio'),
                'selector' => '{{WRAPPER}} .gfo-filter-select',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style Section 5: Establishment Cards
     */
    private function register_style_establishment_cards(): void {
        $this->start_controls_section(
            'style_establishment_cards',
            [
                'label' => __('Place cards', 'geofolio'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'card_bg',
            [
                'label'     => __('Card background', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-place-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'card_hover_bg',
            [
                'label'     => __('Hover background', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-place-card:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'card_active_bg',
            [
                'label'     => __('Active card background', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-place-card.active' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'card_border',
                'label'    => __('Card border', 'geofolio'),
                'selector' => '{{WRAPPER}} .gfo-place-card',
            ]
        );

        $this->add_responsive_control(
            'card_padding',
            [
                'label'      => __('Card padding', 'geofolio'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .gfo-place-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'card_spacing',
            [
                'label'     => __('Spacing between cards', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::SLIDER,
                'range'     => [
                    'px' => ['min' => 0, 'max' => 30, 'step' => 1],
                ],
                'selectors' => [
                    '{{WRAPPER}} .gfo-place-card' => 'margin-bottom: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style Section 6: Type Badge
     */
    private function register_style_type_badge(): void {
        $this->start_controls_section(
            'style_type_badge',
            [
                'label' => __('Type badge', 'geofolio'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'badge_radius',
            [
                'label'     => __('Badge radius', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::SLIDER,
                'range'     => [
                    'px' => ['min' => 0, 'max' => 20, 'step' => 1],
                ],
                'selectors' => [
                    '{{WRAPPER}} .gfo-place-type' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'badge_typography',
                'label'    => __('Badge typography', 'geofolio'),
                'selector' => '{{WRAPPER}} .gfo-place-type',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style Section 7: Card Title
     */
    private function register_style_card_title(): void {
        $this->start_controls_section(
            'style_card_title',
            [
                'label' => __('Card title', 'geofolio'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'title_typography',
                'label'    => __('Title typography', 'geofolio'),
                'selector' => '{{WRAPPER}} .gfo-place-name',
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label'     => __('Title colour', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-place-name' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style Section 8: Card Meta
     */
    private function register_style_card_meta(): void {
        $this->start_controls_section(
            'style_card_meta',
            [
                'label' => __('Card meta', 'geofolio'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'meta_typography',
                'label'    => __('Meta typography', 'geofolio'),
                'selector' => '{{WRAPPER}} .gfo-place-city',
            ]
        );

        $this->add_control(
            'meta_color',
            [
                'label'     => __('Meta colour', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-place-city' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style Section 9: Phone Link
     */
    private function register_style_phone_link(): void {
        $this->start_controls_section(
            'style_phone_link',
            [
                'label' => __('Phone link', 'geofolio'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'phone_color',
            [
                'label'     => __('Link colour', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-place-phone' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'phone_hover_color',
            [
                'label'     => __('Hover colour', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-place-phone:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'phone_typography',
                'label'    => __('Phone typography', 'geofolio'),
                'selector' => '{{WRAPPER}} .gfo-place-phone',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style Section 10: Map & Colors
     */
    private function register_style_map_colors(): void {
        $this->start_controls_section(
            'style_map_colors',
            [
                'label' => __('Map & colours', 'geofolio'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'primary_color',
            [
                'label'     => __('Primary colour', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'description' => __('Affects clusters, buttons and links. Markers take the colour of their entity.', 'geofolio'),
                'selectors' => [
                    '{{WRAPPER}}' => '--gfo-primary: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'accent_color',
            [
                'label'     => __('Accent colour', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'description' => __('Affects links and the phone number', 'geofolio'),
                'selectors' => [
                    '{{WRAPPER}}' => '--gfo-accent: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style Section 11: Clusters
     */
    private function register_style_clusters(): void {
        $this->start_controls_section(
            'style_clusters',
            [
                'label' => __('Clusters', 'geofolio'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'cluster_bg',
            [
                'label'     => __('Cluster background', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--gfo-cluster-bg: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'cluster_border_color',
            [
                'label'     => __('Cluster border', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--gfo-cluster-border: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'cluster_text_color',
            [
                'label'     => __('Cluster text', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}}' => '--gfo-cluster-text: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style Section 12: Popup
     */
    private function register_style_popup(): void {
        $this->start_controls_section(
            'style_popup',
            [
                'label' => __('Popup', 'geofolio'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'popup_bg',
            [
                'label'     => __('Popup background', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-popup .leaflet-popup-content-wrapper' => 'background: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'popup_radius',
            [
                'label'     => __('Popup radius', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::SLIDER,
                'range'     => [
                    'px' => ['min' => 0, 'max' => 30, 'step' => 1],
                ],
                'selectors' => [
                    '{{WRAPPER}} .gfo-popup .leaflet-popup-content-wrapper' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'popup_shadow',
                'label'    => __('Popup shadow', 'geofolio'),
                'selector' => '{{WRAPPER}} .gfo-popup .leaflet-popup-content-wrapper',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'      => 'popup_title_typography',
                'label'     => __('Popup title typography', 'geofolio'),
                'selector'  => '{{WRAPPER}} .gfo-popup-title',
                'separator' => 'before',
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style Section 13: Results Header
     */
    private function register_style_results_header(): void {
        $this->start_controls_section(
            'style_results_header',
            [
                'label' => __('Results header', 'geofolio'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'results_bg',
            [
                'label'     => __('Header background', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-results-header' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'results_typography',
                'label'    => __('Header typography', 'geofolio'),
                'selector' => '{{WRAPPER}} .gfo-results-header',
            ]
        );

        $this->add_control(
            'count_bg',
            [
                'label'     => __('Counter background', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-results-count' => 'background-color: {{VALUE}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'count_color',
            [
                'label'     => __('Counter colour', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-results-count' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Style Section 14: Fullscreen Button
     */
    private function register_style_fullscreen_btn(): void {
        $this->start_controls_section(
            'style_fullscreen_btn',
            [
                'label' => __('Full screen button', 'geofolio'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'fullscreen_btn_bg',
            [
                'label'     => __('Button background', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-fullscreen-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'fullscreen_btn_color',
            [
                'label'     => __('Icon colour', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-fullscreen-btn' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'fullscreen_btn_hover_bg',
            [
                'label'     => __('Hover background', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-fullscreen-btn:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'fullscreen_btn_radius',
            [
                'label'     => __('Button radius', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::SLIDER,
                'range'     => [
                    'px' => ['min' => 0, 'max' => 30, 'step' => 1],
                ],
                'selectors' => [
                    '{{WRAPPER}} .gfo-fullscreen-btn' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
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
