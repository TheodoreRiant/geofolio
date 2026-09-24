<?php
/**
 * Map pages built with Elementor (Canvas template: no header, footer or
 * title) around a single map widget. Run by init.sh with `wp eval-file`;
 * a companion can include this file and reuse geofolio_dev_create_map_page().
 */

if (!function_exists('geofolio_dev_create_map_page')) {
    /**
     * Create an Elementor page holding a single map widget.
     *
     * @param string $title
     * @param string $slug
     * @param array  $settings Widget settings (Elementor control names).
     * @param string $widget   Widget name.
     * @return int Page ID.
     */
    function geofolio_dev_create_map_page($title, $slug, array $settings, $widget = 'geofolio_map') {
        $data = array(array(
            'id'       => substr(md5($slug . 's'), 0, 7),
            'elType'   => 'section',
            'settings' => array('layout' => 'full_width', 'gap' => 'no'),
            'elements' => array(array(
                'id'       => substr(md5($slug . 'c'), 0, 7),
                'elType'   => 'column',
                'settings' => array('_column_size' => 100, 'padding' => array('unit' => 'px', 'top' => '0', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => true)),
                'elements' => array(array(
                    'id'         => substr(md5($slug . 'w'), 0, 7),
                    'elType'     => 'widget',
                    'widgetType' => $widget,
                    'settings'   => $settings,
                    'elements'   => array(),
                )),
                'isInner'  => false,
            )),
            'isInner'  => false,
        ));

        $page_id = wp_insert_post(array(
            'post_type'   => 'page',
            'post_status' => 'publish',
            'post_title'  => $title,
            'post_name'   => $slug,
        ), true);
        if (is_wp_error($page_id)) {
            WP_CLI::error($page_id->get_error_message());
        }

        update_post_meta($page_id, '_wp_page_template', 'elementor_canvas');
        update_post_meta($page_id, '_elementor_edit_mode', 'builder');
        update_post_meta($page_id, '_elementor_template_type', 'wp-page');
        update_post_meta($page_id, '_elementor_version', defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : '');
        // Elementor expects escaped JSON (update_post_meta removes one layer).
        update_post_meta($page_id, '_elementor_data', wp_slash(wp_json_encode($data)));

        return $page_id;
    }
}

if (!defined('GEOFOLIO_DEV_PAGES_LIBRARY')) {
    $map = geofolio_dev_create_map_page('Map', 'map', array(
        'map_height' => array('unit' => 'vh', 'size' => 100),
    ));
    update_option('show_on_front', 'page');
    update_option('page_on_front', $map);

    // Elementor stylesheets are generated on the first visit.
    if (class_exists('\\Elementor\\Plugin')) {
        \Elementor\Plugin::$instance->files_manager->clear_cache();
    }
    WP_CLI::log("  map page (#$map, front page) created");
}
