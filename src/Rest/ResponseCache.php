<?php
/**
 * Cache des réponses REST publiques (liste des lieux, filtres).
 *
 * La carte charge toute la liste d'un coup : sans cache, chaque visite
 * relit tous les lieux, leurs metas et leurs termes. Les réponses sont
 * gardées en transient ; une écriture sur un lieu, un terme ou les réglages
 * incrémente une génération qui périme toutes les clés d'un coup, sans avoir
 * à les énumérer.
 *
 * Hors cache : la recherche libre et la proximité (une clé par visiteur),
 * et la fiche d'un lieu (sa visibilité dépend de l'utilisateur connecté).
 */

namespace Geofolio\Rest;

use Geofolio\Admin\SettingsPage;
use Geofolio\Domain\Schema;

if (!defined('ABSPATH')) {
    exit;
}

class ResponseCache {

    /** Préfixe des transients (supprimés par uninstall.php avec geofolio_*). */
    const PREFIX = 'geofolio_rest_';

    /** Option portant la génération courante. */
    const GENERATION_OPTION = 'geofolio_rest_cache_generation';

    /** Durée de vie d'une réponse, en secondes (12 h) : filet si une invalidation manque. */
    const TTL = 43200;

    /** Paramètres qui rendent une requête propre au visiteur. */
    const UNCACHEABLE_PARAMS = array('search', 'lat', 'lng');

    /** Metas techniques de WordPress, sans effet sur la réponse. */
    const IGNORED_META_KEYS = array('_edit_lock', '_edit_last');

    /**
     * Clé de cache d'une réponse.
     *
     * @param string $route  Nom de la route (places, filters).
     * @param array  $params Paramètres de la requête.
     * @return string
     */
    public static function key($route, array $params) {
        ksort($params);
        $signature = implode('|', array(
            self::generation(),
            get_locale(),
            (string) $route,
            wp_json_encode($params),
        ));
        return self::PREFIX . md5($signature);
    }

    /**
     * La requête peut-elle être servie depuis le cache ?
     *
     * @param array $params Paramètres de la requête.
     * @return bool
     */
    public static function is_cacheable(array $params) {
        foreach (self::UNCACHEABLE_PARAMS as $name) {
            if (isset($params[$name]) && $params[$name] !== '' && $params[$name] !== null) {
                return false;
            }
        }
        return true;
    }

    /**
     * Réponse en cache, ou construite puis gardée.
     *
     * @param string   $key   Clé (voir key()).
     * @param callable $build Construit la réponse (tableau sérialisable).
     * @return mixed
     */
    public static function remember($key, callable $build) {
        $cached = get_transient($key);
        if ($cached !== false) {
            return $cached;
        }
        $value = call_user_func($build);
        set_transient($key, $value, self::TTL);
        return $value;
    }

    /**
     * Périmer toutes les réponses gardées.
     */
    public static function flush() {
        update_option(self::GENERATION_OPTION, self::generation() + 1, true);
    }

    /**
     * Brancher l'invalidation sur les écritures qui changent une réponse.
     */
    public static function register() {
        $flush = array(__CLASS__, 'flush');

        add_action('save_post_' . Schema::POST_TYPE, $flush);
        add_action('deleted_post', array(__CLASS__, 'on_post_change'));
        add_action('set_object_terms', array(__CLASS__, 'on_post_change'));
        add_action('update_option_' . SettingsPage::OPTION_NAME, $flush);

        foreach (array('added_post_meta', 'updated_post_meta', 'deleted_post_meta') as $hook) {
            add_action($hook, array(__CLASS__, 'on_post_meta_change'), 10, 3);
        }
        foreach (array('created_term', 'edited_term', 'delete_term') as $hook) {
            add_action($hook, array(__CLASS__, 'on_term_change'), 10, 3);
        }
        add_action('updated_term_meta', array(__CLASS__, 'on_term_meta_change'), 10, 2);
    }

    /**
     * Suppression d'un post ou changement de ses termes.
     *
     * @param int $object_id
     */
    public static function on_post_change($object_id) {
        if (get_post_type($object_id) === Schema::POST_TYPE) {
            self::flush();
        }
    }

    /**
     * Meta d'un post ajoutée, modifiée ou supprimée.
     *
     * @param int|int[] $meta_id
     * @param int       $object_id
     * @param string    $meta_key
     */
    public static function on_post_meta_change($meta_id, $object_id, $meta_key) {
        if (in_array($meta_key, self::IGNORED_META_KEYS, true)) {
            return;
        }
        self::on_post_change($object_id);
    }

    /**
     * Terme créé, modifié ou supprimé.
     *
     * @param int    $term_id
     * @param int    $tt_id
     * @param string $taxonomy
     */
    public static function on_term_change($term_id, $tt_id, $taxonomy) {
        if (in_array($taxonomy, self::taxonomies(), true)) {
            self::flush();
        }
    }

    /**
     * Meta d'un terme modifiée (couleur d'entité, icône de type).
     *
     * @param int $meta_id
     * @param int $term_id
     */
    public static function on_term_meta_change($meta_id, $term_id) {
        $term = get_term($term_id);
        if ($term && !is_wp_error($term)) {
            self::on_term_change($term_id, $term->term_taxonomy_id, $term->taxonomy);
        }
    }

    /**
     * Taxonomies des lieux.
     *
     * @return string[]
     */
    private static function taxonomies() {
        return array(
            Schema::TAX_TYPE, Schema::TAX_REGION, Schema::TAX_SERVICE,
            Schema::TAX_ACCESSIBILITY, Schema::TAX_ENTITY,
        );
    }

    /**
     * Génération courante.
     *
     * @return int
     */
    private static function generation() {
        return (int) get_option(self::GENERATION_OPTION, 0);
    }
}
