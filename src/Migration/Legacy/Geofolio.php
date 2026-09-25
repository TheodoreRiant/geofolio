<?php
/**
 * Description de Geofolio 1.x, l'ancien nom de Mapped Places, pour l'import
 * intégré : un site qui tournait sous Geofolio garde ses données sous les
 * anciens identifiants (gfo_place, gfo_*, _gfo_*, geofolio_*). Le cœur les
 * décrit lui-même, comme un compagnon décrirait un autre plugin, dès qu'il
 * en trouve la trace ; un compagnon qui décrit un autre prédécesseur garde
 * la main.
 *
 * @package Mapped Places
 */

namespace MappedPlaces\Migration\Legacy;

use MappedPlaces\Domain\FieldRegistry;
use MappedPlaces\Domain\Schema;

if (!defined('ABSPATH')) {
    exit;
}

final class Geofolio {

    /** Ancien type de contenu des lieux. */
    const POST_TYPE = 'gfo_place';

    /** Ancien préfixe des metas de lieu et de terme. */
    const META_PREFIX = '_gfo_';

    /** Ancien fichier principal du plugin. */
    const PLUGIN = 'geofolio/geofolio.php';

    /** Anciennes options reprises telles quelles (les autres sont des caches ou des journaux). */
    const OPTIONS = array(
        'geofolio_settings'   => 'mapped_places_settings',
        'geofolio_appearance' => 'mapped_places_appearance',
        'geofolio_labels'     => 'mapped_places_labels',
    );

    /** Anciennes taxonomies. */
    const TAXONOMIES = array(
        'gfo_type'          => Schema::TAX_TYPE,
        'gfo_region'        => Schema::TAX_REGION,
        'gfo_service'       => Schema::TAX_SERVICE,
        'gfo_accessibility' => Schema::TAX_ACCESSIBILITY,
        'gfo_entity'        => Schema::TAX_ENTITY,
    );

    /** @var bool|null Trace de Geofolio trouvée, mémorisée pour la requête. */
    private static $detected = null;

    /**
     * Brancher la description avant celle d'un éventuel compagnon (priorité 10).
     */
    public static function register() {
        add_filter(Config::FILTER, array(__CLASS__, 'describe'), 5);
        self::$detected = null;
    }

    /**
     * Filtre mapped_places_legacy_import : décrire Geofolio si rien d'autre
     * n'est décrit et si le site en garde des données.
     *
     * @param mixed $config Description déjà fournie.
     * @return mixed
     */
    public static function describe($config) {
        if (is_array($config) && $config !== array()) {
            return $config;
        }
        return self::detected() ? self::description() : $config;
    }

    /**
     * Le site garde-t-il des données de Geofolio ? Une de ses options, ou un
     * lieu de son type de contenu.
     *
     * @return bool
     */
    public static function detected() {
        if (self::$detected !== null) {
            return self::$detected;
        }
        global $wpdb;
        $found = false;
        foreach (array_keys(self::OPTIONS) as $option) {
            if (get_option($option, null) !== null) {
                $found = true;
                break;
            }
        }
        if (!$found && isset($wpdb)) {
            $found = (bool) $wpdb->get_col($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s LIMIT 1",
                self::POST_TYPE
            ));
        }
        self::$detected = $found;
        return $found;
    }

    /**
     * Oublier la détection mémorisée (tests, ou après l'import).
     */
    public static function forget() {
        self::$detected = null;
    }

    /**
     * Description brute, au format du filtre (voir Config::normalize()).
     *
     * @return array
     */
    public static function description() {
        $post_meta = array();
        foreach (FieldRegistry::fields() as $field) {
            $post_meta[self::META_PREFIX . $field] = $field;
        }
        return array(
            'post_type'         => self::POST_TYPE,
            'taxonomies'        => self::TAXONOMIES,
            'post_meta'         => $post_meta,
            'term_meta'         => array(
                self::META_PREFIX . 'color' => Schema::ENTITY_COLOR_META,
                self::META_PREFIX . 'icon'  => Schema::TYPE_ICON_META,
            ),
            'options'           => self::OPTIONS,
            'elementor_widgets' => array('geofolio_map'),
            'shortcodes'        => array('geofolio'),
            'blocks'            => array('geofolio/map'),
            'plugin'            => self::PLUGIN,
        );
    }
}
