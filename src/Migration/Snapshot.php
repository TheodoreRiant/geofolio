<?php
/**
 * Snapshot des données critiques avant exécution d'une migration.
 *
 * Stocke dans wp_options (autoload=false) un instantané :
 *   - Liste des termes Schema::TAX_ENTITY avec leurs noms et couleurs
 *   - Mapping post → entite(s)
 *
 * Permet une restauration manuelle en cas de migration foireuse.
 */

namespace Geofolio\Migration;

use Geofolio\Domain\Schema;

if (!defined('ABSPATH')) {
    exit;
}

class Snapshot {

    const OPTION_PREFIX = 'geofolio_snapshot_';

    /**
     * Crée un snapshot et retourne sa clé d'option.
     *
     * @return string Clé wp_option du snapshot.
     */
    public static function create() {
        $snapshot = array(
            'version'   => defined('GEOFOLIO_VERSION') ? GEOFOLIO_VERSION : 'unknown',
            'timestamp' => time(),
            'terms'     => array(),
            'posts'     => array(),
        );

        // Snapshot des termes Schema::TAX_ENTITY (nom + couleur)
        $terms = get_terms(array(
            'taxonomy'   => Schema::TAX_ENTITY,
            'hide_empty' => false,
        ));
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $snapshot['terms'][$term->slug] = array(
                    'term_id' => (int) $term->term_id,
                    'name'    => $term->name,
                    'color'   => get_term_meta($term->term_id, Schema::ENTITY_COLOR_META, true),
                );
            }
        }

        // Snapshot du mapping posts → entites
        $query = new \WP_Query(array(
            'post_type'      => Schema::POST_TYPE,
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ));
        foreach ($query->posts as $post_id) {
            $entites = wp_get_post_terms($post_id, Schema::TAX_ENTITY, array('fields' => 'slugs'));
            if (is_wp_error($entites)) {
                $entites = array();
            }
            $snapshot['posts'][(int) $post_id] = array(
                'title'   => get_post_field('post_title', $post_id),
                'entites' => $entites,
            );
        }

        $key = self::OPTION_PREFIX . $snapshot['timestamp'];
        // autoload=false : on ne charge pas ce gros payload sur chaque requête
        add_option($key, $snapshot, '', 'no');

        return $key;
    }

    /**
     * Récupère un snapshot par sa clé.
     *
     * @param string $key Clé wp_option.
     * @return array|null
     */
    public static function get($key) {
        $snap = get_option($key);
        return is_array($snap) ? $snap : null;
    }

    /**
     * Liste les snapshots existants, du plus récent au plus ancien.
     *
     * @return array Liste de [key, timestamp, version]
     */
    public static function list_all() {
        global $wpdb;
        $like = $wpdb->esc_like(self::OPTION_PREFIX) . '%';
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_name DESC",
            $like
        ));

        $list = array();
        foreach ($rows as $row) {
            $snap = get_option($row->option_name);
            if (is_array($snap)) {
                $list[] = array(
                    'key'       => $row->option_name,
                    'timestamp' => $snap['timestamp'] ?? 0,
                    'version'   => $snap['version'] ?? 'unknown',
                );
            }
        }
        return $list;
    }

    /**
     * Restaure les couleurs et le mapping posts→entites depuis un snapshot.
     * Cette méthode est défensive et n'écrase que ce qui est explicitement présent
     * dans le snapshot.
     *
     * @param string $key Clé wp_option.
     * @return array Compteur de restaurations.
     */
    public static function restore($key) {
        $snap = self::get($key);
        if (!$snap) {
            return array('error' => 'snapshot introuvable');
        }

        $report = array('terms' => 0, 'posts' => 0);

        // Restaure les couleurs des termes
        foreach ($snap['terms'] as $slug => $data) {
            $term = get_term_by('slug', $slug, Schema::TAX_ENTITY);
            if ($term && !empty($data['color'])) {
                update_term_meta($term->term_id, Schema::ENTITY_COLOR_META,
                    sanitize_hex_color($data['color']));
                $report['terms']++;
            }
        }

        // Restaure le mapping posts → entites
        foreach ($snap['posts'] as $post_id => $data) {
            $post = get_post($post_id);
            if (!$post || $post->post_type !== Schema::POST_TYPE) {
                continue;
            }
            $entites = $data['entites'] ?? array();
            wp_set_object_terms((int) $post_id, $entites, Schema::TAX_ENTITY, false);
            $report['posts']++;
        }

        return $report;
    }
}
