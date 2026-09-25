<?php
/**
 * Correspondance entre colonnes d'un CSV et champs d'un lieu.
 *
 * Les noms de colonnes sont comparés sous leur forme normalisée
 * (Text::normalize : sans accent ni majuscule). Le cœur reconnaît
 * des noms génériques en anglais et en français ; un préréglage ajoute ceux
 * de son propre fichier par le filtre mapped_places_import_columns.
 */

namespace MappedPlaces\Import;

use MappedPlaces\Support\Text;

if (!defined('ABSPATH')) {
    exit;
}

class CsvMapping {

    /** Champs qu'une ligne peut renseigner. */
    const FIELDS = array(
        'name', 'description', 'capacity', 'audience', 'type', 'type_icon', 'service', 'accessibility',
        'region', 'entity', 'entity_color', 'department', 'address', 'postal_code', 'city',
        'latitude', 'longitude', 'phone', 'email', 'website', 'manager', 'opening_hours',
        'image', 'gallery',
    );

    /** Colonne normalisée => champ. */
    const COLUMNS = array(
        'name'          => 'name',
        'nom'           => 'name',
        'title'         => 'name',
        'titre'         => 'name',
        'description'   => 'description',
        'capacity'      => 'capacity',
        'audience'      => 'audience',
        'public'        => 'audience',
        'excerpt'       => 'audience',
        'extrait'       => 'audience',
        'capacite'      => 'capacity',
        'type'          => 'type',
        'type icon'     => 'type_icon',
        'type_icon'     => 'type_icon',
        'icon'          => 'type_icon',
        'icone'         => 'type_icon',
        'icone du type' => 'type_icon',
        'service'       => 'service',
        'services'      => 'service',
        'accessibility' => 'accessibility',
        'accessibilite' => 'accessibility',
        'region'        => 'region',
        'entity'        => 'entity',
        'entite'        => 'entity',
        'entity color'  => 'entity_color',
        'entity colour' => 'entity_color',
        'entity_color'  => 'entity_color',
        'couleur'       => 'entity_color',
        'couleur de l\'entite' => 'entity_color',
        'image'         => 'image',
        'photo'         => 'image',
        'cover'         => 'image',
        'image a la une' => 'image',
        'gallery'       => 'gallery',
        'galerie'       => 'gallery',
        'photos'        => 'gallery',
        'department'    => 'department',
        'departement'   => 'department',
        'dep'           => 'department',
        'address'       => 'address',
        'adresse'       => 'address',
        'postal code'   => 'postal_code',
        'postal_code'   => 'postal_code',
        'postcode'      => 'postal_code',
        'zip'           => 'postal_code',
        'code postal'   => 'postal_code',
        'city'          => 'city',
        'ville'         => 'city',
        'latitude'      => 'latitude',
        'lat'           => 'latitude',
        'longitude'     => 'longitude',
        'lng'           => 'longitude',
        'lon'           => 'longitude',
        'phone'         => 'phone',
        'telephone'     => 'phone',
        'email'         => 'email',
        'e-mail'        => 'email',
        'courriel'      => 'email',
        'website'       => 'website',
        'site web'      => 'website',
        'url'           => 'website',
        'manager'       => 'manager',
        'responsable'   => 'manager',
        'opening hours' => 'opening_hours',
        'opening_hours' => 'opening_hours',
        'horaires'      => 'opening_hours',
    );

    /**
     * Colonnes reconnues après filtre : colonne normalisée => champ connu.
     *
     * @return array<string, string>
     */
    public static function columns() {
        $columns = apply_filters('mapped_places_import_columns', self::COLUMNS);
        $valid   = array();
        foreach ((array) $columns as $column => $field) {
            if (is_string($column) && in_array($field, self::FIELDS, true)) {
                $valid[Text::normalize($column)] = $field;
            }
        }
        return $valid;
    }

    /**
     * Champs d'une ligne CSV : pour chaque champ, la première colonne non
     * vide qui lui correspond. Les colonnes inconnues sont ignorées.
     *
     * @param array<string, string> $row En-tête => valeur.
     * @return array<string, string>
     */
    public static function map_row(array $row) {
        $columns = self::columns();
        $fields  = array();
        foreach ($row as $header => $value) {
            $column = Text::normalize($header);
            $value  = trim((string) $value);
            if ($value === '' || !isset($columns[$column]) || isset($fields[$columns[$column]])) {
                continue;
            }
            $fields[$columns[$column]] = $value;
        }
        return $fields;
    }
}
