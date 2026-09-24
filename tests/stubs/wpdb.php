<?php
/**
 * $wpdb factice : enregistre les requêtes au lieu de les exécuter.
 */

class Gfo_Test_Wpdb {
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

function gfo_test_reset_wpdb() {
    $GLOBALS['wpdb'] = new Gfo_Test_Wpdb();
    return $GLOBALS['wpdb'];
}

function wp_cache_flush() {
    return true;
}

function flush_rewrite_rules($hard = true) {
    $GLOBALS['gfo_test_flushed'] = true;
}

gfo_test_reset_wpdb();
