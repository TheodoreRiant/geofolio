<?php
/**
 * Meta Boxes pour les établissements
 */

namespace Geofolio\Admin;

use Geofolio\Domain\FieldRegistry;
use Geofolio\Domain\People;
use Geofolio\Domain\Schema;

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
    const GALLERY_META_BOX_ID = 'geofolio_gallery_box';

    /** Identifiants des meta boxes de la colonne principale, dans l'ordre. */
    const BOX_IDS = array('geofolio_localisation', 'geofolio_contact', 'geofolio_direction', self::GALLERY_META_BOX_ID);

    /** Identifiant ET nom du champ cache qui porte les IDs de photos. */
    const GALLERY_FIELD_ID = 'geofolio_gallery';

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
            'geofolio_localisation',
            __('Location', 'geofolio'),
            array($this, 'render_localisation_box'),
            Schema::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'geofolio_contact',
            __('Contact', 'geofolio'),
            array($this, 'render_contact_box'),
            Schema::POST_TYPE,
            'normal',
            'default'
        );

        add_meta_box(
            'geofolio_direction',
            __('Management', 'geofolio'),
            array($this, 'render_direction_box'),
            Schema::POST_TYPE,
            'normal',
            'default'
        );

        add_meta_box(
            self::GALLERY_META_BOX_ID,
            __('Photo gallery', 'geofolio'),
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
        wp_nonce_field('geofolio_save_meta', 'geofolio_meta_nonce');

        $adresse = FieldRegistry::get($post->ID, 'address');
        $code_postal = FieldRegistry::get($post->ID, 'postal_code');
        $ville = FieldRegistry::get($post->ID, 'city');
        $latitude = FieldRegistry::get($post->ID, 'latitude');
        $longitude = FieldRegistry::get($post->ID, 'longitude');
        ?>
        <div class="gfo-meta-box">
            <p>
                <label for="geofolio_address"><strong><?php esc_html_e('Address', 'geofolio'); ?></strong></label>
                <input type="text" id="geofolio_address" name="geofolio_address" value="<?php echo esc_attr($adresse); ?>" class="widefat" />
            </p>

            <p class="gfo-row">
                <span class="gfo-col">
                    <label for="geofolio_postal_code"><strong><?php esc_html_e('Postal code', 'geofolio'); ?></strong></label>
                    <input type="text" id="geofolio_postal_code" name="geofolio_postal_code" value="<?php echo esc_attr($code_postal); ?>" />
                </span>
                <span class="gfo-col">
                    <label for="geofolio_city"><strong><?php esc_html_e('City', 'geofolio'); ?></strong></label>
                    <input type="text" id="geofolio_city" name="geofolio_city" value="<?php echo esc_attr($ville); ?>" />
                </span>
            </p>

            <p>
                <button type="button" id="geofolio_geocode_btn" class="button">
                    <?php esc_html_e('Geocode address', 'geofolio'); ?>
                </button>
                <span id="geofolio_geocode_status"></span>
            </p>

            <p class="gfo-row">
                <span class="gfo-col">
                    <label for="geofolio_latitude"><strong><?php esc_html_e('Latitude', 'geofolio'); ?></strong></label>
                    <input type="text" id="geofolio_latitude" name="geofolio_latitude" value="<?php echo esc_attr($latitude); ?>" />
                </span>
                <span class="gfo-col">
                    <label for="geofolio_longitude"><strong><?php esc_html_e('Longitude', 'geofolio'); ?></strong></label>
                    <input type="text" id="geofolio_longitude" name="geofolio_longitude" value="<?php echo esc_attr($longitude); ?>" />
                </span>
            </p>

            <div id="geofolio_admin_map" style="height: 300px; margin-top: 15px;"></div>
            <p class="description"><?php esc_html_e('Click on the map to place the marker, or use the "Geocode" button to find the coordinates automatically.', 'geofolio'); ?></p>
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
        <div class="gfo-meta-box">
            <p class="gfo-row">
                <span class="gfo-col">
                    <label for="geofolio_phone"><strong><?php esc_html_e('Phone', 'geofolio'); ?></strong></label>
                    <input type="tel" id="geofolio_phone" name="geofolio_phone" value="<?php echo esc_attr($telephone); ?>" />
                </span>
                <span class="gfo-col">
                    <label for="geofolio_email"><strong><?php esc_html_e('Email', 'geofolio'); ?></strong></label>
                    <input type="email" id="geofolio_email" name="geofolio_email" value="<?php echo esc_attr($email); ?>" />
                </span>
            </p>

            <p>
                <label for="geofolio_website"><strong><?php esc_html_e('Website', 'geofolio'); ?></strong></label>
                <input type="url" id="geofolio_website" name="geofolio_website" value="<?php echo esc_url($site_web); ?>" class="widefat" placeholder="https://" />
            </p>

            <p>
                <label for="geofolio_opening_hours"><strong><?php esc_html_e('Opening hours', 'geofolio'); ?></strong></label>
                <textarea id="geofolio_opening_hours" name="geofolio_opening_hours" class="widefat" rows="4"><?php echo esc_textarea($horaires); ?></textarea>
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
        <div class="gfo-meta-box gfo-people" data-gfo-people>
            <input type="hidden"
                   id="geofolio_people"
                   name="geofolio_people"
                   value="<?php echo esc_attr(People::encode($people)); ?>" />
            <datalist id="geofolio_people_roles">
                <?php foreach (People::role_suggestions() as $role) : ?>
                <option value="<?php echo esc_attr($role); ?>"></option>
                <?php endforeach; ?>
            </datalist>

            <div class="gfo-people-header" aria-hidden="true">
                <span></span>
                <span><?php esc_html_e('Role', 'geofolio'); ?></span>
                <span><?php esc_html_e('Name', 'geofolio'); ?></span>
                <span></span>
            </div>
            <div class="gfo-people-rows" id="geofolio_people_rows">
                <?php foreach ($people as $person) : ?>
                    <?php self::render_person_row($person); ?>
                <?php endforeach; ?>
            </div>
            <template id="geofolio_person_template"><?php self::render_person_row(array('role' => '', 'name' => '')); ?></template>

            <p>
                <button type="button" class="button" id="geofolio_people_add">
                    <span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span>
                    <?php esc_html_e('Add a person', 'geofolio'); ?>
                </button>
            </p>
            <p class="description"><?php esc_html_e('Role and name, in the order shown on the map. Drag a row to reorder.', 'geofolio'); ?></p>
        </div>
        <?php
    }

    /**
     * Une ligne rôle + nom. Les champs n'ont pas d'attribut name : le script
     * admin recopie la liste dans le champ caché JSON `geofolio_people`.
     *
     * @param array{role: string, name: string} $person
     */
    private static function render_person_row(array $person) {
        ?>
        <div class="gfo-person-row">
            <span class="gfo-person-handle dashicons dashicons-menu" title="<?php esc_attr_e('Drag to reorder', 'geofolio'); ?>"></span>
            <input type="text" class="gfo-person-role" list="geofolio_people_roles"
                   value="<?php echo esc_attr($person['role']); ?>"
                   placeholder="<?php esc_attr_e('Role', 'geofolio'); ?>"
                   aria-label="<?php esc_attr_e('Role', 'geofolio'); ?>" />
            <input type="text" class="gfo-person-name"
                   value="<?php echo esc_attr($person['name']); ?>"
                   placeholder="<?php esc_attr_e('First Last', 'geofolio'); ?>"
                   aria-label="<?php esc_attr_e('Name', 'geofolio'); ?>" />
            <button type="button" class="button-link gfo-person-remove" aria-label="<?php esc_attr_e('Remove this person', 'geofolio'); ?>">
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
        <div class="gfo-meta-box">
            <input type="hidden"
                   id="<?php echo esc_attr(self::GALLERY_FIELD_ID); ?>"
                   name="<?php echo esc_attr(self::GALLERY_FIELD_ID); ?>"
                   value="<?php echo esc_attr(wp_json_encode($ids)); ?>" />

            <p>
                <button type="button" id="geofolio_gallery_select" class="button">
                    <?php esc_html_e('Add / edit photos', 'geofolio'); ?>
                </button>
                <button type="button" id="geofolio_gallery_clear" class="button" <?php echo empty($ids) ? 'hidden' : ''; ?>>
                    <?php esc_html_e('Clear the gallery', 'geofolio'); ?>
                </button>
            </p>

            <div id="geofolio_gallery_preview" class="gfo-gallery-preview"></div>

            <p class="description">
                <?php esc_html_e('Drag and drop to reorder. First photo = cover.', 'geofolio'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Sauvegarder les meta boxes
     */
    public function save_meta_boxes($post_id, $post) {
        // Vérifications de sécurité
        if (!isset($_POST['geofolio_meta_nonce'])
            || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['geofolio_meta_nonce'])), 'geofolio_save_meta')) {
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
        if (isset($_POST['geofolio_people'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- People::parse() nettoie chaque rôle et chaque nom.
            $people = People::parse(wp_unslash($_POST['geofolio_people']));
            update_post_meta($post_id, FieldRegistry::meta_key('manager'), wp_slash(People::names($people)));
        }
    }
}
