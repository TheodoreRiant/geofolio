<?php
/**
 * Substituts des filtres WordPress : un vrai registre en mémoire, pour
 * tester les points d'extension (préréglages, plugins compagnons).
 */

/** Rappels enregistrés : hook => priorité => liste de [rappel, nb d'arguments]. */
$GLOBALS['gfo_test_filters'] = array();

function add_filter($hook, $callback = null, $priority = 10, $accepted_args = 1) {
    $GLOBALS['gfo_test_filters'][$hook][$priority][] = array($callback, $accepted_args);
    return true;
}

function apply_filters($hook, $value, ...$args) {
    if (empty($GLOBALS['gfo_test_filters'][$hook])) {
        return $value;
    }
    $by_priority = $GLOBALS['gfo_test_filters'][$hook];
    ksort($by_priority);
    foreach ($by_priority as $callbacks) {
        foreach ($callbacks as $entry) {
            list($callback, $accepted) = $entry;
            $value = call_user_func_array($callback, array_slice(array_merge(array($value), $args), 0, max(1, $accepted)));
        }
    }
    return $value;
}

function has_filter($hook, $callback = false) {
    if (empty($GLOBALS['gfo_test_filters'][$hook])) {
        return false;
    }
    if ($callback === false) {
        return true;
    }
    foreach ($GLOBALS['gfo_test_filters'][$hook] as $priority => $callbacks) {
        foreach ($callbacks as $entry) {
            if ($entry[0] === $callback) {
                return $priority;
            }
        }
    }
    return false;
}

function add_action($hook, $callback = null, $priority = 10, $accepted_args = 1) {
    return add_filter($hook, $callback, $priority, $accepted_args);
}

function do_action($hook, ...$args) {
    apply_filters($hook, null, ...$args);
}

/**
 * Vider le registre (un hook précis, ou tous).
 *
 * @param string|null $hook
 */
function gfo_test_reset_filters($hook = null) {
    if ($hook === null) {
        $GLOBALS['gfo_test_filters'] = array();
        return;
    }
    unset($GLOBALS['gfo_test_filters'][$hook]);
}

function esc_attr_e($text, $domain = null) {
    echo esc_attr($text);
}

function esc_html_e($text, $domain = null) {
    echo esc_html($text);
}

function esc_attr__($text, $domain = null) {
    return esc_attr($text);
}

function shortcode_atts($pairs, $atts, $shortcode = '') {
    $atts = (array) $atts;
    $out  = array();
    foreach ($pairs as $name => $default) {
        $out[$name] = array_key_exists($name, $atts) ? $atts[$name] : $default;
    }
    return $out;
}

function update_option($name, $value, $autoload = null) {
    $GLOBALS['gfo_test_options'][$name] = $value;
    return true;
}

function delete_option($name) {
    unset($GLOBALS['gfo_test_options'][$name]);
    return true;
}

$GLOBALS['gfo_test_transients'] = array();

function get_transient($name) {
    return $GLOBALS['gfo_test_transients'][$name] ?? false;
}

function set_transient($name, $value, $expiration = 0) {
    $GLOBALS['gfo_test_transients'][$name] = $value;
    return true;
}

function delete_transient($name) {
    unset($GLOBALS['gfo_test_transients'][$name]);
    return true;
}

function current_time($type) {
    return '2026-09-24 12:00:00';
}

/** Aucun script enregistré : les assets de la carte ne sont pas chargés en test. */
function wp_script_is($handle, $status = 'enqueued') {
    return false;
}

function sanitize_title($title) {
    return trim(preg_replace('/[^a-z0-9_-]+/', '-', strtolower((string) $title)), '-');
}
