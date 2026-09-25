<?php
/**
 * $wpdb factice : enregistre les requêtes au lieu de les exécuter.
 */

class Mapl_Test_Wpdb {
    public $posts         = 'wp_posts';
    public $postmeta      = 'wp_postmeta';
    public $term_taxonomy = 'wp_term_taxonomy';
    public $termmeta      = 'wp_termmeta';
    public $options       = 'wp_options';

    /** @var array[] Appels à update() : [table, data, where]. */
    public $updates = array();

    /** @var string[] Requêtes de lecture. */
    public $queries = array();

    /** @var string[] Résultat renvoyé par get_col(). */
    public $col_result = array();

    public function update($table, $data, $where) {
        $this->updates[] = array($table, $data, $where);
        return 1;
    }

    /** @var object[] Résultat renvoyé par get_results(). */
    public $results = array();

    public function get_results($query) {
        $this->queries[] = $query;
        return $this->results;
    }

    public function get_col($query) {
        $this->queries[] = $query;
        return $this->col_result;
    }

    public function prepare($query, ...$args) {
        return vsprintf(str_replace('%s', "'%s'", $query), $args);
    }

    public function esc_like($text) {
        return addcslashes($text, '_%\\');
    }
}

function mapl_test_reset_wpdb() {
    $GLOBALS['wpdb'] = new Mapl_Test_Wpdb();
    return $GLOBALS['wpdb'];
}

function update_metadata($type, $id, $key, $value) {
    $GLOBALS['mapl_test_meta'][(int) $id][$key] = array(wp_unslash($value));
    return true;
}

function delete_metadata($type, $id, $key) {
    unset($GLOBALS['mapl_test_meta'][(int) $id][$key]);
    return true;
}

function metadata_exists($type, $id, $key) {
    return isset($GLOBALS['mapl_test_meta'][(int) $id][$key]);
}

/** Comme remove_accents() de WordPress : lettres accentuées ramenées à leur base. */
function remove_accents($text) {
    $decomposed = \Normalizer::normalize((string) $text, \Normalizer::FORM_D);
    return preg_replace('/\p{Mn}+/u', '', $decomposed === false ? (string) $text : $decomposed);
}

function clean_post_cache($id) {
    return true;
}

function wp_cache_flush() {
    return true;
}

function flush_rewrite_rules($hard = true) {
    $GLOBALS['mapl_test_flushed'] = true;
}

mapl_test_reset_wpdb();
