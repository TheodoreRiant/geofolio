<?php
/**
 * Duplication d'un établissement depuis l'admin.
 *
 * Utile pour les services présents à plusieurs adresses (ex. prévention
 * spécialisée) : on duplique la fiche, puis on ne modifie que l'adresse.
 * La copie est créée en brouillon, donc absente de la carte tant qu'elle
 * n'est pas publiée.
 */

namespace Geofolio\Admin;

use Geofolio\Domain\Schema;

if (!defined('ABSPATH')) {
    exit;
}

class Duplicate {

    /** Action admin (admin.php?action=…) et clé du lien dans la liste. */
    const ACTION = 'geofolio_duplicate';

    const POST_TYPE = Schema::POST_TYPE;

    /** Paramètre ajouté à l'URL d'édition de la copie pour afficher l'avis. */
    const NOTICE_ARG = 'geofolio_duplicated';

    /**
     * Metas propres au post d'origine, jamais recopiées : verrou d'édition,
     * dernier éditeur, anciens slugs, état de corbeille, pings en attente.
     */
    const EXCLUDED_META_KEYS = array(
        '_edit_lock',
        '_edit_last',
        '_wp_old_slug',
        '_wp_old_date',
        '_wp_desired_post_slug',
        '_wp_trash_meta_status',
        '_wp_trash_meta_time',
        '_encloseme',
        '_pingme',
    );

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_filter('post_row_actions', array($this, 'add_row_action'), 10, 2);
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_script'));
        add_action('admin_action_' . self::ACTION, array($this, 'handle_admin_action'));
    }

    /* ------------------------------------------------------------------ */
    /*  Liens dans l'admin                                                 */
    /* ------------------------------------------------------------------ */

    /**
     * Ajouter « Dupliquer » aux actions au survol de la liste.
     *
     * @param array   $actions
     * @param \WP_Post $post
     * @return array
     */
    public function add_row_action($actions, $post) {
        if (!self::can_duplicate($post)) {
            return $actions;
        }

        return array_merge($actions, array(
            self::ACTION => sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                esc_url(self::duplicate_url($post->ID)),
                esc_attr(sprintf(/* translators: %s: place title */ __('Duplicate “%s”', 'geofolio'), $post->post_title)),
                esc_html__('Duplicate', 'geofolio')
            ),
        ));
    }

    /**
     * Éditeur de blocs : lien « Dupliquer » dans le panneau de la fiche et,
     * sur la copie fraîchement créée, avis rappelant de changer l'adresse.
     *
     * Les établissements s'éditent avec l'éditeur de blocs (show_in_rest) :
     * la metabox Publier classique et le hook admin_notices n'y sont pas
     * affichés, d'où ce passage par un petit script.
     */
    public function enqueue_editor_script() {
        $post = get_post();
        if (!self::can_duplicate($post) || $post->post_status === 'auto-draft') {
            return;
        }

        wp_enqueue_script(
            'geofolio-duplicate',
            GEOFOLIO_PLUGIN_URL . 'assets/js/geofolio-duplicate.js',
            array('wp-plugins', 'wp-element', 'wp-data', 'wp-notices', 'wp-edit-post'),
            GEOFOLIO_VERSION,
            true
        );

        wp_localize_script('geofolio-duplicate', 'geofolioDuplicate', array(
            'url'    => self::duplicate_url($post->ID),
            'label'  => __('Duplicate this place', 'geofolio'),
            'notice' => empty($_GET[self::NOTICE_ARG])
                ? ''
                : __('Copy created as a draft. Edit the address, click “Geocode address”, then publish it to show it on the map.', 'geofolio'),
        ));
    }

    /* ------------------------------------------------------------------ */
    /*  Traitement de la requête                                           */
    /* ------------------------------------------------------------------ */

    /**
     * Point d'entrée admin.php?action=geofolio_duplicate.
     */
    public function handle_admin_action() {
        $result = self::process_request(wp_unslash($_GET));

        if (is_wp_error($result)) {
            wp_die(
                esc_html($result->get_error_message()),
                esc_html__('Duplication failed', 'geofolio'),
                array('response' => 403, 'back_link' => true)
            );
        }

        wp_safe_redirect(add_query_arg(self::NOTICE_ARG, 1, get_edit_post_link($result, 'raw')));
        exit;
    }

    /**
     * Vérifier nonce et droits, puis dupliquer.
     *
     * @param array $query Paramètres de la requête (déjà déséchappés).
     * @return int|WP_Error ID de la copie, ou l'erreur à afficher.
     */
    public static function process_request(array $query) {
        $post_id = isset($query['post']) ? absint($query['post']) : 0;
        $nonce   = isset($query['_wpnonce']) ? (string) $query['_wpnonce'] : '';

        if (!$post_id || !wp_verify_nonce($nonce, self::nonce_action($post_id))) {
            return new \WP_Error(
                'geofolio_duplicate_nonce',
                __('This duplication link has expired or is invalid. Reload the list of places and try again.', 'geofolio')
            );
        }

        $post = get_post($post_id);
        if (!self::can_duplicate($post)) {
            return new \WP_Error(
                'geofolio_duplicate_forbidden',
                __('You do not have the required permissions to duplicate this place.', 'geofolio')
            );
        }

        return self::duplicate($post);
    }

    /**
     * L'utilisateur courant peut-il dupliquer ce contenu ?
     *
     * @param WP_Post|null $post
     * @return bool
     */
    public static function can_duplicate($post) {
        return $post
            && $post->post_type === self::POST_TYPE
            && current_user_can('edit_post', $post->ID)
            && current_user_can('edit_posts');
    }

    /**
     * @param int $post_id
     * @return string Action de nonce propre à cet établissement.
     */
    public static function nonce_action($post_id) {
        return self::ACTION . '_' . absint($post_id);
    }

    /**
     * @param int $post_id
     * @return string URL signée de duplication, non échappée (wp_nonce_url()
     *                encoderait les & en &amp;, inutilisable côté JS).
     */
    public static function duplicate_url($post_id) {
        return add_query_arg(
            array(
                'action'   => self::ACTION,
                'post'     => absint($post_id),
                '_wpnonce' => wp_create_nonce(self::nonce_action($post_id)),
            ),
            admin_url('admin.php')
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Service de duplication                                             */
    /* ------------------------------------------------------------------ */

    /**
     * Créer une copie brouillon : contenu, metas, taxonomies, couverture et
     * galerie (mêmes pièces jointes, aucun ré-upload).
     *
     * @param \WP_Post $post
     * @return int|WP_Error ID de la copie.
     */
    public static function duplicate($post) {
        // wp_insert_post() retire une couche d'échappement : on en ajoute une
        // pour que guillemets et antislash du contenu arrivent intacts.
        $new_id = wp_insert_post(wp_slash(self::build_copy_args($post, get_current_user_id())), true);

        if (is_wp_error($new_id)) {
            return $new_id;
        }

        self::copy_meta($post->ID, $new_id);
        self::copy_terms($post->ID, $new_id);

        return $new_id;
    }

    /**
     * Champs du post copie.
     *
     * @param \WP_Post $post
     * @param int     $author_id
     * @return array
     */
    public static function build_copy_args($post, $author_id) {
        return array(
            'post_type'      => $post->post_type,
            'post_status'    => 'draft',
            /* translators: %s: titre de l'établissement d'origine */
            'post_title'     => sprintf(__('%s (copy)', 'geofolio'), $post->post_title),
            'post_content'   => $post->post_content,
            'post_excerpt'   => $post->post_excerpt,
            'post_author'    => (int) $author_id,
            'menu_order'     => (int) $post->menu_order,
            'comment_status' => $post->comment_status,
            'ping_status'    => $post->ping_status,
        );
    }

    /**
     * Ne garder que les metas à recopier.
     *
     * @param array $all_meta Résultat de get_post_meta($id) : clé => valeurs.
     * @return array
     */
    public static function copyable_meta(array $all_meta) {
        return array_diff_key($all_meta, array_flip(self::EXCLUDED_META_KEYS));
    }

    private static function copy_meta($from_id, $to_id) {
        foreach (self::copyable_meta(get_post_meta($from_id)) as $key => $values) {
            foreach ($values as $value) {
                // get_post_meta($id) renvoie les valeurs brutes, sérialisées :
                // les désérialiser évite une double sérialisation à l'écriture.
                add_post_meta($to_id, $key, wp_slash(maybe_unserialize($value)));
            }
        }
    }

    private static function copy_terms($from_id, $to_id) {
        foreach (get_object_taxonomies(self::POST_TYPE) as $taxonomy) {
            $term_ids = wp_get_object_terms($from_id, $taxonomy, array('fields' => 'ids'));
            if (is_wp_error($term_ids) || empty($term_ids)) {
                continue;
            }
            wp_set_object_terms($to_id, array_map('intval', $term_ids), $taxonomy);
        }
    }
}
