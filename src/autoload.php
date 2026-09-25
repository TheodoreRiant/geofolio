<?php
/**
 * Chargement automatique PSR-4 des classes MappedPlaces\ depuis src/, sans
 * Composer (aucune dépendance à installer sur l'hébergement).
 */

if (!defined('ABSPATH')) {
    exit;
}

spl_autoload_register(static function ($class) {
    $prefix = 'MappedPlaces\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_readable($file)) {
        require $file;
    }
});
