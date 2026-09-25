<?php
/**
 * Renommer les widgets Elementor d'un ancien plugin dans _elementor_data, en
 * passant par la structure décodée (jamais par remplacement de texte).
 *
 * @package Mapped Places
 */

namespace MappedPlaces\Migration\Legacy;

if (!defined('ABSPATH')) {
    exit;
}

class ElementorRewriter {

    /** Meta où Elementor range la structure d'une page. */
    const META_KEY = '_elementor_data';

    /**
     * Renommer les widgets, à toute profondeur.
     *
     * @param array    $elements Structure Elementor décodée.
     * @param string[] $old      Anciens noms de widget.
     * @param string   $new      Nouveau nom.
     * @return array{0: array, 1: int} Structure et nombre de widgets renommés.
     */
    public static function rewrite(array $elements, array $old, $new) {
        $count = 0;
        foreach ($elements as $index => $element) {
            if (!is_array($element)) {
                continue;
            }
            if (isset($element['widgetType']) && in_array($element['widgetType'], $old, true)) {
                $element['widgetType'] = $new;
                $count++;
            }
            if (isset($element['elements']) && is_array($element['elements'])) {
                list($element['elements'], $nested) = self::rewrite($element['elements'], $old, $new);
                $count += $nested;
            }
            $elements[$index] = $element;
        }
        return array($elements, $count);
    }

    /**
     * Appliquer à tous les posts, révisions comprises.
     *
     * @param string[] $old
     * @param string   $new
     * @return int Nombre de posts réécrits.
     */
    public static function apply(array $old, $new) {
        global $wpdb;
        if ($old === array()) {
            return 0;
        }
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value LIKE %s",
            self::META_KEY,
            '%' . $wpdb->esc_like('"widgetType"') . '%'
        ));
        $updated = 0;
        foreach ((array) $rows as $row) {
            $data = json_decode((string) $row->meta_value, true);
            if (!is_array($data)) {
                continue;
            }
            list($data, $count) = self::rewrite($data, $old, $new);
            if ($count > 0) {
                // update_metadata() et non update_post_meta(), qui écrirait
                // la meta d'une révision sur sa page parente.
                update_metadata('post', (int) $row->post_id, self::META_KEY, wp_slash(wp_json_encode($data)));
                $updated++;
            }
        }
        if ($updated > 0 && class_exists('\Elementor\Plugin') && isset(\Elementor\Plugin::$instance->files_manager)) {
            \Elementor\Plugin::$instance->files_manager->clear_cache();
        }
        return $updated;
    }
}
