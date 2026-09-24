<?php
/**
 * Substituts WordPress en mémoire pour les termes et leurs metas.
 *
 * Chaque test repart d'un état vide via gfo_test_reset_terms().
 */

class WP_Term {
    public $term_id  = 0;
    public $name     = '';
    public $slug     = '';
    public $taxonomy = '';
    public $count    = 0;
    public $parent   = 0;

    public function __construct(array $fields) {
        foreach ($fields as $key => $value) {
            $this->$key = $value;
        }
    }
}

/**
 * Vider les termes et metas simulés.
 */
function gfo_test_reset_terms() {
    $GLOBALS['gfo_test_term_store']     = array();
    $GLOBALS['gfo_test_term_meta'] = array();
    $GLOBALS['gfo_test_next_term'] = 100;
}

/**
 * Ajouter un terme simulé.
 *
 * @param string $taxonomy
 * @param string $name
 * @param string $slug
 * @param array  $meta  Metas de terme.
 * @param int    $count Nombre d'objets rattachés.
 * @return WP_Term
 */
function gfo_test_add_term($taxonomy, $name, $slug, array $meta = array(), $count = 1) {
    $id   = $GLOBALS['gfo_test_next_term']++;
    $term = new WP_Term(array(
        'term_id'  => $id,
        'name'     => $name,
        'slug'     => $slug,
        'taxonomy' => $taxonomy,
        'count'    => $count,
    ));
    $GLOBALS['gfo_test_term_store'][$id]     = $term;
    $GLOBALS['gfo_test_term_meta'][$id] = $meta;
    return $term;
}

function get_terms($args = array()) {
    $taxonomy   = isset($args['taxonomy']) ? (array) $args['taxonomy'] : array();
    $hide_empty = !isset($args['hide_empty']) || $args['hide_empty'];
    $terms      = array_filter($GLOBALS['gfo_test_term_store'], static function ($term) use ($taxonomy, $hide_empty) {
        return in_array($term->taxonomy, $taxonomy, true) && (!$hide_empty || $term->count > 0);
    });
    usort($terms, static function ($a, $b) {
        return strcmp($a->name, $b->name);
    });
    return array_values($terms);
}

function get_term_by($field, $value, $taxonomy = '') {
    foreach ($GLOBALS['gfo_test_term_store'] as $term) {
        if ($taxonomy !== '' && $term->taxonomy !== $taxonomy) {
            continue;
        }
        $match = ($field === 'slug' && $term->slug === $value)
            || ($field === 'name' && $term->name === $value)
            || (in_array($field, array('id', 'term_id'), true) && (int) $term->term_id === (int) $value);
        if ($match) {
            return $term;
        }
    }
    return false;
}

function term_exists($term, $taxonomy = '') {
    $found = get_term_by('slug', $term, $taxonomy) ?: get_term_by('name', $term, $taxonomy);
    return $found ? array('term_id' => $found->term_id, 'term_taxonomy_id' => $found->term_id) : null;
}

function wp_insert_term($name, $taxonomy, $args = array()) {
    $slug = isset($args['slug']) ? $args['slug'] : strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
    if (get_term_by('slug', $slug, $taxonomy) || get_term_by('name', $name, $taxonomy)) {
        return new WP_Error('term_exists', 'Terme existant');
    }
    $term = gfo_test_add_term($taxonomy, $name, $slug, array(), 0);
    return array('term_id' => $term->term_id, 'term_taxonomy_id' => $term->term_id);
}

function wp_update_term($term_id, $taxonomy, $args = array()) {
    foreach ($args as $key => $value) {
        $GLOBALS['gfo_test_term_store'][$term_id]->$key = $value;
    }
    return array('term_id' => $term_id);
}

function wp_delete_term($term_id, $taxonomy) {
    unset($GLOBALS['gfo_test_term_store'][$term_id], $GLOBALS['gfo_test_term_meta'][$term_id]);
    return true;
}

function wp_update_term_count_now($terms, $taxonomy) {
    return true;
}

/**
 * Articles liés à un terme (liens posés par wp_set_object_terms avec des IDs).
 */
function get_objects_in_term($term_ids, $taxonomies) {
    $term_ids = array_map('intval', (array) $term_ids);
    $objects  = array();
    foreach ($GLOBALS['gfo_test_terms'] as $post_id => $by_taxonomy) {
        foreach ((array) $taxonomies as $taxonomy) {
            $linked = array_map('intval', (array) ($by_taxonomy[$taxonomy] ?? array()));
            if (array_intersect($term_ids, $linked)) {
                $objects[] = (string) $post_id;
            }
        }
    }
    return array_values(array_unique($objects));
}

function wp_get_post_terms($post_id, $taxonomy, $args = array()) {
    return array_values((array) ($GLOBALS['gfo_test_terms'][(int) $post_id][$taxonomy] ?? array()));
}

function get_term($term_id, $taxonomy = '') {
    return isset($GLOBALS['gfo_test_term_store'][$term_id]) ? $GLOBALS['gfo_test_term_store'][$term_id] : null;
}

function get_term_meta($term_id, $key = '', $single = false) {
    $meta = isset($GLOBALS['gfo_test_term_meta'][$term_id]) ? $GLOBALS['gfo_test_term_meta'][$term_id] : array();
    if ($key === '') {
        return $meta;
    }
    return array_key_exists($key, $meta) ? $meta[$key] : ($single ? '' : array());
}

function update_term_meta($term_id, $key, $value) {
    $GLOBALS['gfo_test_term_meta'][$term_id][$key] = $value;
    return true;
}

function delete_term_meta($term_id, $key) {
    unset($GLOBALS['gfo_test_term_meta'][$term_id][$key]);
    return true;
}

gfo_test_reset_terms();

function sanitize_hex_color($color) {
    return preg_match('/^#([A-Fa-f0-9]{3}){1,2}$/', (string) $color) ? (string) $color : null;
}
