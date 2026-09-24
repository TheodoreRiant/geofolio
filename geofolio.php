<?php
/**
 * Plugin Name: Geofolio
 * Plugin URI: https://github.com/TheodoreRiant/geofolio
 * Description: Interactive map of places with search, filters and an Elementor widget.
 * Version: 1.2.1
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: Théodore Riant
 * Author URI: https://allside.studio
 * License: GPL v2 or later
 * Text Domain: geofolio
 * Domain Path: /languages
 */

// Security: no direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('GEOFOLIO_VERSION', '1.2.1');
define('GEOFOLIO_PLUGIN_FILE', __FILE__);
define('GEOFOLIO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GEOFOLIO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GEOFOLIO_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once __DIR__ . '/src/autoload.php';

/**
 * Initialiser le plugin
 */
function geofolio_init() {
    return \Geofolio\Plugin::get_instance();
}

// Lancer le plugin
geofolio_init();
