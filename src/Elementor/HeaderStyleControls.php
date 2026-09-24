<?php
/**
 * Contrôles de style du widget : titre et sous-titre de la sidebar,
 * pastilles d'entités et phrase d'aide.
 *
 * @package Geofolio
 */

namespace Geofolio\Elementor;

if (!defined('ABSPATH')) {
    exit;
}

trait HeaderStyleControls {

    /**
     * Section « Titre et sous-titre ».
     */
    private function register_style_sidebar_heading(): void {
        $this->start_controls_section(
            'style_sidebar_heading',
            [
                'label' => __('Sidebar title and subtitle', 'geofolio'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'sidebar_title_typography',
                'label'    => __('Title typography', 'geofolio'),
                'selector' => '{{WRAPPER}} .gfo-sidebar-title',
            ]
        );

        $this->add_control(
            'sidebar_title_color',
            [
                'label'     => __('Title colour', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-sidebar-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'sidebar_subtitle_typography',
                'label'    => __('Subtitle typography', 'geofolio'),
                'selector' => '{{WRAPPER}} .gfo-sidebar-subtitle',
            ]
        );

        $this->add_control(
            'sidebar_subtitle_color',
            [
                'label'     => __('Subtitle colour', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .gfo-sidebar-subtitle' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Section « Pastilles d'entités ». La couleur de chaque pastille vient
     * de son entité : seuls la forme, le texte et la phrase d'aide se règlent.
     */
    private function register_style_entity_pills(): void {
        $this->start_controls_section(
            'style_entity_pills',
            [
                'label' => __('Entity pills', 'geofolio'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'pill_typography',
                'label'    => __('Pill typography', 'geofolio'),
                'selector' => '{{WRAPPER}} .gfo-entity-pill',
            ]
        );

        $this->add_control(
            'pill_radius',
            [
                'label'      => __('Pill border radius', 'geofolio'),
                'type'       => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range'      => ['px' => ['min' => 0, 'max' => 50]],
                // Variable : la règle du plugin est en !important (protection
                // contre les thèmes) et ignorerait une valeur directe.
                'selectors'  => [
                    '{{WRAPPER}} .gfo-map-container' => '--gfo-pill-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        // Liste plutôt qu'interrupteur : un interrupteur éteint n'émet aucun
        // CSS, la phrase ne pourrait donc jamais être masquée.
        $this->add_control(
            'entity_hint_display',
            [
                'label'     => __('Help sentence', 'geofolio'),
                'type'      => \Elementor\Controls_Manager::SELECT,
                'options'   => [
                    ''            => __('Default', 'geofolio'),
                    'inline-flex' => __('Show', 'geofolio'),
                    'none'        => __('Hide', 'geofolio'),
                ],
                'selectors' => [
                    '{{WRAPPER}} .gfo-entity-hint' => 'display: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }
}
