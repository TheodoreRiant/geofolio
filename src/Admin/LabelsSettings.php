<?php
/**
 * Réglages « Libellés et défauts » : noms du type de contenu et de la
 * taxonomie « entité », slug d'URL des lieux, valeurs par défaut des
 * nouvelles cartes. Priorité : filtre > réglage > valeur du code.
 *
 * @package Geofolio
 */

namespace Geofolio\Admin;

use Geofolio\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

class LabelsSettings {

    /** Option enregistrée. */
    const OPTION_NAME = 'geofolio_labels';

    /** Groupe de réglages du formulaire. */
    const OPTION_GROUP = 'geofolio_labels_group';

    /** Zoom maximal accepté. */
    const MAX_ZOOM = 18;

    /** Valeurs par défaut des cartes que ce réglage peut fixer. */
    const MAP_KEYS = array('sidebar_title', 'sidebar_subtitle', 'center_lat', 'center_lng', 'zoom', 'fit_bounds');

    /**
     * Valeurs par défaut : vides, le code décide.
     *
     * @return array<string, string>
     */
    public static function defaults() {
        return array(
            'place_singular'   => '',
            'place_plural'     => '',
            'entity_singular'  => '',
            'entity_plural'    => '',
            'place_slug'       => '',
            'sidebar_title'    => '',
            'sidebar_subtitle' => '',
            'center_lat'       => '',
            'center_lng'       => '',
            'zoom'             => '',
            'fit_bounds'       => '',
        );
    }

    /**
     * Réglages enregistrés, complétés par les valeurs par défaut.
     *
     * @return array<string, string>
     */
    public static function get_all() {
        $stored = get_option(self::OPTION_NAME, array());
        return array_merge(self::defaults(), is_array($stored) ? array_intersect_key($stored, self::defaults()) : array());
    }

    /**
     * Nettoyer les données du formulaire.
     *
     * @param mixed $input
     * @return array<string, string>
     */
    public static function sanitize($input) {
        $input = is_array($input) ? $input : array();
        $clean = self::defaults();
        $text  = static function ($key) use ($input) {
            return isset($input[$key]) ? sanitize_text_field((string) $input[$key]) : '';
        };

        foreach (array('place_singular', 'place_plural', 'entity_singular', 'entity_plural', 'sidebar_title', 'sidebar_subtitle') as $key) {
            $clean[$key] = $text($key);
        }
        $clean['place_slug'] = sanitize_title($text('place_slug'));

        foreach (array('center_lat', 'center_lng') as $key) {
            $value       = str_replace(',', '.', $text($key));
            $clean[$key] = is_numeric($value) ? (string) floatval($value) : '';
        }
        $zoom           = $text('zoom');
        $clean['zoom']  = $zoom === '' ? '' : (string) max(1, min(self::MAX_ZOOM, absint($zoom)));
        $fit            = $text('fit_bounds');
        $clean['fit_bounds'] = in_array($fit, array('true', 'false'), true) ? $fit : '';

        return $clean;
    }

    /**
     * Valeurs de carte réglées (non vides), à fusionner sur celles du code.
     *
     * @return array<string, string>
     */
    public static function map_defaults() {
        $settings = self::get_all();
        $values   = array();
        foreach (self::MAP_KEYS as $key) {
            if ($settings[$key] !== '') {
                $values[$key] = $settings[$key];
            }
        }
        return $values;
    }

    /**
     * Slug d'URL réglé, ou '' si aucun.
     *
     * @return string
     */
    public static function place_slug() {
        return self::get_all()['place_slug'];
    }

    /**
     * Libellés principaux du type de contenu, remplacés par les noms réglés.
     *
     * @param array $labels Libellés du code.
     * @return array
     */
    public static function place_labels(array $labels) {
        $settings = self::get_all();
        return self::apply_names($labels, $settings['place_singular'], $settings['place_plural'], array('name_admin_bar'));
    }

    /**
     * Libellés principaux de la taxonomie « entité ».
     *
     * @param array $labels Libellés du code.
     * @return array
     */
    public static function entity_labels(array $labels) {
        $settings = self::get_all();
        return self::apply_names($labels, $settings['entity_singular'], $settings['entity_plural'], array());
    }

    /**
     * Remplacer name, menu_name (pluriel) et singular_name (singulier).
     *
     * @param array    $labels
     * @param string   $singular
     * @param string   $plural
     * @param string[] $singular_keys Autres clés au singulier.
     * @return array
     */
    private static function apply_names(array $labels, $singular, $plural, array $singular_keys) {
        if ($plural !== '') {
            foreach (array('name', 'menu_name') as $key) {
                if (array_key_exists($key, $labels)) {
                    $labels[$key] = $plural;
                }
            }
        }
        if ($singular !== '') {
            foreach (array_merge(array('singular_name'), $singular_keys) as $key) {
                if (array_key_exists($key, $labels)) {
                    $labels[$key] = $singular;
                }
            }
        }
        return $labels;
    }

    /**
     * Un slug modifié change les règles de réécriture : les régénérer à la
     * prochaine requête.
     *
     * @param mixed $old
     * @param mixed $new
     */
    public static function on_update($old, $new) {
        $old_slug = is_array($old) && isset($old['place_slug']) ? $old['place_slug'] : '';
        $new_slug = is_array($new) && isset($new['place_slug']) ? $new['place_slug'] : '';
        if ($old_slug !== $new_slug) {
            Plugin::request_rewrite_flush();
        }
    }
}
