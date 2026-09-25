<?php
/**
 * Uninstall Mapped Places.
 *
 * Removes the plugin's options, transients and migration data. Places and
 * their taxonomies are kept, because they are the site's content: define
 * MAPPED_PLACES_UNINSTALL_DATA as true in wp-config.php before uninstalling to
 * delete them as well.
 *
 * @package Mapped Places
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$mapped_places_options = array(
    'mapped_places_settings',
    'mapped_places_schema_version',
    'mapped_places_migrations_done',
    'mapped_places_migration_log_last',
    'mapped_places_rest_cache_generation',
);
foreach ($mapped_places_options as $mapped_places_option) {
    delete_option($mapped_places_option);
}
delete_transient('mapped_places_migration_lock');

global $wpdb;

// Snapshots taken before each migration, and any leftover transient.
// phpcs:disable WordPress.DB.DirectDatabaseQuery -- option names with a prefix: no API lists them.
$wpdb->query($wpdb->prepare(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s",
    $wpdb->esc_like('mapped_places_snapshot_') . '%',
    $wpdb->esc_like('_transient_mapped_places_') . '%',
    $wpdb->esc_like('_transient_timeout_mapped_places_') . '%'
));
// phpcs:enable

if (!defined('MAPPED_PLACES_UNINSTALL_DATA') || !MAPPED_PLACES_UNINSTALL_DATA) {
    return;
}

$mapped_places_taxonomies = array('mapl_type', 'mapl_region', 'mapl_service', 'mapl_accessibility', 'mapl_entity');

// The plugin is not loaded during uninstall: register the taxonomies so the
// term API accepts them.
foreach ($mapped_places_taxonomies as $mapped_places_taxonomy) {
    register_taxonomy($mapped_places_taxonomy, 'mapl_place');
}

$mapped_places_place_ids = get_posts(array(
    'post_type'      => 'mapl_place',
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'fields'         => 'ids',
));
foreach ($mapped_places_place_ids as $mapped_places_place_id) {
    wp_delete_post($mapped_places_place_id, true);
}

foreach ($mapped_places_taxonomies as $mapped_places_taxonomy) {
    $mapped_places_term_ids = get_terms(array(
        'taxonomy'   => $mapped_places_taxonomy,
        'hide_empty' => false,
        'fields'     => 'ids',
    ));
    if (is_wp_error($mapped_places_term_ids)) {
        continue;
    }
    foreach ($mapped_places_term_ids as $mapped_places_term_id) {
        wp_delete_term($mapped_places_term_id, $mapped_places_taxonomy);
    }
}
