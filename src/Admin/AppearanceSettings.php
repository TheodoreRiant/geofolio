<?php
/**
 * Réglages « Apparence » : couleurs, police et arrondis de la carte, pour
 * tout le site. Ils deviennent des variables --mapl-* sur .mapl-map-container,
 * ajoutées après la feuille de la carte ; un réglage de widget Elementor
 * (sélecteur plus précis) garde le dessus sur sa page.
 *
 * @package Mapped Places
 */

namespace MappedPlaces\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class AppearanceSettings {

    /** Option enregistrée (séparée des réglages de carte : un onglet par option). */
    const OPTION_NAME = 'mapped_places_appearance';

    /** Groupe de réglages du formulaire. */
    const OPTION_GROUP = 'mapped_places_appearance_group';

    /** Police embarquée (Poppins), valeur par défaut. */
    const FONT_BUNDLED = 'bundled';

    /** Police du thème. */
    const FONT_THEME = 'theme';

    /** Arrondi maximal, en pixels. */
    const MAX_RADIUS = 32;

    /**
     * Valeurs par défaut : vides, le CSS du plugin s'applique.
     *
     * @return array<string, string>
     */
    public static function defaults() {
        return array(
            'primary_color' => '',
            'accent_color'  => '',
            'font'          => self::FONT_BUNDLED,
            'radius'        => '',
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

        foreach (array('primary_color', 'accent_color') as $key) {
            $color       = isset($input[$key]) ? sanitize_hex_color(trim((string) $input[$key])) : '';
            $clean[$key] = is_string($color) ? $color : '';
        }

        $font          = isset($input['font']) ? (string) $input['font'] : '';
        $clean['font'] = in_array($font, array(self::FONT_BUNDLED, self::FONT_THEME), true) ? $font : self::FONT_BUNDLED;

        $radius          = isset($input['radius']) ? trim((string) $input['radius']) : '';
        $clean['radius'] = $radius === '' ? '' : (string) min(self::MAX_RADIUS, absint($radius));

        return $clean;
    }

    /**
     * Règle CSS des variables réglées, ou '' si rien n'est réglé.
     *
     * @param array $settings Réglages (voir defaults()).
     * @return string
     */
    public static function css_variables(array $settings) {
        $settings = array_merge(self::defaults(), $settings);
        $vars     = array();

        if ($settings['primary_color'] !== '') {
            $c = $settings['primary_color'];
            $vars['--mapl-primary']       = $c;
            $vars['--mapl-primary-dark']  = 'color-mix(in srgb,' . $c . ' 80%,#000)';
            $vars['--mapl-primary-light'] = 'color-mix(in srgb,' . $c . ' 85%,#fff)';
        }
        if ($settings['accent_color'] !== '') {
            $c = $settings['accent_color'];
            $vars['--mapl-accent']      = $c;
            $vars['--mapl-accent-dark'] = 'color-mix(in srgb,' . $c . ' 85%,#000)';
        }
        if ($settings['font'] === self::FONT_THEME) {
            // « initial » rend la variable invalide : les font-family qui la
            // lisent retombent sur unset et héritent de la police de la page.
            // (« inherit » ferait hériter la variable du parent, donc Poppins.)
            $vars['--mapl-font']       = 'initial';
            $vars['--mapl-font-title'] = 'initial';
        }
        if ($settings['radius'] !== '') {
            $r = (int) $settings['radius'];
            $vars['--mapl-radius-sm'] = round($r * 0.6) . 'px';
            $vars['--mapl-radius']    = $r . 'px';
            $vars['--mapl-radius-lg'] = round($r * 1.4) . 'px';
            $vars['--mapl-radius-xl'] = ($r * 2) . 'px';
        }

        if (!$vars) {
            return '';
        }
        $declarations = '';
        foreach ($vars as $name => $value) {
            $declarations .= $name . ':' . $value . ';';
        }
        return '.mapl-map-container{' . $declarations . '}';
    }

    /**
     * Ajouter les variables réglées après la feuille de la carte (appelé
     * quand les assets de la carte sont chargés).
     */
    public static function add_inline_style() {
        $css = self::css_variables(self::get_all());
        if ($css !== '') {
            wp_add_inline_style('mapped-places', $css);
        }
    }
}
