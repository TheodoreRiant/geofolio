<?php
/**
 * Contrôles du widget Elementor Geofolio. Onglet Contenu : paramètres de la carte, options d'affichage, textes de la sidebar.
 *
 * Méthodes utilisées par MapWidget::register_controls().
 *
 * @package Geofolio
 */

namespace Geofolio\Elementor;

use Geofolio\Map\Defaults;
use Geofolio\Map\TileProviders;

if (!defined('ABSPATH')) {
    exit;
}

trait ContentControls {

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
}
