<?php
/**
 * Outils de termes génériques pour les migrations : garantir un terme,
 * le retrouver, fusionner des alias, semer une couleur.
 */

namespace Geofolio\Migration;

use Geofolio\Domain\Schema;

if (!defined('ABSPATH')) {
    exit;
}

class TermTools {

    /** Meta de terme portant la couleur d'une entité. */
    const COLOR_META = Schema::ENTITY_COLOR_META;

    /**
     * Garantit l'existence d'un terme canonique. Idempotent.
     *
     * @param string $taxonomy Taxonomie cible.
     * @param string $name     Nom du terme canonique.
     * @param string $slug     Slug du terme canonique.
     * @return array{term_id:int,created:bool,error?:string}
     */
    public static function ensure_term($taxonomy, $name, $slug) {
        $existing = get_term_by('slug', $slug, $taxonomy);
        if ($existing && !is_wp_error($existing)) {
            return array('term_id' => (int) $existing->term_id, 'created' => false);
        }

        $term = wp_insert_term($name, $taxonomy, array('slug' => $slug));
        if (!is_wp_error($term)) {
            return array('term_id' => (int) $term['term_id'], 'created' => true);
        }

        $fallback = get_term_by('name', $name, $taxonomy);
        if ($fallback && !is_wp_error($fallback)) {
            return array('term_id' => (int) $fallback->term_id, 'created' => false);
        }

        return array(
            'term_id' => 0,
            'created' => false,
            'error'   => $term->get_error_message(),
        );
    }

    /**
     * Retrouve un terme par slug puis par nom exact.
     *
     * @param string $taxonomy Taxonomie cible.
     * @param string $alias    Slug ou nom possible.
     * @return WP_Term|null
     */
    public static function find_term_by_slug_or_name($taxonomy, $alias) {
        foreach (array('slug', 'name') as $field) {
            $term = get_term_by($field, $alias, $taxonomy);
            if ($term && !is_wp_error($term)) {
                return $term;
            }
        }
        return null;
    }

    /**
     * Fusionne des termes alias dans un terme canonique, en préservant les
     * autres termes de la taxonomie déjà attachés aux articles.
     *
     * @param string $taxonomy     Taxonomie cible.
     * @param int    $canonical_id ID du terme canonique.
     * @param array  $aliases      Slugs ou noms à fusionner.
     * @return array Rapport.
     */
    public static function merge_terms_by_aliases($taxonomy, $canonical_id, array $aliases) {
        $report = array(
            'canonical_id'     => (int) $canonical_id,
            'merged_terms'     => array(),
            'reassigned_posts' => 0,
            'deleted_terms'    => 0,
            'errors'           => array(),
        );

        foreach ($aliases as $alias) {
            $source = self::find_term_by_slug_or_name($taxonomy, $alias);
            if (!$source || (int) $source->term_id === (int) $canonical_id) {
                continue;
            }

            $merged = self::merge_term($taxonomy, (int) $source->term_id, (int) $canonical_id);
            $report['reassigned_posts'] += $merged['reassigned'];
            if ($merged['deleted']) {
                $report['deleted_terms']++;
            }
            if ($merged['errors']) {
                $report['errors'][$source->slug] = $merged['errors'];
            }

            $report['merged_terms'][] = array(
                'term_id'          => (int) $source->term_id,
                'slug'             => $source->slug,
                'name'             => $source->name,
                'reassigned_posts' => $merged['reassigned'],
                'deleted'          => $merged['deleted'],
                'errors'           => $merged['errors'],
            );
        }

        if (!empty($report['merged_terms'])) {
            wp_update_term_count_now(array((int) $canonical_id), $taxonomy);
        }

        return $report;
    }

    /**
     * Pose la couleur d'un terme UNIQUEMENT si aucune n'est définie : l'admin
     * WordPress reste la seule source de vérité des couleurs, une migration
     * ne fait que semer une valeur sur un terme neuf.
     *
     * @param int    $term_id
     * @param string $hex
     * @return bool true si la couleur a été semée.
     */
    public static function seed_term_color($term_id, $hex) {
        if (!empty(get_term_meta($term_id, self::COLOR_META, true))) {
            return false;
        }
        $sanitized = sanitize_hex_color($hex);
        if (!$sanitized) {
            return false;
        }
        update_term_meta($term_id, self::COLOR_META, $sanitized);
        return true;
    }

    /**
     * Réaffecte les articles d'un terme source vers le terme canonique, puis
     * supprime le source si tous les articles ont été réaffectés (sinon ils
     * perdraient les deux termes).
     *
     * @param string $taxonomy
     * @param int    $source_id
     * @param int    $canonical_id
     * @return array{reassigned: int, deleted: bool, errors: array}
     */
    private static function merge_term($taxonomy, $source_id, $canonical_id) {
        $reassigned = 0;
        $errors     = array();

        $post_ids = get_objects_in_term($source_id, $taxonomy);
        foreach (is_wp_error($post_ids) ? array() : $post_ids as $post_id) {
            $current_ids = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'ids'));
            if (is_wp_error($current_ids)) {
                $errors[] = array('post_id' => (int) $post_id, 'error' => $current_ids->get_error_message());
                continue;
            }

            $new_ids   = array_diff(array_map('intval', $current_ids), array($source_id));
            $new_ids[] = $canonical_id;
            $set       = wp_set_object_terms($post_id, array_values(array_unique(array_filter($new_ids))), $taxonomy, false);
            if (is_wp_error($set)) {
                $errors[] = array('post_id' => (int) $post_id, 'error' => $set->get_error_message());
                continue;
            }
            $reassigned++;
        }

        $deleted = false;
        if (empty($errors)) {
            $result  = wp_delete_term($source_id, $taxonomy);
            $deleted = !is_wp_error($result) && (bool) $result;
        }

        return array('reassigned' => $reassigned, 'deleted' => $deleted, 'errors' => $errors);
    }
}
