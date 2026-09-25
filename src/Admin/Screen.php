<?php
/**
 * Écrans d'administration du plugin : les avis (admin_notices) n'apparaissent
 * que sur ceux-là, jamais sur le reste du tableau de bord.
 *
 * @package Mapped Places
 */

namespace MappedPlaces\Admin;

use MappedPlaces\Domain\Schema;

if (!defined('ABSPATH')) {
    exit;
}

final class Screen {

    /**
     * L'écran courant appartient-il au plugin ? Liste et édition des lieux,
     * termes de leurs taxonomies, et sous-pages du menu Lieux (réglages,
     * import) : WordPress leur donne toutes le type de contenu des lieux.
     *
     * @return bool
     */
    public static function is_plugin_screen() {
        if (!function_exists('get_current_screen')) {
            return false;
        }
        $screen = get_current_screen();
        return $screen instanceof \WP_Screen && $screen->post_type === Schema::POST_TYPE;
    }
}
