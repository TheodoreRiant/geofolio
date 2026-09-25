<?php
/**
 * Configuration de l'import depuis un ancien plugin de carte, fournie par le
 * filtre mapped_places_legacy_import. Le cœur ne connaît aucun ancien plugin :
 * un plugin compagnon décrit le sien (noms de type de contenu, taxonomies,
 * metas, options, widget, shortcode, icônes, réglages).
 *
 * @package Mapped Places
 */

namespace MappedPlaces\Migration\Legacy;

use MappedPlaces\Admin\AppearanceSettings;
use MappedPlaces\Admin\LabelsSettings;
use MappedPlaces\Domain\FieldRegistry;
use MappedPlaces\Domain\Schema;

if (!defined('ABSPATH')) {
    exit;
}

class Config {

    /** Filtre qui décrit l'ancien plugin. */
    const FILTER = 'mapped_places_legacy_import';

    /** Clé d'icône valide. */
    const ICON_KEY_PATTERN = '/^[a-z0-9-]+$/';

    /**
     * Configuration normalisée, ou tableau vide si aucun ancien plugin n'est
     * décrit.
     *
     * @return array
     */
    public static function get() {
        $raw = apply_filters('mapped_places_legacy_import', array());
        if (!is_array($raw) || $raw === array()) {
            return array();
        }
        return self::normalize($raw);
    }

    /**
     * Ne garder que des valeurs de type attendu, visant des identifiants
     * Mapped Places existants.
     *
     * @param array $raw
     * @return array
     */
    public static function normalize(array $raw) {
        $taxonomies = array(Schema::TAX_TYPE, Schema::TAX_REGION, Schema::TAX_SERVICE, Schema::TAX_ACCESSIBILITY, Schema::TAX_ENTITY);
        $term_meta  = array(Schema::ENTITY_COLOR_META, Schema::TYPE_ICON_META);

        return array(
            'post_type'         => sanitize_key((string) ($raw['post_type'] ?? '')),
            'taxonomies'        => self::map($raw['taxonomies'] ?? array(), static function ($new) use ($taxonomies) {
                return in_array($new, $taxonomies, true);
            }),
            'post_meta'         => self::map($raw['post_meta'] ?? array(), static function ($field) {
                return in_array($field, FieldRegistry::fields(), true);
            }),
            'term_meta'         => self::map($raw['term_meta'] ?? array(), static function ($new) use ($term_meta) {
                return in_array($new, $term_meta, true);
            }),
            'options'           => self::map($raw['options'] ?? array(), static function ($new) {
                return strpos($new, 'mapped_places_') === 0;
            }),
            'elementor_widgets' => self::strings($raw['elementor_widgets'] ?? array()),
            'shortcodes'        => self::strings($raw['shortcodes'] ?? array()),
            'blocks'            => self::strings($raw['blocks'] ?? array()),
            'delete_post_meta'  => self::strings($raw['delete_post_meta'] ?? array()),
            'type_icons'        => self::type_icons($raw['type_icons'] ?? array()),
            'manager_role'      => sanitize_text_field((string) ($raw['manager_role'] ?? '')),
            'place_slug'        => sanitize_title((string) ($raw['place_slug'] ?? '')),
            'labels'            => self::subset($raw['labels'] ?? array(), LabelsSettings::defaults()),
            'appearance'        => self::subset($raw['appearance'] ?? array(), AppearanceSettings::defaults()),
            'plugin'            => (string) ($raw['plugin'] ?? ''),
        );
    }

    /**
     * Table ancien nom => nouveau, cibles validées.
     *
     * @param mixed    $value
     * @param callable $is_valid_target
     * @return array<string, string>
     */
    private static function map($value, callable $is_valid_target) {
        $clean = array();
        foreach (is_array($value) ? $value : array() as $old => $new) {
            if (is_string($old) && $old !== '' && is_string($new) && $is_valid_target($new)) {
                $clean[$old] = $new;
            }
        }
        return $clean;
    }

    /**
     * Liste de chaînes non vides.
     *
     * @param mixed $value
     * @return string[]
     */
    private static function strings($value) {
        return array_values(array_filter(is_array($value) ? $value : array(), static function ($item) {
            return is_string($item) && $item !== '';
        }));
    }

    /**
     * Catalogue nom de type => clé d'icône, dans l'ordre fourni.
     *
     * @param mixed $value
     * @return array<string, string>
     */
    private static function type_icons($value) {
        $clean = array();
        foreach (is_array($value) ? $value : array() as $name => $icon) {
            if (is_string($name) && is_string($icon) && preg_match(self::ICON_KEY_PATTERN, $icon)) {
                $clean[IconMatcher::key($name)] = $icon;
            }
        }
        return $clean;
    }

    /**
     * Valeurs d'un réglage connu, en chaînes.
     *
     * @param mixed $value
     * @param array $known Clés admises.
     * @return array<string, string>
     */
    private static function subset($value, array $known) {
        $clean = array();
        foreach (is_array($value) ? array_intersect_key($value, $known) : array() as $key => $item) {
            if (is_scalar($item)) {
                $clean[$key] = (string) $item;
            }
        }
        return $clean;
    }
}
