<?php
/**
 * Contrôles du widget Elementor Geofolio. Onglet Style : mise en page, sidebar, barre de recherche, filtre par type.
 *
 * Méthodes utilisées par MapWidget::register_controls().
 *
 * @package Geofolio
 */

namespace Geofolio\Elementor;

if (!defined('ABSPATH')) {
    exit;
}

trait LayoutStyleControls {

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
}
