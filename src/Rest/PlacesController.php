<?php
/**
 * API REST publique des lieux (espace de noms mapped-places/v1) :
 *
 *   GET places          liste, filtrable par taxonomie, recherche, proximité
 *   GET places/{id}     fiche complète
 *   GET filters         termes utilisables comme filtres, couleur par défaut
 */

namespace MappedPlaces\Rest;

use MappedPlaces\Domain\FieldRegistry;
use MappedPlaces\Domain\Icons;
use MappedPlaces\Domain\Schema;
use MappedPlaces\Domain\Taxonomies;
use MappedPlaces\Map\Defaults;
use MappedPlaces\Support\Geo;

if (!defined('ABSPATH')) {
    exit;
}

class PlacesController {

    /** Espace de noms des routes. */
    const REST_NAMESPACE = 'mapped-places/v1';

    /** Paramètre de requête => taxonomie filtrée (slugs séparés par des virgules). */
    const TAXONOMY_PARAMS = array(
        'type'          => Schema::TAX_TYPE,
        'region'        => Schema::TAX_REGION,
        'service'       => Schema::TAX_SERVICE,
        'accessibility' => Schema::TAX_ACCESSIBILITY,
    );

    /** Champs cherchés par le paramètre search. */
    const SEARCH_FIELDS = array('city', 'postal_code', 'address');

    /** Rayon de recherche par défaut, en km. */
    const DEFAULT_RADIUS = 50;

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Enregistrer les routes.
     */
    public function register_routes() {
        register_rest_route(self::REST_NAMESPACE, '/places', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_places'),
            'permission_callback' => '__return_true',
            'args'                => self::places_args(),
        ));

