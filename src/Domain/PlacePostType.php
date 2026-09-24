<?php
/**
 * Custom Post Type Établissement
 */

namespace Geofolio\Domain;

use Geofolio\Admin\LabelsSettings;

if (!defined('ABSPATH')) {
    exit;
}

class PlacePostType {

    /**
     * Icône du menu d'administration : la carte pliée et l'épingle de Geofolio
     * (brand/icon-mono.svg), en SVG encodé ; WordPress la colore selon le
     * thème d'administration.
     */
    const MENU_ICON = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyMCAyMCIgcm9sZT0iaW1nIiBhcmlhLWxhYmVsPSJHZW9mb2xpbyI+PHBhdGggZmlsbD0iYmxhY2siIGZpbGwtcnVsZT0iZXZlbm9kZCIgZD0iTTEuNSA1LjIgNiAzLjZsNCAxLjYgNC0xLjYgNC41IDEuNnYxMC42TDE0IDE3LjRsLTQtMS42LTQgMS42LTQuNS0xLjZWNS4yWm0xLjYgMS4xdjguMmwyLjEuOFY3LjFsLTIuMS0uOFptMTIuNy44djguMmwyLjEtLjhWNi4zbC0yLjEuOFoiLz48cGF0aCBmaWxsPSJibGFjayIgZD0iTTEwIDIuMmMtMi40IDAtNC4zIDEuOS00LjMgNC4zIDAgMy4zIDQuMyA3LjIgNC4zIDcuMnM0LjMtMy45IDQuMy03LjJjMC0yLjQtMS45LTQuMy00LjMtNC4zWm0wIDZhMS43IDEuNyAwIDEgMSAwLTMuNCAxLjcgMS43IDAgMCAxIDAgMy40WiIvPjwvc3ZnPg==';

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
     * geofolio_place_labels (« Établissements » plutôt que « Lieux »…).
     *
     * @return array<string, string>
     */
    public static function labels() {
        $labels = array(
            'name'                  => __('Places', 'geofolio'),
            'singular_name'         => __('Place', 'geofolio'),
            'menu_name'             => __('Places', 'geofolio'),
            'name_admin_bar'        => __('Place', 'geofolio'),
            'add_new'               => __('Add', 'geofolio'),
            'add_new_item'          => __('Add a place', 'geofolio'),
            'new_item'              => __('New place', 'geofolio'),
            'edit_item'             => __('Edit place', 'geofolio'),
            'view_item'             => __('View place', 'geofolio'),
            'all_items'             => __('All places', 'geofolio'),
            'search_items'          => __('Search places', 'geofolio'),
            'parent_item_colon'     => __('Parent place:', 'geofolio'),
            'not_found'             => __('No places found.', 'geofolio'),
            'not_found_in_trash'    => __('No places in the trash.', 'geofolio'),
            'featured_image'        => __('Place image', 'geofolio'),
            'set_featured_image'    => __('Set image', 'geofolio'),
            'remove_featured_image' => __('Remove image', 'geofolio'),
            'use_featured_image'    => __('Use as image', 'geofolio'),
        );
        $labels   = LabelsSettings::place_labels($labels);
        $filtered = apply_filters('geofolio_place_labels', $labels);
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
            'supports'           => apply_filters('geofolio_place_supports', array('title', 'thumbnail')),
            'show_in_rest'       => true,
        );

        register_post_type(Schema::POST_TYPE, $args);
    }
}
