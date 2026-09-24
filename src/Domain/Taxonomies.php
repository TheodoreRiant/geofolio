<?php
/**
 * Taxonomies pour les établissements
 */

namespace Geofolio\Domain;

use Geofolio\Map\Defaults;

use Geofolio\Admin\LabelsSettings;

if (!defined('ABSPATH')) {
    exit;
}

class Taxonomies {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'register_taxonomies'));

        // Entite color picker hooks
        add_action(Schema::TAX_ENTITY . '_add_form_fields', array($this, 'entity_add_form_fields'));
        add_action(Schema::TAX_ENTITY . '_edit_form_fields', array($this, 'entity_edit_form_fields'));
        add_action('created_' . Schema::TAX_ENTITY, array($this, 'save_entity_color'));
        add_action('edited_' . Schema::TAX_ENTITY, array($this, 'save_entity_color'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_color_picker'));

        // Icône des types de lieux
        add_action(Icons::TAXONOMY . '_add_form_fields', array($this, 'type_add_form_fields'));
        add_action(Icons::TAXONOMY . '_edit_form_fields', array($this, 'type_edit_form_fields'));
        add_action('created_' . Icons::TAXONOMY, array($this, 'save_type_icon'));
        add_action('edited_' . Icons::TAXONOMY, array($this, 'save_type_icon'));
    }

    /**
     * Enregistrer les taxonomies
     */
    public function register_taxonomies() {
        // Type d'établissement
        $this->register_taxonomy(
            Schema::TAX_TYPE,
            __('Place types', 'geofolio'),
            __('Place type', 'geofolio'),
            Schema::taxonomy_slug(Schema::TAX_TYPE)
        );

        // Région
        $this->register_taxonomy(
            Schema::TAX_REGION,
            __('Regions', 'geofolio'),
            __('Region', 'geofolio'),
            Schema::taxonomy_slug(Schema::TAX_REGION)
        );

        // Services
        $this->register_taxonomy(
            Schema::TAX_SERVICE,
            __('Services', 'geofolio'),
            __('Service', 'geofolio'),
            Schema::taxonomy_slug(Schema::TAX_SERVICE)
        );

        // Accessibilité
        $this->register_taxonomy(
            Schema::TAX_ACCESSIBILITY,
            __('Accessibility', 'geofolio'),
            __('Accessibility', 'geofolio'),
            Schema::taxonomy_slug(Schema::TAX_ACCESSIBILITY)
        );

        // Entité (branche organisationnelle avec couleur)
        register_taxonomy(Schema::TAX_ENTITY, Schema::POST_TYPE, array(
            'labels' => LabelsSettings::entity_labels(array(
                'name'              => __('Entities', 'geofolio'),
                'singular_name'     => __('Entity', 'geofolio'),
                'search_items'      => __('Search entities', 'geofolio'),
                'all_items'         => __('All entities', 'geofolio'),
                'edit_item'         => __('Edit entity', 'geofolio'),
                'update_item'       => __('Update entity', 'geofolio'),
                'add_new_item'      => __('Add an entity', 'geofolio'),
                'new_item_name'     => __('New entity name', 'geofolio'),
                'menu_name'         => __('Entities', 'geofolio'),
            )),
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => Schema::taxonomy_slug(Schema::TAX_ENTITY)),
        ));
    }

    /**
     * Helper pour enregistrer une taxonomie
     */
    private function register_taxonomy($taxonomy, $plural, $singular, $slug) {
        $labels = array(
            'name'              => $plural,
            'singular_name'     => $singular,
            'search_items'      => sprintf(/* translators: %s: taxonomy name */ __('Search %s', 'geofolio'), $plural),
            'all_items'         => sprintf(/* translators: %s: taxonomy name */ __('All %s', 'geofolio'), $plural),
            'parent_item'       => sprintf(/* translators: %s: taxonomy name */ __('Parent %s', 'geofolio'), $singular),
            'parent_item_colon' => sprintf(/* translators: %s: taxonomy name */ __('Parent %s:', 'geofolio'), $singular),
            'edit_item'         => sprintf(/* translators: %s: taxonomy name */ __('Edit %s', 'geofolio'), $singular),
            'update_item'       => sprintf(/* translators: %s: taxonomy name */ __('Update %s', 'geofolio'), $singular),
            'add_new_item'      => sprintf(/* translators: %s: taxonomy name */ __('Add %s', 'geofolio'), $singular),
            'new_item_name'     => sprintf(/* translators: %s: taxonomy name */ __('New %s', 'geofolio'), $singular),
            'menu_name'         => $plural,
        );

        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => $slug),
            'show_in_rest'      => true,
        );

        register_taxonomy($taxonomy, array(Schema::POST_TYPE), $args);
    }

    /**
     * Afficher le champ couleur sur le formulaire d'ajout d'entité
     */
    public function entity_add_form_fields() {
        ?>
        <div class="form-field">
            <?php wp_nonce_field('geofolio_entity_color', 'geofolio_entity_color_nonce'); ?>
            <label for="entity_color"><?php esc_html_e('Colour', 'geofolio'); ?></label>
            <input type="text" name="entity_color" id="entity_color" value="<?php echo esc_attr(Defaults::color()); ?>" class="gfo-color-picker" />
            <p class="description"><?php esc_html_e('Colour used for markers and cards on the interactive map.', 'geofolio'); ?></p>
        </div>
        <?php
    }

    /**
     * Afficher le champ couleur sur le formulaire d'édition d'entité
     */
    public function entity_edit_form_fields($term) {
        $color = get_term_meta($term->term_id, Schema::ENTITY_COLOR_META, true);
        if (!$color) {
            $color = Defaults::color();
        }
        ?>
        <tr class="form-field">
            <th scope="row"><label for="entity_color"><?php esc_html_e('Colour', 'geofolio'); ?></label></th>
            <td>
                <?php wp_nonce_field('geofolio_entity_color', 'geofolio_entity_color_nonce'); ?>
                <input type="text" name="entity_color" id="entity_color" value="<?php echo esc_attr($color); ?>" class="gfo-color-picker" />
                <p class="description"><?php esc_html_e('Colour used for markers and cards on the interactive map.', 'geofolio'); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Sauvegarder la couleur de l'entité
     */
    public function save_entity_color($term_id) {
        if (!isset($_POST['entity_color'], $_POST['geofolio_entity_color_nonce'])
            || !wp_verify_nonce(sanitize_key(wp_unslash($_POST['geofolio_entity_color_nonce'])), 'geofolio_entity_color')
            || !current_user_can('manage_categories')) {
            return;
        }
        $color = sanitize_hex_color(sanitize_text_field(wp_unslash($_POST['entity_color'])));
        update_term_meta($term_id, Schema::ENTITY_COLOR_META, (string) $color);
    }

    /**
     * Choix de l'icône sur le formulaire d'ajout de type.
     */
    public function type_add_form_fields() {
        ?>
        <div class="form-field">
            <span class="gfo-icon-picker-label"><?php esc_html_e('Icon', 'geofolio'); ?></span>
            <?php self::render_icon_picker(''); ?>
            <p class="description"><?php esc_html_e('Icon shown in the list of places. Without a choice, a preset may suggest one; otherwise, a pin.', 'geofolio'); ?></p>
        </div>
        <?php
    }

    /**
     * Choix de l'icône sur le formulaire d'édition de type.
     *
     * @param \WP_Term $term
     */
    public function type_edit_form_fields($term) {
        $icon = get_term_meta($term->term_id, Icons::TERM_META, true);
        ?>
        <tr class="form-field">
            <th scope="row"><?php esc_html_e('Icon', 'geofolio'); ?></th>
            <td>
                <?php self::render_icon_picker(is_string($icon) ? $icon : ''); ?>
                <p class="description"><?php esc_html_e('Icon shown in the list of places. Without a choice, a preset may suggest one; otherwise, a pin.', 'geofolio'); ?></p>
            </td>
        </tr>
        <?php
    }

    /**
     * Boutons radio avec aperçu de chaque icône, plus « aucune ».
     *
     * @param string $current Clé enregistrée ('' = aucune).
     */
    private static function render_icon_picker($current) {
        wp_nonce_field('geofolio_type_icon', 'geofolio_type_icon_nonce');
        ?>
        <fieldset class="gfo-icon-picker">
            <label class="gfo-icon-choice">
                <input type="radio" name="geofolio_type_icon" value="" <?php checked($current, ''); ?> />
                <span><?php esc_html_e('Automatic', 'geofolio'); ?></span>
            </label>
            <?php foreach (Icons::all() as $key => $path) : ?>
            <label class="gfo-icon-choice" title="<?php echo esc_attr($key); ?>">
                <input type="radio" name="geofolio_type_icon" value="<?php echo esc_attr($key); ?>" <?php checked($current, $key); ?> />
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?php echo wp_kses($path, Icons::ALLOWED_SHAPES); ?></svg>
                <span class="screen-reader-text"><?php echo esc_html($key); ?></span>
            </label>
            <?php endforeach; ?>
        </fieldset>
        <?php
    }

    /**
     * Enregistrer l'icône choisie dans le formulaire de type.
     *
     * @param int $term_id
     */
    public function save_type_icon($term_id) {
        if (!isset($_POST['geofolio_type_icon'], $_POST['geofolio_type_icon_nonce'])
            || !wp_verify_nonce(sanitize_key(wp_unslash($_POST['geofolio_type_icon_nonce'])), 'geofolio_type_icon')
            || !current_user_can('manage_categories')) {
            return;
        }
        self::store_type_icon($term_id, sanitize_key(wp_unslash($_POST['geofolio_type_icon'])));
    }

    /**
     * Enregistrer une clé d'icône connue, ou effacer la meta.
     *
     * @param int    $term_id
     * @param string $icon
     */
    public static function store_type_icon($term_id, $icon) {
        if (Icons::is_valid($icon)) {
            update_term_meta($term_id, Icons::TERM_META, $icon);
            return;
        }
        delete_term_meta($term_id, Icons::TERM_META);
    }

    /**
     * Charger le color picker WordPress sur les pages d'administration de la taxonomie entite
     */
    public function enqueue_color_picker($hook) {
        if ($hook === 'edit-tags.php' || $hook === 'term.php') {
            $screen = get_current_screen();
            if ($screen && $screen->taxonomy === Schema::TAX_ENTITY) {
                wp_enqueue_style('wp-color-picker');
                wp_enqueue_script('wp-color-picker');
                wp_add_inline_script('wp-color-picker', "
                    jQuery(document).ready(function($) {
                        $('.gfo-color-picker').wpColorPicker();
                    });
                ");
            }
        }
    }

    /**
     * Récupérer les termes d'une taxonomie pour les filtres
     */
    public static function get_filter_terms($taxonomy) {
        $terms = get_terms(array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => true,
        ));

        if (is_wp_error($terms)) {
            return array();
        }

        return array_map(function($term) {
            return array(
                'id'    => $term->term_id,
                'slug'  => $term->slug,
                'name'  => $term->name,
                'count' => $term->count,
            );
        }, $terms);
    }
}
