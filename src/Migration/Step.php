<?php
/**
 * Contrat d'une étape de migration.
 */

namespace Geofolio\Migration;

if (!defined('ABSPATH')) {
    exit;
}

interface Step {

    /**
     * @return string Identifiant stable et unique.
     */
    public function id();

    /**
     * Exécuter l'étape. Idempotente.
     *
     * @return array Rapport.
     */
    public function run();
}
