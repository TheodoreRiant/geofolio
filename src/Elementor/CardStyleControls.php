<?php
/**
 * Contrôles du widget Elementor Mapped Places. Onglet Style : fiches de la liste (fiche, badge de type, titre, informations, téléphone).
 *
 * Méthodes utilisées par MapWidget::register_controls().
 *
 * @package Mapped Places
 */

namespace MappedPlaces\Elementor;

if (!defined('ABSPATH')) {
    exit;
}

trait CardStyleControls {

    /**
     * Style Section 5: Establishment Cards
     */
    private function register_style_establishment_cards(): void {
        $this->start_controls_section(
            'style_establishment_cards',
            [
                'label' => __('Place cards', 'mapped-places'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'card_bg',
            [
                'label'     => __('Card background', 'mapped-places'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mapl-place-card' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'card_hover_bg',
            [
                'label'     => __('Hover background', 'mapped-places'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mapl-place-card:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'card_active_bg',
            [
                'label'     => __('Active card background', 'mapped-places'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mapl-place-card.active' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name'     => 'card_border',
                'label'    => __('Card border', 'mapped-places'),
                'selector' => '{{WRAPPER}} .mapl-place-card',
            ]
        );

        $this->add_responsive_control(
            'card_padding',
            [
                'label'      => __('Card padding', 'mapped-places'),
                'type'       => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'selectors'  => [
                    '{{WRAPPER}} .mapl-place-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'card_spacing',
            [
                'label'     => __('Spacing between cards', 'mapped-places'),
                'type'      => \Elementor\Controls_Manager::SLIDER,
                'range'     => [
                    'px' => ['min' => 0, 'max' => 30, 'step' => 1],
                ],
                'selectors' => [
                    '{{WRAPPER}} .mapl-place-card' => 'margin-bottom: {{SIZE}}{{UNIT}};',
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
                'label' => __('Type badge', 'mapped-places'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'badge_radius',
            [
                'label'     => __('Badge radius', 'mapped-places'),
                'type'      => \Elementor\Controls_Manager::SLIDER,
                'range'     => [
                    'px' => ['min' => 0, 'max' => 20, 'step' => 1],
                ],
                'selectors' => [
                    '{{WRAPPER}} .mapl-place-type' => 'border-radius: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'badge_typography',
                'label'    => __('Badge typography', 'mapped-places'),
                'selector' => '{{WRAPPER}} .mapl-place-type',
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
                'label' => __('Card title', 'mapped-places'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'title_typography',
                'label'    => __('Title typography', 'mapped-places'),
                'selector' => '{{WRAPPER}} .mapl-place-name',
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label'     => __('Title colour', 'mapped-places'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mapl-place-name' => 'color: {{VALUE}};',
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
                'label' => __('Card meta', 'mapped-places'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'meta_typography',
                'label'    => __('Meta typography', 'mapped-places'),
                'selector' => '{{WRAPPER}} .mapl-place-city',
            ]
        );

        $this->add_control(
            'meta_color',
            [
                'label'     => __('Meta colour', 'mapped-places'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mapl-place-city' => 'color: {{VALUE}};',
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
                'label' => __('Phone link', 'mapped-places'),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'phone_color',
            [
                'label'     => __('Link colour', 'mapped-places'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mapl-place-phone' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'phone_hover_color',
            [
                'label'     => __('Hover colour', 'mapped-places'),
                'type'      => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .mapl-place-phone:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name'     => 'phone_typography',
                'label'    => __('Phone typography', 'mapped-places'),
                'selector' => '{{WRAPPER}} .mapl-place-phone',
            ]
        );

        $this->end_controls_section();
    }
}
