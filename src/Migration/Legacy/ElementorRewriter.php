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
     * Seules les lignes qui nomment un ancien widget sont sélectionnées, par
     * identifiant, puis lues et réécrites une à une : un site Elementor
     * volumineux (des dizaines de Mo de _elementor_data) tient en mémoire.
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
        $updated = 0;
        foreach (self::meta_ids($old) as $meta_id) {
            $updated += self::apply_to_meta((int) $meta_id, $old, $new);
        }
        if ($updated > 0 && class_exists('\Elementor\Plugin') && isset(\Elementor\Plugin::$instance->files_manager)) {
            \Elementor\Plugin::$instance->files_manager->clear_cache();
        }
        return $updated;
    }

    /**
     * Identifiants des metas _elementor_data qui nomment un ancien widget.
     *
     * @param string[] $old
     * @return int[]
     */
    private static function meta_ids(array $old) {
        global $wpdb;
        $ids = array();
        foreach ($old as $name) {
            $found = $wpdb->get_col($wpdb->prepare(
                "SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value LIKE %s ORDER BY meta_id",
                self::META_KEY,
                '%' . $wpdb->esc_like('"widgetType":"' . $name . '"') . '%'
            ));
            $ids = array_merge($ids, array_map('intval', (array) $found));
        }
        $ids = array_values(array_unique($ids));
        sort($ids);
        return $ids;
    }

    /**
     * Réécrire une meta, désignée par son identifiant : la meta d'une
     * révision reste sur la révision, jamais sur sa page parente.
     *
     * @param int      $meta_id
     * @param string[] $old
     * @param string   $new
     * @return int 1 si la ligne a été réécrite.
     */
    private static function apply_to_meta($meta_id, array $old, $new) {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_id = %d",
            $meta_id
        ));
        if (!$row) {
            return 0;
        }
        $data = json_decode((string) $row->meta_value, true);
        if (!is_array($data)) {
            return 0;
        }
        list($data, $count) = self::rewrite($data, $old, $new);
        if ($count === 0) {
            return 0;
        }
        $wpdb->update($wpdb->postmeta, array('meta_value' => wp_json_encode($data)), array('meta_id' => $meta_id), array('%s'), array('%d'));
        wp_cache_delete((int) $row->post_id, 'post_meta');
        return 1;
    }
}
