<?php
/**
 * Icône d'un type d'après son nom : correspondance exacte, puis partielle
 * (l'un contient l'autre), dans l'ordre du catalogue.
 *
 * @package Geofolio
 */

namespace Geofolio\Migration\Legacy;

if (!defined('ABSPATH')) {
    exit;
}

class IconMatcher {

    /** Apostrophes typographiques ramenées à l'apostrophe droite. */
    const APOSTROPHES = array("\u{2019}", "\u{2018}", "\u{02BC}", '`');

    /**
     * Clé de comparaison : sans accents, en minuscules, apostrophes droites,
     * espaces réduits (« Centre Éducatif Fermé » = « centre educatif ferme »).
     *
     * @param string $name
     * @return string
     */
    public static function key($name) {
        $key = remove_accents(str_replace(self::APOSTROPHES, "'", (string) $name));
        return trim(preg_replace('/\s+/u', ' ', mb_strtolower($key, 'UTF-8')));
    }

    /**
     * @param array<string, string> $catalog Nom (clé de comparaison) => icône.
     * @param string                $name    Nom du type.
     * @return string Clé d'icône, ou '' si aucune correspondance.
     */
    public static function match(array $catalog, $name) {
        $key = self::key($name);
        if ($key === '') {
            return '';
        }
        if (isset($catalog[$key])) {
            return $catalog[$key];
        }
        foreach ($catalog as $known => $icon) {
            if (strpos($key, (string) $known) !== false || strpos((string) $known, $key) !== false) {
                return $icon;
            }
        }
        return '';
    }
}
