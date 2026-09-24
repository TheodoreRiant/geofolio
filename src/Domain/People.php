<?php
/**
 * Personnes rattachées à un lieu : un rôle et un nom chacune, dans l'ordre
 * d'affichage (« Directeur/trice : Marie Beton », « Secrétaire général :
 * Antonin Klark »…).
 *
 * Stockées en JSON dans la meta `people`. L'ancien champ `manager` (noms
 * séparés par des virgules, rôle implicite) reste lu en repli et est tenu à
 * jour à l'enregistrement pour les consommateurs qui ne connaissent que lui.
 */

namespace Geofolio\Domain;

if (!defined('ABSPATH')) {
    exit;
}

final class People {

    /** Nombre maximal de personnes par lieu. */
    const MAX = 20;

    /**
     * Rôle donné aux noms de l'ancien champ « manager ».
     *
     * @return string
     */
    public static function default_role() {
        return (string) apply_filters('geofolio_default_person_role', __('Manager', 'geofolio'));
    }

    /**
     * Rôles proposés à la saisie (liste ouverte : tout texte reste possible).
     *
     * @return string[]
     */
    public static function role_suggestions() {
        $roles = array(
            __('Director', 'geofolio'),
            __('Manager', 'geofolio'),
            __('Secretary general', 'geofolio'),
            __('Head of service', 'geofolio'),
            __('Contact person', 'geofolio'),
        );
        $filtered = apply_filters('geofolio_people_roles', $roles);
        return array_values(array_unique(array_filter(array_map('strval', is_array($filtered) ? $filtered : $roles))));
    }

    /**
     * Décoder et nettoyer une liste de personnes.
     *
     * @param mixed $raw JSON (chaîne) ou tableau déjà décodé.
     * @return array<int, array{role: string, name: string}> Sans entrée vide.
     */
    public static function parse($raw) {
        $list = is_array($raw) ? $raw : json_decode(is_string($raw) ? $raw : '', true);
        if (!is_array($list)) {
            return array();
        }
        $people = array();
        foreach ($list as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $name = sanitize_text_field((string) ($entry['name'] ?? ''));
            $role = sanitize_text_field((string) ($entry['role'] ?? ''));
            if ($name === '') {
                continue;
            }
            $people[] = array('role' => $role, 'name' => $name);
            if (count($people) >= self::MAX) {
                break;
            }
        }
        return $people;
    }

    /**
     * Encoder pour la meta.
     *
     * @param array $people
     * @return string JSON, tableau vide compris.
     */
    public static function encode(array $people) {
        // Unicode non échappé : lisible en base, et insensible aux couches
        // d'échappement de barres obliques entre le formulaire et la meta.
        return (string) wp_json_encode(array_values($people), JSON_UNESCAPED_UNICODE);
    }

    /**
     * Nettoyage pour le registre des champs : JSON validé.
     *
     * @param mixed $value
     * @return string
     */
    public static function sanitize_json($value) {
        return self::encode(self::parse($value));
    }

    /**
     * Ancien champ « manager » (noms séparés par des virgules) converti en
     * personnes, toutes avec le rôle par défaut.
     *
     * @param string $manager
     * @param string $default_role
     * @return array<int, array{role: string, name: string}>
     */
    public static function from_legacy($manager, $default_role) {
        $people = array();
        foreach (explode(',', (string) $manager) as $name) {
            $name = trim($name);
            if ($name !== '') {
                $people[] = array('role' => (string) $default_role, 'name' => $name);
            }
        }
        return $people;
    }

    /**
     * Noms seuls, séparés par des virgules : la valeur du champ historique.
     *
     * @param array $people
     * @return string
     */
    public static function names(array $people) {
        return implode(', ', array_column($people, 'name'));
    }

    /**
     * Personnes d'un lieu, avec repli sur l'ancien champ.
     *
     * @param int    $post_id
     * @param string $default_role Rôle donné aux noms de l'ancien champ.
     * @return array<int, array{role: string, name: string}>
     */
    public static function for_post($post_id, $default_role = '') {
        $people = self::parse(FieldRegistry::get($post_id, 'people'));
        if ($people) {
            return $people;
        }
        return self::from_legacy(FieldRegistry::get($post_id, 'manager'), $default_role);
    }
}
