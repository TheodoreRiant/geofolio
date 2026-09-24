<?php
/**
 * Uninstall Geofolio.
 *
 * Removes the plugin's options, transients and migration data. Places and
 * their taxonomies are kept, because they are the site's content: define
 * GEOFOLIO_UNINSTALL_DATA as true in wp-config.php before uninstalling to
 * delete them as well.
 *
 * @package Geofolio
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$geofolio_options = array(
    'geofolio_settings',
    'geofolio_schema_version',
    'geofolio_migrations_done',
    'geofolio_migration_log_last',
);
foreach ($geofolio_options as $geofolio_option) {
    delete_option($geofolio_option);
}
delete_transient('geofolio_migration_lock');

global $wpdb;

// Snapshots taken before each migration, and any leftover transient.
// phpcs:disable WordPress.DB.DirectDatabaseQuery -- option names with a prefix: no API lists them.
$wpdb->query($wpdb->prepare(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
    $wpdb->esc_like('geofolio_snapshot_') . '%',
    $wpdb->esc_like('_transient_geofolio_') . '%',
    $wpdb->esc_like('_transient_timeout_geofolio_') . '%'
));
// phpcs:enable

if (!defined('GEOFOLIO_UNINSTALL_DATA') || !GEOFOLIO_UNINSTALL_DATA) {
    return;
}

$geofolio_taxonomies = array('gfo_type', 'gfo_region', 'gfo_service', 'gfo_accessibility', 'gfo_entity');

// The plugin is not loaded during uninstall: register the taxonomies so the
// term API accepts them.
foreach ($geofolio_taxonomies as $geofolio_taxonomy) {
    register_taxonomy($geofolio_taxonomy, 'gfo_place');
}

$geofolio_place_ids = get_posts(array(
    'post_type'      => 'gfo_place',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'fields'         => 'ids',
));
foreach ($geofolio_place_ids as $geofolio_place_id) {
    wp_delete_post($geofolio_place_id, true);
}

foreach ($geofolio_taxonomies as $geofolio_taxonomy) {
    $geofolio_term_ids = get_terms(array(
        'taxonomy'   => $geofolio_taxonomy,
        'hide_empty' => false,
        'fields'     => 'ids',
    ));
    if (is_wp_error($geofolio_term_ids)) {
        continue;
    }
    foreach ($geofolio_term_ids as $geofolio_term_id) {
        wp_delete_term($geofolio_term_id, $geofolio_taxonomy);
    }
}
