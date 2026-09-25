<?php
/**
 * Identifiants stockés en base : type de contenu, taxonomies et metas de
 * terme. Source unique ; les metas de lieu sont dans FieldRegistry.
 */

namespace MappedPlaces\Domain;

use MappedPlaces\Admin\LabelsSettings;

if (!defined('ABSPATH')) {
    exit;
}

final class Schema {

    /** Type de contenu des lieux. */
    const POST_TYPE = 'mapl_place';

    /** Menu d'administration des lieux, parent des pages Réglages et Import. */
    const ADMIN_PARENT = 'edit.php?post_type=' . self::POST_TYPE;

    /** Taxonomies des lieux. */
    const TAX_TYPE          = 'mapl_type';
    const TAX_REGION        = 'mapl_region';
    const TAX_SERVICE       = 'mapl_service';
    const TAX_ACCESSIBILITY = 'mapl_accessibility';
    const TAX_ENTITY        = 'mapl_entity';

    /** Meta de terme : couleur d'une entité. */
    const ENTITY_COLOR_META = '_mapl_color';

    /** Meta de terme : clé d'icône d'un type. */
    const TYPE_ICON_META = '_mapl_icon';

    /** Slug d'URL des lieux, par défaut. */
    const PLACE_SLUG = 'places';

    /** Slugs d'URL des archives de taxonomie, par défaut. */
    const TAXONOMY_SLUGS = array(
        self::TAX_TYPE          => 'place-type',
        self::TAX_REGION        => 'place-region',
        self::TAX_SERVICE       => 'place-service',
        self::TAX_ACCESSIBILITY => 'place-accessibility',
        self::TAX_ENTITY        => 'place-entity',
    );

    /**
     * Slug d'URL des lieux (filtre mapped_places_place_slug) : un site peut
     * garder ses anciennes URL.
     *
     * @return string
     */
    public static function place_slug() {
        // Priorité : filtre > réglage « Libellés et défauts » > code.
        $setting = LabelsSettings::place_slug();
        $slug    = apply_filters('mapped_places_place_slug', $setting !== '' ? $setting : self::PLACE_SLUG);
        return is_string($slug) && $slug !== '' ? sanitize_title($slug) : self::PLACE_SLUG;
    }

    /**
     * Slug d'URL de l'archive d'une taxonomie (filtre mapped_places_taxonomy_slugs).
     *
     * @param string $taxonomy
     * @return string
     */
    public static function taxonomy_slug($taxonomy) {
        $slugs = (array) apply_filters('mapped_places_taxonomy_slugs', self::TAXONOMY_SLUGS);
        $slug  = isset($slugs[$taxonomy]) && is_string($slugs[$taxonomy]) ? $slugs[$taxonomy] : '';
        return $slug !== '' ? sanitize_title($slug) : self::TAXONOMY_SLUGS[$taxonomy];
    }
}
