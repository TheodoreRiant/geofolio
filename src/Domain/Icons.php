<?php
/**
 * Bibliothèque d'icônes génériques des types de lieux, et résolution de
 * l'icône et du libellé d'un type.
 *
 * Icône d'un type : meta de terme _type_icon si elle désigne une icône
 * connue, sinon le filtre mapped_places_type_icon (un préréglage y fournit un
 * mapping sans toucher aux termes), sinon l'épingle.
 *
 * Tracés : contenu d'un <svg viewBox="0 0 24 24"> à traits (stroke).
 */

namespace MappedPlaces\Domain;

if (!defined('ABSPATH')) {
    exit;
}

class Icons {

    /** Meta de terme portant la clé d'icône d'un type. */
    const TERM_META = Schema::TYPE_ICON_META;

    /** Icône de repli. */
    const FALLBACK = 'pin';

    /** Taxonomie des types de lieux. */
    const TAXONOMY = Schema::TAX_TYPE;

    /** Clé d'icône : minuscules, chiffres, tirets. */
    const KEY_PATTERN = '/^[a-z0-9-]+$/';

    /** Formes SVG autorisées dans un tracé, avec leurs attributs. */
    const ALLOWED_SHAPES = array(
        'path'     => array('d' => true),
        'circle'   => array('cx' => true, 'cy' => true, 'r' => true),
        'ellipse'  => array('cx' => true, 'cy' => true, 'rx' => true, 'ry' => true),
        'line'     => array('x1' => true, 'y1' => true, 'x2' => true, 'y2' => true),
        'polyline' => array('points' => true),
        'polygon'  => array('points' => true),
        'rect'     => array('x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true),
    );

    /** Icônes du cœur : clé => tracé. */
    const LIBRARY = array(
        'pin'          => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>',
        'home'         => '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'home-arch'    => '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path d="M9 22v-4a3 3 0 016 0v4"/>',
        'home-heart'   => '<path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78"/>',
        'building'     => '<rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="9" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
        'alert'        => '<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'sun'          => '<circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>',
        'heart'        => '<path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/>',
        'people'       => '<path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>',
        'person'       => '<path d="M4 21v-2a4 4 0 014-4h8a4 4 0 014 4v2"/><circle cx="12" cy="7" r="4"/>',
        'clipboard'    => '<path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M9 14l2 2 4-4"/>',
        'search'       => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'map'          => '<polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"/><line x1="8" y1="2" x2="8" y2="18"/><line x1="16" y1="6" x2="16" y2="22"/>',
        'shield'       => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
        'shield-alert' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M12 8v4"/><path d="M12 16h.01"/>',
        'tool'         => '<path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/>',
        'book'         => '<path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>',
        'restaurant'   => '<path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 002-2V2"/><path d="M7 2v20"/><path d="M21 15V2a5 5 0 00-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/>',
        'truck'        => '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
        'office'       => '<rect x="4" y="2" width="16" height="20" rx="2"/><line x1="9" y1="22" x2="9" y2="18"/><line x1="15" y1="22" x2="15" y2="18"/><line x1="8" y1="6" x2="8" y2="6.01"/><line x1="12" y1="6" x2="12" y2="6.01"/><line x1="16" y1="6" x2="16" y2="6.01"/><line x1="8" y1="10" x2="8" y2="10.01"/><line x1="12" y1="10" x2="12" y2="10.01"/><line x1="16" y1="10" x2="16" y2="10.01"/><line x1="8" y1="14" x2="8" y2="14.01"/><line x1="12" y1="14" x2="12" y2="14.01"/><line x1="16" y1="14" x2="16" y2="14.01"/>',
        'star'         => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        'flag'         => '<path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/>',
    );

    /**
     * Icônes disponibles, après filtre : clé => tracé nettoyé. Les entrées
     * dont la clé ou le tracé sont invalides sont écartées.
     *
     * @return array<string, string>
     */
    public static function all() {
        $icons = apply_filters('mapped_places_icons', self::LIBRARY);
        $valid = array();
        foreach ((array) $icons as $key => $path) {
            if (!is_string($key) || !preg_match(self::KEY_PATTERN, $key) || !is_string($path)) {
                continue;
            }
            $clean = trim(wp_kses($path, self::ALLOWED_SHAPES));
            if ($clean !== '') {
                $valid[$key] = $clean;
            }
        }
        if (!isset($valid[self::FALLBACK])) {
            $valid[self::FALLBACK] = self::LIBRARY[self::FALLBACK];
        }
        return $valid;
    }

    /**
     * @param mixed $key
     * @return bool La clé désigne une icône disponible.
     */
    public static function is_valid($key) {
        return is_string($key) && array_key_exists($key, self::all());
    }

    /**
     * Tracé d'une icône, épingle si la clé est inconnue.
     *
     * @param string $key
     * @return string
     */
    public static function path($key) {
        $icons = self::all();
        return isset($icons[$key]) ? $icons[$key] : $icons[self::FALLBACK];
    }

    /**
     * Clé d'icône d'un type : meta, puis filtre, puis épingle.
     *
     * @param \WP_Term $term
     * @return string
     */
    public static function term_icon($term) {
        $meta = get_term_meta($term->term_id, self::TERM_META, true);
        if (self::is_valid($meta)) {
            return $meta;
        }
        $filtered = apply_filters('mapped_places_type_icon', self::FALLBACK, $term->slug, self::plain($term->name));
        return self::is_valid($filtered) ? $filtered : self::FALLBACK;
    }

    /**
     * Libellé affiché d'un type (le filtre sert aux acronymes, à la casse).
     *
     * @param \WP_Term $term
     * @return string
     */
    public static function term_label($term) {
        $label = apply_filters('mapped_places_type_label', self::plain($term->name), $term);
        return is_string($label) && $label !== '' ? $label : self::plain($term->name);
    }

    /**
     * Description publique d'un type, telle qu'exposée par l'API.
     *
     * @param \WP_Term $term
     * @return array{slug: string, name: string, label: string, icon: string, path: string}
     */
    public static function describe_term($term) {
        $icon = self::term_icon($term);
        return array(
            'slug'  => (string) $term->slug,
            'name'  => self::plain($term->name),
            'label' => self::term_label($term),
            'icon'  => $icon,
            'path'  => self::path($icon),
        );
    }

    /**
     * Types utilisés par au moins un lieu, décrits, dans l'ordre des termes
     * (un filtre peut le réordonner).
     *
     * @return array[]
     */
    public static function type_catalog() {
        $terms = get_terms(array('taxonomy' => self::TAXONOMY, 'hide_empty' => true));
        if (!is_array($terms)) {
            return array();
        }
        $catalog  = array_map(array(__CLASS__, 'describe_term'), $terms);
        $filtered = apply_filters('mapped_places_type_catalog', $catalog);
        return is_array($filtered) ? array_values($filtered) : $catalog;
    }

    /**
     * Nom de terme en texte brut (WordPress stocke & sous la forme &amp;).
     *
     * @param string $text
     * @return string
     */
    private static function plain($text) {
        return html_entity_decode((string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
