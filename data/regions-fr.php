<?php
/**
 * Régions françaises par numéro de département (sur deux caractères).
 *
 * Non branchée par défaut : un préréglage la relie au filtre
 * geofolio_import_region pour déduire la région d'un lieu importé.
 */

if (!defined('ABSPATH')) {
    exit;
}

return array(
    // Auvergne-Rhône-Alpes
    '01' => 'Auvergne-Rhône-Alpes',
    '03' => 'Auvergne-Rhône-Alpes',
    '07' => 'Auvergne-Rhône-Alpes',
    '15' => 'Auvergne-Rhône-Alpes',
    '26' => 'Auvergne-Rhône-Alpes',
    '38' => 'Auvergne-Rhône-Alpes',
    '42' => 'Auvergne-Rhône-Alpes',
    '43' => 'Auvergne-Rhône-Alpes',
    '63' => 'Auvergne-Rhône-Alpes',
    '69' => 'Auvergne-Rhône-Alpes',
    '73' => 'Auvergne-Rhône-Alpes',
    '74' => 'Auvergne-Rhône-Alpes',
    // PACA
    '04' => 'PACA',
    '05' => 'PACA',
    '06' => 'PACA',
    '13' => 'PACA',
    '83' => 'PACA',
    '84' => 'PACA',
    // Île-de-France
    '75' => 'Île-de-France',
    '77' => 'Île-de-France',
    '78' => 'Île-de-France',
    '91' => 'Île-de-France',
    '92' => 'Île-de-France',
    '93' => 'Île-de-France',
    '94' => 'Île-de-France',
    '95' => 'Île-de-France',
);
