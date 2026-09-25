<?php
/**
 * Outils de texte purs, sans dépendance à WordPress.
 */

namespace MappedPlaces\Support;

if (!defined('ABSPATH')) {
    exit;
}

class Text {

    /** Lettres accentuées (minuscules) et leur équivalent ASCII. */
    const ASCII_FOLD = array(
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae',
        'ç' => 'c',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'ñ' => 'n',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'œ' => 'oe',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'ý' => 'y', 'ÿ' => 'y',
    );

    /** Apostrophes typographiques ramenées à l'apostrophe droite. */
    const APOSTROPHES = array("\u{2019}", "\u{2018}", "\u{02BC}", "\u{2032}", '`');

    /**
     * Forme de comparaison d'un libellé : minuscules, sans accent,
     * apostrophes droites, espaces resserrés. Idempotente.
     *
     * @param mixed $text
     * @return string
     */
    public static function normalize($text) {
        $text = is_scalar($text) ? (string) $text : '';
        $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
        $text = strtr($text, self::ASCII_FOLD);
        $text = str_replace(self::APOSTROPHES, "'", $text);
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
