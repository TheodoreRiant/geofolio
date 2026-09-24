<?php
/**
 * Contrôles du widget Elementor Geofolio. Onglet Style : couleurs de la carte, clusters, popup, en-tête des résultats, bouton plein écran.
 *
 * Méthodes utilisées par MapWidget::register_controls().
 *
 * @package Geofolio
 */

namespace Geofolio\Elementor;

if (!defined('ABSPATH')) {
    exit;
}

trait MapStyleControls {

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
}
