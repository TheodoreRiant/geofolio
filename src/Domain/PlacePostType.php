<?php
/**
 * Custom Post Type Établissement
 */

namespace MappedPlaces\Domain;

use MappedPlaces\Admin\LabelsSettings;

if (!defined('ABSPATH')) {
    exit;
}

class PlacePostType {

    /**
     * Icône du menu d'administration : la carte pliée et l'épingle de Mapped Places
     * (brand/icon-mono.svg), en SVG encodé ; WordPress la colore selon le
     * thème d'administration.
     */
    const MENU_ICON = 'dashicons-location';

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_media'));
    }

    /**
     * Charger la médiathèque WordPress et jQuery UI Sortable
     * sur l'écran d'édition d'un établissement (utilisé par la meta box galerie).
     */
    public function enqueue_admin_media() {
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        $is_place_screen = false;

        if ($screen) {
            $is_place_screen = ($screen->id === Schema::POST_TYPE || $screen->post_type === Schema::POST_TYPE);
        } else {
            global $post_type;
            $is_place_screen = ($post_type === Schema::POST_TYPE);
        }

        if ($is_place_screen) {
            wp_enqueue_media();
            wp_enqueue_script('jquery-ui-sortable');
        }
    }

    /**
     * Libellés du type de contenu, adaptables par le filtre
     * mapped_places_place_labels (« Établissements » plutôt que « Lieux »…).
     *
     * @return array<string, string>
     */
    public static function labels() {
        $labels = array(
            'name'                  => __('Places', 'mapped-places'),
            'singular_name'         => __('Place', 'mapped-places'),
            'menu_name'             => __('Places', 'mapped-places'),
            'name_admin_bar'        => __('Place', 'mapped-places'),
            'add_new'               => __('Add', 'mapped-places'),
            'add_new_item'          => __('Add a place', 'mapped-places'),
            'new_item'              => __('New place', 'mapped-places'),
            'edit_item'             => __('Edit place', 'mapped-places'),
            'view_item'             => __('View place', 'mapped-places'),
            'all_items'             => __('All places', 'mapped-places'),
            'search_items'          => __('Search places', 'mapped-places'),
            'parent_item_colon'     => __('Parent place:', 'mapped-places'),
            'not_found'             => __('No places found.', 'mapped-places'),
            'not_found_in_trash'    => __('No places in the trash.', 'mapped-places'),
            'featured_image'        => __('Place image', 'mapped-places'),
            'set_featured_image'    => __('Set image', 'mapped-places'),
            'remove_featured_image' => __('Remove image', 'mapped-places'),
            'use_featured_image'    => __('Use as image', 'mapped-places'),
        );
        $labels   = LabelsSettings::place_labels($labels);
        $filtered = apply_filters('mapped_places_place_labels', $labels);
        return is_array($filtered) ? array_merge($labels, $filtered) : $labels;
    }

    /**
     * Enregistrer le type de contenu des lieux.
     */
    public function register_post_type() {
        $labels = self::labels();

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => Schema::place_slug()),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 20,
            'menu_icon'          => self::MENU_ICON,
            // Pas d'éditeur d'article : un lieu se renseigne dans un formulaire
            // (voir Admin\PlaceEditScreen). Un site qui veut une vraie page
            // rédigée par lieu peut réactiver « editor » par ce filtre.
            'supports'           => apply_filters('mapped_places_place_supports', array('title', 'thumbnail')),
            'show_in_rest'       => true,
        );

        register_post_type(Schema::POST_TYPE, $args);
    }
}
