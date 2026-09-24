<?php
/**
 * Amorçage des tests : substituts minimaux aux fonctions WordPress.
 *
 * Les classes testées (registre des fonds de carte, réglages) sont de la
 * logique pure ; ces substituts permettent de les exécuter hors WordPress,
 * sans installer la suite de tests officielle.
 *
 * Lancer :  php tests/run-tests.php
 */

define('ABSPATH', __DIR__ . '/');
define('GEOFOLIO_PLUGIN_DIR', dirname(__DIR__) . '/');

/** Options simulées, remplacées par chaque test. */
$GLOBALS['gfo_test_options'] = array();

/** Erreurs de réglages collectées par add_settings_error(). */
$GLOBALS['gfo_test_settings_errors'] = array();

function __($text, $domain = null) {
    return $text;
}

function esc_html__($text, $domain = null) {
    return $text;
}

function get_option($name, $default = false) {
    return array_key_exists($name, $GLOBALS['gfo_test_options'])
        ? $GLOBALS['gfo_test_options'][$name]
        : $default;
}

function sanitize_key($key) {
    return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $key));
}

function sanitize_text_field($value) {
    $value = (string) $value;
    $value = strip_tags($value);
    $value = preg_replace('/[\r\n\t\x00-\x1F]/', '', $value);
    return trim($value);
}

function sanitize_textarea_field($value) {
    return trim(strip_tags((string) $value));
}

function sanitize_email($value) {
    return filter_var((string) $value, FILTER_VALIDATE_EMAIL) ? (string) $value : '';
}

function esc_url_raw($url, $protocols = null) {
    return preg_match('#^https?://#i', (string) $url) ? (string) $url : '';
}

function wp_kses($value, $allowed) {
    // Substitut suffisant pour les tests : on ne garde pas le HTML non listé.
    $tags = '';
    foreach (array_keys($allowed) as $tag) {
        $tags .= '<' . $tag . '>';
    }
    return strip_tags((string) $value, $tags);
}

function add_settings_error($setting, $code, $message, $type = 'error') {
    $GLOBALS['gfo_test_settings_errors'][] = array(
        'setting' => $setting,
        'code'    => $code,
        'message' => $message,
        'type'    => $type,
    );
}

function add_meta_box() {}

/** Pieces jointes simulees reconnues comme images. */
$GLOBALS['gfo_test_images'] = array();

function wp_attachment_is_image($id) {
    return in_array((int) $id, $GLOBALS['gfo_test_images'], true);
}

function absint($value) {
    return abs((int) $value);
}
function add_submenu_page() {}
function register_setting() {}

/**
 * Réinitialiser l'état simulé entre deux tests.
 *
 * @param array $options Options à installer.
 */
/** Langue du site simulée. */
$GLOBALS['gfo_test_locale'] = 'en_US';

function get_locale() {
    return $GLOBALS['gfo_test_locale'];
}

function gfo_test_reset(array $options = array()) {
    $GLOBALS['gfo_test_options']        = $options;
    $GLOBALS['gfo_test_settings_errors'] = array();
}

/**
 * Codes d'erreur collectés depuis la dernière réinitialisation.
 *
 * @return string[]
 */
function gfo_test_error_codes() {
    return array_map(
        static function ($error) { return $error['code']; },
        $GLOBALS['gfo_test_settings_errors']
    );
}

function wp_strip_all_tags($string, $remove_breaks = false) {
    $string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $string);
    $string = strip_tags($string);
    if ($remove_breaks) {
        $string = preg_replace('/[\r\n\t ]+/', ' ', $string);
    }
    return trim($string);
}

require_once __DIR__ . '/stubs/wp-hooks.php';
require_once __DIR__ . '/stubs/wp-posts.php';
require_once __DIR__ . '/stubs/wp-terms.php';
require_once __DIR__ . '/stubs/elementor.php';
require_once __DIR__ . '/stubs/wpdb.php';

require_once dirname(__DIR__) . '/src/autoload.php';
// Extensions compagnes livrées dans le même dépôt (leurs tests tournent ici).
foreach (glob(dirname(__DIR__) . '/*/src/autoload.php') as $companion_autoload) {
    require_once $companion_autoload;
}


function wp_json_encode($data) {
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

if (!function_exists('esc_textarea')) {
    function esc_textarea($text) {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('remove_meta_box')) {
    function remove_meta_box($id, $screen, $context) {
        $GLOBALS['gfo_test_removed_meta_boxes'][] = array($id, $screen, $context);
    }
}
