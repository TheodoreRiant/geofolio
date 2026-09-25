<?php
/**
 * Meta Boxes pour les établissements
 */

namespace MappedPlaces\Admin;

use MappedPlaces\Domain\FieldRegistry;
use MappedPlaces\Domain\People;
use MappedPlaces\Domain\Schema;

if (!defined('ABSPATH')) {
    exit;
}

class MetaBoxes {

    /**
     * Identifiant de la meta box Galerie.
     *
     * WordPress en fait l'id du conteneur `<div class="postbox">`. Il ne doit
     * donc JAMAIS coincider avec l'id d'un champ de formulaire : sinon
     * getElementById (et jQuery) renvoient le conteneur au lieu du champ, et
     * la valeur ecrite atterrit sur un <div> qui n'est jamais soumis.
     */
    const GALLERY_META_BOX_ID = 'mapped_places_gallery_box';

    /** Identifiants des meta boxes de la colonne principale, dans l'ordre. */
    const BOX_IDS = array('mapped_places_localisation', 'mapped_places_contact', 'mapped_places_direction', self::GALLERY_META_BOX_ID);

    /** Identifiant ET nom du champ cache qui porte les IDs de photos. */
    const GALLERY_FIELD_ID = 'mapped_places_gallery';

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_' . Schema::POST_TYPE, array($this, 'save_meta_boxes'), 10, 2);
    }

    /**
     * Ajouter les meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'mapped_places_localisation',
            __('Location', 'mapped-places'),
            array($this, 'render_localisation_box'),
            Schema::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'mapped_places_contact',
            __('Contact', 'mapped-places'),
            array($this, 'render_contact_box'),
            Schema::POST_TYPE,
            'normal',
            'default'
        );

        add_meta_box(
            'mapped_places_direction',
            __('Management', 'mapped-places'),
            array($this, 'render_direction_box'),
            Schema::POST_TYPE,
            'normal',
            'default'
        );

        add_meta_box(
            self::GALLERY_META_BOX_ID,
            __('Photo gallery', 'mapped-places'),
            array($this, 'render_gallery_box'),
            Schema::POST_TYPE,
            'normal',
            'default'
        );
    }

    /**
     * Afficher la meta box Localisation
     */
    public function render_localisation_box($post) {
        wp_nonce_field('mapped_places_save_meta', 'mapped_places_meta_nonce');

        $adresse = FieldRegistry::get($post->ID, 'address');
        $code_postal = FieldRegistry::get($post->ID, 'postal_code');
        $ville = FieldRegistry::get($post->ID, 'city');
        $latitude = FieldRegistry::get($post->ID, 'latitude');
        $longitude = FieldRegistry::get($post->ID, 'longitude');
        ?>
        <div class="mapl-meta-box">
            <p>
                <label for="mapped_places_address"><strong><?php esc_html_e('Address', 'mapped-places'); ?></strong></label>
                <input type="text" id="mapped_places_address" name="mapped_places_address" value="<?php echo esc_attr($adresse); ?>" class="widefat" />
            </p>

            <p class="mapl-row">
                <span class="mapl-col">
                    <label for="mapped_places_postal_code"><strong><?php esc_html_e('Postal code', 'mapped-places'); ?></strong></label>
                    <input type="text" id="mapped_places_postal_code" name="mapped_places_postal_code" value="<?php echo esc_attr($code_postal); ?>" />
                </span>
                <span class="mapl-col">
                    <label for="mapped_places_city"><strong><?php esc_html_e('City', 'mapped-places'); ?></strong></label>
                    <input type="text" id="mapped_places_city" name="mapped_places_city" value="<?php echo esc_attr($ville); ?>" />
                </span>
            </p>

            <p>
                <button type="button" id="mapped_places_geocode_btn" class="button">
                    <?php esc_html_e('Geocode address', 'mapped-places'); ?>
                </button>
                <span id="mapped_places_geocode_status"></span>
            </p>

            <p class="mapl-row">
                <span class="mapl-col">
                    <label for="mapped_places_latitude"><strong><?php esc_html_e('Latitude', 'mapped-places'); ?></strong></label>
                    <input type="text" id="mapped_places_latitude" name="mapped_places_latitude" value="<?php echo esc_attr($latitude); ?>" />
                </span>
                <span class="mapl-col">
                    <label for="mapped_places_longitude"><strong><?php esc_html_e('Longitude', 'mapped-places'); ?></strong></label>
                    <input type="text" id="mapped_places_longitude" name="mapped_places_longitude" value="<?php echo esc_attr($longitude); ?>" />
                </span>
            </p>

            <div id="mapped_places_admin_map" style="height: 300px; margin-top: 15px;"></div>
            <p class="description"><?php esc_html_e('Click on the map to place the marker, or use the "Geocode" button to find the coordinates automatically.', 'mapped-places'); ?></p>
        </div>
        <?php
    }

    /**
     * Afficher la meta box Contact
     */
    public function render_contact_box($post) {
        $telephone = FieldRegistry::get($post->ID, 'phone');
        $email = FieldRegistry::get($post->ID, 'email');
        $site_web = FieldRegistry::get($post->ID, 'website');
        $horaires = FieldRegistry::get($post->ID, 'opening_hours');
        ?>
        <div class="mapl-meta-box">
            <p class="mapl-row">
                <span class="mapl-col">
                    <label for="mapped_places_phone"><strong><?php esc_html_e('Phone', 'mapped-places'); ?></strong></label>
                    <input type="tel" id="mapped_places_phone" name="mapped_places_phone" value="<?php echo esc_attr($telephone); ?>" />
                </span>
                <span class="mapl-col">
                    <label for="mapped_places_email"><strong><?php esc_html_e('Email', 'mapped-places'); ?></strong></label>
                    <input type="email" id="mapped_places_email" name="mapped_places_email" value="<?php echo esc_attr($email); ?>" />
                </span>
            </p>

            <p>
                <label for="mapped_places_website"><strong><?php esc_html_e('Website', 'mapped-places'); ?></strong></label>
                <input type="url" id="mapped_places_website" name="mapped_places_website" value="<?php echo esc_url($site_web); ?>" class="widefat" placeholder="https://" />
            </p>

            <p>
                <label for="mapped_places_opening_hours"><strong><?php esc_html_e('Opening hours', 'mapped-places'); ?></strong></label>
                <textarea id="mapped_places_opening_hours" name="mapped_places_opening_hours" class="widefat" rows="4"><?php echo esc_textarea($horaires); ?></textarea>
            </p>
        </div>
        <?php
    }

    /**
     * Afficher la meta box Direction
     */
    public function render_direction_box($post) {
        $people = People::for_post($post->ID, People::default_role());
        ?>
        <div class="mapl-meta-box mapl-people" data-mapl-people>
            <input type="hidden"
                   id="mapped_places_people"
                   name="mapped_places_people"
                   value="<?php echo esc_attr(People::encode($people)); ?>" />
            <datalist id="mapped_places_people_roles">
                <?php foreach (People::role_suggestions() as $role) : ?>
                <option value="<?php echo esc_attr($role); ?>"></option>
                <?php endforeach; ?>
            </datalist>

            <div class="mapl-people-header" aria-hidden="true">
                <span></span>
                <span><?php esc_html_e('Role', 'mapped-places'); ?></span>
                <span><?php esc_html_e('Name', 'mapped-places'); ?></span>
                <span></span>
            </div>
            <div class="mapl-people-rows" id="mapped_places_people_rows">
                <?php foreach ($people as $person) : ?>
                    <?php self::render_person_row($person); ?>
                <?php endforeach; ?>
            </div>
            <template id="mapped_places_person_template"><?php self::render_person_row(array('role' => '', 'name' => '')); ?></template>

            <p>
                <button type="button" class="button" id="mapped_places_people_add">
                    <span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
                    <?php esc_html_e('Add a person', 'mapped-places'); ?>
                </button>
            </p>
            <p class="description"><?php esc_html_e('Role and name, in the order shown on the map. Drag a row to reorder.', 'mapped-places'); ?></p>
        </div>
        <?php
    }

    /**
     * Une ligne rôle + nom. Les champs n'ont pas d'attribut name : le script
     * admin recopie la liste dans le champ caché JSON `mapped_places_people`.
     *
     * @param array{role: string, name: string} $person
     */
    private static function render_person_row(array $person) {
        ?>
        <div class="mapl-person-row">
            <span class="mapl-person-handle dashicons dashicons-menu" title="<?php esc_attr_e('Drag to reorder', 'mapped-places'); ?>"></span>
            <input type="text" class="mapl-person-role" list="mapped_places_people_roles"
                   value="<?php echo esc_attr($person['role']); ?>"
                   placeholder="<?php esc_attr_e('Role', 'mapped-places'); ?>"
                   aria-label="<?php esc_attr_e('Role', 'mapped-places'); ?>" />
            <input type="text" class="mapl-person-name"
                   value="<?php echo esc_attr($person['name']); ?>"
                   placeholder="<?php esc_attr_e('First Last', 'mapped-places'); ?>"
                   aria-label="<?php esc_attr_e('Name', 'mapped-places'); ?>" />
            <button type="button" class="button-link mapl-person-remove" aria-label="<?php esc_attr_e('Remove this person', 'mapped-places'); ?>">
                <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
            </button>
        </div>
        <?php
    }

    /**
     * Afficher la meta box Galerie photos
     */
    public function render_gallery_box($post) {
        $ids = FieldRegistry::parse_gallery_ids(FieldRegistry::get($post->ID, 'gallery'));
        ?>
        <div class="mapl-meta-box">
            <input type="hidden"
                   id="<?php echo esc_attr(self::GALLERY_FIELD_ID); ?>"
                   name="<?php echo esc_attr(self::GALLERY_FIELD_ID); ?>"
                   value="<?php echo esc_attr(wp_json_encode($ids)); ?>" />

            <p>
                <button type="button" id="mapped_places_gallery_select" class="button">
                    <?php esc_html_e('Add / edit photos', 'mapped-places'); ?>
                </button>
                <button type="button" id="mapped_places_gallery_clear" class="button" <?php echo empty($ids) ? 'hidden' : ''; ?>>
                    <?php esc_html_e('Clear the gallery', 'mapped-places'); ?>
                </button>
            </p>

            <div id="mapped_places_gallery_preview" class="mapl-gallery-preview"></div>

            <p class="description">
                <?php esc_html_e('Drag and drop to reorder. First photo = cover.', 'mapped-places'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Sauvegarder les meta boxes
     */
    public function save_meta_boxes($post_id, $post) {
        // Vérifications de sécurité
        if (!isset($_POST['mapped_places_meta_nonce'])
            || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mapped_places_meta_nonce'])), 'mapped_places_save_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Même registre que l'API REST (Plugin::register_place_meta).
        foreach (FieldRegistry::fields() as $field) {
            $input = FieldRegistry::form_field($field);
            if (isset($_POST[$input])) {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- FieldRegistry::sanitize() applique le nettoyage propre à chaque champ (texte, e-mail, URL, texte multiligne).
                $value = FieldRegistry::sanitize($field, wp_unslash($_POST[$input]));
                // update_post_meta() retire une couche de barres obliques.
                update_post_meta($post_id, FieldRegistry::meta_key($field), wp_slash($value));
            }
        }

        // L'ancien champ « manager » (noms seuls) suit la liste des personnes
        // pour les consommateurs qui ne connaissent que lui.
        if (isset($_POST['mapped_places_people'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- People::parse() nettoie chaque rôle et chaque nom.
            $people = People::parse(wp_unslash($_POST['mapped_places_people']));
            update_post_meta($post_id, FieldRegistry::meta_key('manager'), wp_slash(People::names($people)));
        }
    }
}