        register_rest_route(self::REST_NAMESPACE, '/places/(?P<id>\d+)', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_place'),
            'permission_callback' => '__return_true',
            'args'                => array(
                'id' => array(
                    'validate_callback' => static function ($param) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));

        register_rest_route(self::REST_NAMESPACE, '/filters', array(
            'methods'             => 'GET',
            'callback'            => array($this, 'get_filters'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Arguments de la route places.
     *
     * @return array
     */
    public static function places_args() {
        $args = array();
        foreach (array_keys(self::TAXONOMY_PARAMS) as $param) {
            $args[$param] = array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'description'       => 'Comma-separated term slugs',
            );
        }
        // Pas de sanitize_callback sur lat/lng : WordPress le rappelle avec trois
        // arguments, que floatval() refuse sur PHP 8 ; le type « number » suffit,
        // le schéma valide et convertit la valeur.
        return array_merge($args, array(
            'lat'    => array('type' => 'number', 'description' => 'Latitude for a proximity search'),
            'lng'    => array('type' => 'number', 'description' => 'Longitude for a proximity search'),
            'radius' => array('type' => 'integer', 'default' => self::DEFAULT_RADIUS, 'sanitize_callback' => 'absint', 'description' => 'Search radius in km'),
            'search' => array('type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'description' => 'Text search (city, postal code, address)'),
        ));
    }

    /**
     * Liste des lieux publiés ayant des coordonnées.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function get_places($request) {
        $params = self::request_params($request);
        if (!ResponseCache::is_cacheable($params)) {
            return rest_ensure_response(self::build_places($request));
        }
        return rest_ensure_response(ResponseCache::remember(
            ResponseCache::key('places', $params),
            static function () use ($request) {
                return self::build_places($request);
            }
        ));
    }

    /**
     * Paramètres de la route places qui influencent la réponse.
     *
     * @param \WP_REST_Request $request
     * @return array
     */
    public static function request_params($request) {
        $params = array();
        foreach (array_keys(self::places_args()) as $name) {
            $params[$name] = $request->get_param($name);
        }
        return $params;
    }

    /**
     * Corps de la réponse places.
     *
     * @param \WP_REST_Request $request
     * @return array
     */
    private static function build_places($request) {
        $query = new \WP_Query(self::query_args($request));

        $user_lat = $request->get_param('lat');
        $user_lng = $request->get_param('lng');
        $radius   = $request->get_param('radius') ?: self::DEFAULT_RADIUS;

        $places = array();
        foreach ($query->posts as $post) {
            $lat = floatval(FieldRegistry::get($post->ID, 'latitude'));
            $lng = floatval(FieldRegistry::get($post->ID, 'longitude'));
            if (!$lat || !$lng) {
                continue;
            }

            $distance = null;
            if ($user_lat && $user_lng) {
                $distance = Geo::distance_km($user_lat, $user_lng, $lat, $lng);
                if ($distance > $radius) {
                    continue;
                }
            }
            $places[] = PlaceMapper::summary($post->ID, $distance);
        }

        if ($user_lat && $user_lng) {
            usort($places, static function ($a, $b) {
                return $a['distance'] <=> $b['distance'];
            });
        }

        return array(
            'count'  => count($places),
            'places' => $places,
            // Libellé et icône de chaque type : la carte ne code rien en dur.
            'types'  => Icons::type_catalog(),
        );
    }

    /**
     * Arguments de WP_Query pour la route places.
     *
     * @param \WP_REST_Request $request
     * @return array
     */
    public static function query_args($request) {
        $args = array(
            'post_type'      => Schema::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
        );

        $tax_query = array('relation' => 'AND');
        foreach (self::TAXONOMY_PARAMS as $param => $taxonomy) {
            $value = (string) $request->get_param($param);
            if ($value !== '') {
                $tax_query[] = array(
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => explode(',', $value),
                );
            }
        }
        if (count($tax_query) > 1) {
            $args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- filtre par taxonomie demandé par le visiteur.
        }

        $search = (string) $request->get_param('search');
        if ($search !== '') {
            $args['meta_query'] = array('relation' => 'OR'); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- recherche sur les metas d'adresse.
            foreach (self::SEARCH_FIELDS as $field) {
                $args['meta_query'][] = array(
                    'key'     => FieldRegistry::meta_key($field),
                    'value'   => $search,
                    'compare' => 'LIKE',
                );
            }
        }

        return $args;
    }

    /**
     * Termes utilisables comme filtres.
     *
     * @return \WP_REST_Response
     */
    public function get_filters() {
        return rest_ensure_response(ResponseCache::remember(
            ResponseCache::key('filters', array()),
            array(__CLASS__, 'build_filters')
        ));
    }

    /**
     * Corps de la réponse filters.
     *
     * @return array
     */
    public static function build_filters() {
        $entity_terms = get_terms(array('taxonomy' => Schema::TAX_ENTITY, 'hide_empty' => false));
        $entities     = array();
        if (is_array($entity_terms)) {
            foreach ($entity_terms as $term) {
                $entities[] = array_merge(PlaceMapper::describe_entity($term), array('count' => $term->count));
            }
        }

        return array(
            'types'         => self::describe_filter_types(Taxonomies::get_filter_terms(Schema::TAX_TYPE)),
            'regions'       => Taxonomies::get_filter_terms(Schema::TAX_REGION),
            'services'      => Taxonomies::get_filter_terms(Schema::TAX_SERVICE),
            'accessibility' => Taxonomies::get_filter_terms(Schema::TAX_ACCESSIBILITY),
            'entities'      => $entities,
            'default_color' => Defaults::color(),
        );
    }

    /**
     * Compléter les types de la route filters avec leur libellé et leur icône.
     *
     * @param array[] $terms Retour de Taxonomies::get_filter_terms().
     * @return array[]
     */
    public static function describe_filter_types(array $terms) {
        $described = array();
        foreach (Icons::type_catalog() as $type) {
            $described[$type['slug']] = $type;
        }
        return array_map(static function ($term) use ($described) {
            $extra = isset($described[$term['slug']])
                ? $described[$term['slug']]
                : array('label' => $term['name'], 'icon' => Icons::FALLBACK, 'path' => Icons::path(Icons::FALLBACK));
            return array_merge($term, array(
                'label' => $extra['label'],
                'icon'  => $extra['icon'],
                'path'  => $extra['path'],
            ));
        }, $terms);
    }

    /**
     * Fiche complète d'un lieu.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_place($request) {
        $post = get_post((int) $request->get_param('id'));
        if (!PlaceMapper::is_visible($post)) {
            return new \WP_Error('not_found', __('Place not found', 'mapped-places'), array('status' => 404));
        }
        return rest_ensure_response(PlaceMapper::detail($post));
    }
}
