<?php
/**
 * Contrôles du widget Elementor Mapped Places. Onglet Style : mise en page, sidebar, barre de recherche, filtre par type.
 *
 * Méthodes utilisées par MapWidget::register_controls().
 *
 * @package Mapped Places
 */

namespace MappedPlaces\Elementor;

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
                'label' => __('Layout', 'mapped-places'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'sidebar_width',
            [
                'label'      => __('Sidebar width', 'mapped-places'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range'      => [
                    'px' => ['min' => 200, 'max' => 500, 'step' => 5],
                    '%'  => ['min' => 15, 'max' => 50, 'step' => 1],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .mapl-map-container' => '--mapl-sidebar-width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'container_padding',
            [
                'label'      => __('Container padding', 'mapped-places'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .mapl-map-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'container_border_radius',
            [
                'label'      => __('Container radius', 'mapped-places'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .mapl-map-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'container_shadow',
                'label'    => __('Container shadow', 'mapped-places'),
                'selector' => '{{WRAPPER}} .mapl-map-container',
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
                'label' => __('Sidebar', 'mapped-places'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'sidebar_bg',
            [
                'label'     => __('Sidebar background', 'mapped-places'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mapl-map-sidebar' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'sidebar_border_color',
            [
                'label'     => __('Sidebar border colour', 'mapped-places'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mapl-map-sidebar' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'header_bg',
            [
                'label'     => __('Sidebar header background', 'mapped-places'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mapl-sidebar-header' => 'background-color: {{VALUE}};',
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
                'label' => __('Search bar', 'mapped-places'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'search_bg',
            [
                'label'     => __('Search field background', 'mapped-places'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mapl-search-input' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'search_border',
                'label'    => __('Field border', 'mapped-places'),
                'selector' => '{{WRAPPER}} .mapl-search-input',
            ]
        );

        $this->add_control(
            'search_radius',
            [
                'label'     => __('Radius', 'mapped-places'),
                'type'      => \Elementor\Controls_Manager::SLIDER,
                'range'     => [
                    'px' => ['min' => 0, 'max' => 30, 'step' => 1],
                ],
                'selectors' => [
                    '{{WRAPPER}} .mapl-search-input, {{WRAPPER}} .mapl-search-btn' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'search_typography',
                'label'    => __('Field typography', 'mapped-places'),
                'selector' => '{{WRAPPER}} .mapl-search-input',
            ]
        );

        $this->add_control(
            'heading_search_btn',
            [
                'label'     => __('Search button', 'mapped-places'),
                'type'      => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->start_controls_tabs('search_btn_tabs');

        /* --- Onglet Normal --- */
        $this->start_controls_tab(
            'search_btn_tab_normal',
            ['label' => __('Normal', 'mapped-places')]
        );

        $this->add_control(
            'search_btn_bg',
            [
                'label'     => __('Background', 'mapped-places'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mapl-search-btn' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'search_btn_icon_color',
            [
                'label'     => __('Pictogram colour', 'mapped-places'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'description' => __('Colour of the search icon (magnifier).', 'mapped-places'),
                'selectors' => [
                    // L'icône est tracée avec stroke="currentColor" → on pilote via color.
                    '{{WRAPPER}} .mapl-search-btn'     => 'color: {{VALUE}};',
                    '{{WRAPPER}} .mapl-search-btn svg' => 'stroke: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'search_btn_border',
                'label'    => __('Border', 'mapped-places'),
                'selector' => '{{WRAPPER}} .mapl-search-btn',
            ]
        );

        $this->end_controls_tab();

        /* --- Onglet Survol --- */
        $this->start_controls_tab(
            'search_btn_tab_hover',
            ['label' => __('Hover', 'mapped-places')]
        );

        $this->add_control(
            'search_btn_hover_bg',
            [
                'label'     => __('Background', 'mapped-places'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mapl-search-btn:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'search_btn_icon_color_hover',
            [
                'label'     => __('Pictogram colour', 'mapped-places'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mapl-search-btn:hover'     => 'color: {{VALUE}};',
                    '{{WRAPPER}} .mapl-search-btn:hover svg' => 'stroke: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'search_btn_border_hover',
                'label'    => __('Border', 'mapped-places'),
                'selector' => '{{WRAPPER}} .mapl-search-btn:hover',
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
                'label' => __('Dropdown filter', 'mapped-places'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'filter_bg',
            [
                'label'     => __('Select background', 'mapped-places'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mapl-filter-select' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'filter_border',
                'label'    => __('Select border', 'mapped-places'),
                'selector' => '{{WRAPPER}} .mapl-filter-select',
            ]
        );

        $this->add_control(
            'filter_radius',
            [
                'label'     => __('Select radius', 'mapped-places'),
                'type'      => \Elementor\Controls_Manager::SLIDER,
                'range'     => [
                    'px' => ['min' => 0, 'max' => 30, 'step' => 1],
                ],
                'selectors' => [
                    '{{WRAPPER}} .mapl-filter-select' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'filter_typography',
                'label'    => __('Select typography', 'mapped-places'),
                'selector' => '{{WRAPPER}} .mapl-filter-select',
            ]
        );

        $this->end_controls_section();
    }
}
