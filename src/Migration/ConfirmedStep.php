<?php
/**
 * Étape qui ne doit pas tourner d'elle-même : un administrateur la lance
 * après avoir vu ce qu'elle va faire (import d'un ancien plugin).
 *
 * @package Mapped Places
 */

namespace MappedPlaces\Migration;

if (!defined('ABSPATH')) {
    exit;
}

interface ConfirmedStep {

    /**
     * @return bool Vrai tant qu'une confirmation est nécessaire.
     */
    public function requires_confirmation();
}
