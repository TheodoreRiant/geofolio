<?php
/**
 * Arguments de la route /places : WordPress appelle chaque sanitize_callback
 * avec trois arguments (valeur, requête, nom). Une fonction interne de PHP
 * à un seul paramètre (floatval, intval…) lève alors une ArgumentCountError
 * fatale sur PHP 8.
 */

use PHPUnit\Framework\TestCase;
use Geofolio\Rest\PlacesController;

final class RestArgsTest extends TestCase {

    public function test_aucun_sanitize_callback_n_est_une_fonction_interne_a_un_parametre() {
        foreach (PlacesController::places_args() as $name => $arg) {
            if (!isset($arg['sanitize_callback']) || !is_string($arg['sanitize_callback']) || !function_exists($arg['sanitize_callback'])) {
                continue;
            }
            $function = new ReflectionFunction($arg['sanitize_callback']);
            $this->assertFalse(
                $function->isInternal() && $function->getNumberOfParameters() < 3,
                sprintf('%s : %s() refuse les trois arguments passés par WordPress', $name, $arg['sanitize_callback'])
            );
        }
    }

    public function test_lat_et_lng_restent_des_nombres() {
        $args = PlacesController::places_args();

        $this->assertSame('number', $args['lat']['type']);
        $this->assertSame('number', $args['lng']['type']);
    }
}
