<?php
/**
 * Registre des champs d'un lieu : source unique des clés de meta et de leur
 * nettoyage, partagée par les meta boxes, l'API REST, l'import et
 * l'enregistrement des metas (éditeur de blocs).
 */

namespace Geofolio\Domain;

if (!defined('ABSPATH')) {
    exit;
}

final class FieldRegistry {

    /** Préfixe des champs de formulaire des meta boxes. */
    const FORM_PREFIX = 'geofolio_';

    /** Champ => [clé de meta, nettoyage de la valeur]. */
    const FIELDS = array(
        'address'       => array('_gfo_address', 'sanitize_text_field'),
        'postal_code'   => array('_gfo_postal_code', 'sanitize_text_field'),
        'city'          => array('_gfo_city', 'sanitize_text_field'),
        'latitude'      => array('_gfo_latitude', array(__CLASS__, 'sanitize_coordinate')),
        'longitude'     => array('_gfo_longitude', array(__CLASS__, 'sanitize_coordinate')),
        'phone'         => array('_gfo_phone', 'sanitize_text_field'),
        'email'         => array('_gfo_email', 'sanitize_email'),
        'website'       => array('_gfo_website', 'esc_url_raw'),
        'manager'       => array('_gfo_manager', 'sanitize_text_field'),
        'people'        => array('_gfo_people', array(People::class, 'sanitize_json')),
        'opening_hours' => array('_gfo_opening_hours', 'sanitize_textarea_field'),
        'gallery'       => array('_gfo_gallery', array(__CLASS__, 'sanitize_gallery_json')),
    );

    /**
     * @return string[] Noms des champs.
     */
    public static function fields() {
        return array_keys(self::FIELDS);
    }

    /**
     * Clé de meta d'un champ.
     *
     * @param string $field
     * @return string
     * @throws \InvalidArgumentException Champ inconnu.
     */
    public static function meta_key($field) {
        if (!isset(self::FIELDS[$field])) {
            throw new \InvalidArgumentException('Unknown place field: ' . esc_html((string) $field));
        }
        return self::FIELDS[$field][0];
    }

    /**
     * Nettoyer la valeur d'un champ.
     *
     * @param string $field
     * @param mixed  $value
     * @return string
     */
    public static function sanitize($field, $value) {
        self::meta_key($field);
        return (string) call_user_func(self::FIELDS[$field][1], $value);
    }

    /**
     * Nettoyage de chaque meta : clé de meta => rappel (valeur seule).
     *
     * @return array<string, callable>
     */
    public static function sanitizers() {
        $sanitizers = array();
        foreach (self::FIELDS as $field) {
            $sanitizers[$field[0]] = $field[1];
        }
        return $sanitizers;
    }

    /**
     * Valeur enregistrée d'un champ.
     *
     * @param int    $post_id
     * @param string $field
     * @return string
     */
    public static function get($post_id, $field) {
        return (string) get_post_meta($post_id, self::meta_key($field), true);
    }

    /**
     * Nom (et id) du champ de formulaire des meta boxes.
     *
     * @param string $field
     * @return string
     */
    public static function form_field($field) {
        return self::FORM_PREFIX . $field;
    }

    /**
     * Coordonnée décimale en chaîne, ou chaîne vide si la saisie n'est pas
     * un nombre. La virgule décimale (saisie à la française) est acceptée.
     *
     * @param mixed $value
     * @return string
     */
    public static function sanitize_coordinate($value) {
        if (!is_scalar($value)) {
            return '';
        }
        $normalized = str_replace(',', '.', trim((string) $value));
        return is_numeric($normalized) ? (string) floatval($normalized) : '';
    }

    /**
     * Galerie : JSON d'IDs de pièces jointes images (voir parse_gallery_ids).
     *
     * @param mixed $value
     * @return string
     */
    public static function sanitize_gallery_json($value) {
        return wp_json_encode(self::parse_gallery_ids(is_string($value) ? $value : ''));
    }

    /**
     * Lire une liste d'IDs de photos depuis sa représentation JSON.
     *
     * Utilisée à l'affichage ET à l'enregistrement, pour que les deux côtés
     * appliquent exactement les mêmes règles. Ne garde que des pièces jointes
     * de type image, dédoublonne, et préserve l'ordre choisi par l'utilisateur
     * (la première photo sert de couverture).
     *
     * @param mixed $raw JSON attendu : un tableau d'entiers.
     * @return int[] Liste nettoyée.
     */
    public static function parse_gallery_ids($raw) {
        if (!is_string($raw) || $raw === '') {
            return array();
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return array();
        }

        $ids = array();
        foreach ($decoded as $candidate) {
            $attachment_id = absint($candidate);
            if ($attachment_id
                && !in_array($attachment_id, $ids, true)
                && wp_attachment_is_image($attachment_id)) {
                $ids[] = $attachment_id;
            }
        }

        return $ids;
    }
}
