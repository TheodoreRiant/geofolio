<?php
/**
 * Étape de migration : reprendre les données d'un ancien plugin de carte
 * décrit par le filtre mapped_places_legacy_import (voir Config).
 *
 * Dans l'ordre, après le snapshot de l'exécuteur : renommage des
 * identifiants (type de contenu, taxonomies, metas, options), icônes des
 * types, personnes issues de l'ancien champ « responsable », widgets
 * Elementor et shortcodes des pages, réglages (slug, libellés, apparence)
 * sans écraser ceux déjà faits, puis régénération des URL et désactivation
 * de l'ancien plugin. Idempotente : une seconde exécution ne trouve plus rien.
 *
 * @package Mapped Places
 */

namespace MappedPlaces\Migration\Legacy;

use MappedPlaces\Admin\AppearanceSettings;
use MappedPlaces\Admin\LabelsSettings;
use MappedPlaces\Blocks\MapBlock;
use MappedPlaces\Domain\FieldRegistry;
use MappedPlaces\Domain\People;
use MappedPlaces\Domain\Schema;
use MappedPlaces\Elementor\Integration;
use MappedPlaces\Map\Shortcode;
use MappedPlaces\Migration\ConfirmedStep;
use MappedPlaces\Migration\Step;
use MappedPlaces\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

class ImportStep implements Step, ConfirmedStep {

    /** Identifiant de l'étape. */
    const ID = 'mapped_places_legacy_import';

    /** Rôle choisi sur l'écran de migration (remplace celui du filtre). */
    const ROLE_OPTION = 'mapped_places_legacy_import_role';

    /**
     * Ajouter l'étape en tête des migrations quand un ancien plugin est
     * décrit et a laissé des données (filtre mapped_places_migration_steps).
     *
     * @param array $steps
     * @return array
     */
    public static function register($steps) {
        $steps  = is_array($steps) ? $steps : array();
        $config = Config::get();
        if ($config === array() || !self::detected($config)) {
            return $steps;
        }
        return array_merge(array(new self()), $steps);
    }

    /**
     * L'ancien plugin a-t-il laissé des données ?
     *
     * @param array $config
     * @return bool
     */
    private static function detected(array $config) {
        global $wpdb;
        foreach (array_keys($config['options']) as $option) {
            if (get_option($option, null) !== null) {
                return true;
            }
        }
        if ($config['post_type'] === '') {
            return false;
        }
        return (bool) $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s LIMIT 1",
            $config['post_type']
        ));
    }

    public function id() {
        return self::ID;
    }

    /**
     * Toujours lancée par un administrateur, depuis l'écran d'import qui
     * montre ce qu'elle va faire (ImportScreen).
     *
     * @return bool
     */
    public function requires_confirmation() {
        return true;
    }

    public function run() {
        $config = Config::get();
        if ($config === array()) {
            return array('skipped' => 'no legacy plugin described');
        }

        // Relevés avant le renommage : seuls ces lieux viennent de l'ancien plugin.
        $legacy_ids = self::legacy_post_ids($config['post_type']);

        $report = array('renamed' => $this->rename($config));
        $report['icons']      = $this->assign_icons($config['type_icons']);
        $report['people']     = $this->convert_managers(self::manager_role($config), $legacy_ids);
        $report['deleted_meta'] = $this->delete_old_meta($config['delete_post_meta'], $legacy_ids);
        delete_option(self::ROLE_OPTION);
        $report['elementor']  = ElementorRewriter::apply($config['elementor_widgets'], Integration::WIDGET_NAME);
        $report['shortcodes'] = ShortcodeRewriter::apply($config['shortcodes'], Shortcode::TAG);
        $report['blocks']     = BlockRewriter::apply($config['blocks'], MapBlock::NAME);
        $report['settings']   = $this->import_settings($config);
        Geofolio::forget();

        wp_cache_flush();
        // Pas maintenant : l'ancien plugin, peut-être encore chargé pendant
        // cette requête, a enregistré ses propres règles de réécriture.
        Plugin::request_rewrite_flush();
        $report['plugin'] = $this->deactivate_plugin($config['plugin']);

        return $report;
    }

    /**
     * Renommer type de contenu, taxonomies, metas et options. Les lignes
     * gardent leurs identifiants, donc leurs liaisons.
     *
     * @param array $config
     * @return array
     */
    private function rename(array $config) {
        global $wpdb;
        $report = array('posts' => 0, 'taxonomies' => array(), 'post_meta' => array(), 'term_meta' => array(), 'options' => array());

        if ($config['post_type'] !== '') {
            $report['posts'] = (int) $wpdb->update($wpdb->posts, array('post_type' => Schema::POST_TYPE), array('post_type' => $config['post_type']));
        }
        foreach ($config['taxonomies'] as $old => $new) {
            $report['taxonomies'][$old] = (int) $wpdb->update($wpdb->term_taxonomy, array('taxonomy' => $new), array('taxonomy' => $old));
        }
        foreach ($config['post_meta'] as $old => $field) {
            $report['post_meta'][$old] = (int) $wpdb->update($wpdb->postmeta, array('meta_key' => FieldRegistry::meta_key($field)), array('meta_key' => $old));
        }
        foreach ($config['term_meta'] as $old => $new) {
            $report['term_meta'][$old] = (int) $wpdb->update($wpdb->termmeta, array('meta_key' => $new), array('meta_key' => $old));
        }
        foreach ($config['options'] as $old => $new) {
            $report['options'][$old] = self::rename_option($old, $new);
        }
        return $report;
    }

    /**
     * Renommer une option sans écraser une valeur déjà présente.
     *
     * @param string $old
     * @param string $new
     * @return string moved | kept | absent
     */
    private static function rename_option($old, $new) {
        $value = get_option($old, null);
        if ($value === null) {
            return 'absent';
        }
        if (get_option($new, null) === null) {
            update_option($new, $value, false);
        }
        delete_option($old);
        return get_option($new, null) === $value ? 'moved' : 'kept';
    }

    /**
     * Poser une icône sur chaque type qui n'en a pas, d'après le catalogue.
     *
     * @param array<string, string> $catalog
     * @return array{assigned: int, unmatched: string[]}
     */
    private function assign_icons(array $catalog) {
        $report = array('assigned' => 0, 'unmatched' => array());
        if ($catalog === array()) {
            return $report;
        }
        $terms = get_terms(array('taxonomy' => Schema::TAX_TYPE, 'hide_empty' => false));
        foreach (is_array($terms) ? $terms : array() as $term) {
            if ((string) get_term_meta($term->term_id, Schema::TYPE_ICON_META, true) !== '') {
                continue;
            }
            $icon = IconMatcher::match($catalog, $term->name);
            if ($icon === '') {
                $report['unmatched'][] = $term->name;
                continue;
            }
            update_term_meta($term->term_id, Schema::TYPE_ICON_META, $icon);
            $report['assigned']++;
        }
        return $report;
    }

    /**
     * Ancien champ « responsable » (noms séparés par des virgules) converti
     * en personnes, pour les lieux qui n'en ont pas encore.
     *
     * @param string $role Rôle donné aux personnes converties.
     * @param int[]  $ids  Lieux venus de l'ancien plugin.
     * @return int Lieux convertis.
     */
    private function convert_managers($role, array $ids) {
        if ($role === '') {
            return 0;
        }
        $converted = 0;
        foreach ($ids as $id) {
            $id = (int) $id;
            if ((string) get_post_meta($id, FieldRegistry::meta_key('people'), true) !== '') {
                continue;
            }
            $people = self::people_from_manager((string) get_post_meta($id, FieldRegistry::meta_key('manager'), true), $role);
            if ($people !== array()) {
                update_metadata('post', $id, FieldRegistry::meta_key('people'), wp_slash(People::encode($people)));
                $converted++;
            }
        }
        return $converted;
    }

    /**
     * Supprimer, des seuls lieux importés, les anciennes metas sans
     * équivalent dans Mapped Places (sinon orphelines).
     *
     * @param string[] $keys
     * @param int[]    $ids
     * @return array<string, int> Nombre de lieux nettoyés par clé.
     */
    private function delete_old_meta(array $keys, array $ids) {
        $report = array();
        foreach ($keys as $key) {
            $report[$key] = 0;
            foreach ($ids as $id) {
                if (metadata_exists('post', $id, $key)) {
                    delete_metadata('post', $id, $key);
                    $report[$key]++;
                }
            }
        }
        return $report;
    }

    /**
     * Identifiants des posts de l'ancien type de contenu.
     *
     * @param string $post_type
     * @return int[]
     */
    private static function legacy_post_ids($post_type) {
        global $wpdb;
        if ($post_type === '') {
            return array();
        }
        return array_map('intval', (array) $wpdb->get_col($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
            $post_type
        )));
    }

    /**
     * Rôle des personnes converties : celui choisi sur l'écran de migration,
     * sinon celui du filtre.
     *
     * @param array $config
     * @return string
     */
    public static function manager_role(array $config) {
        $chosen = (string) get_option(self::ROLE_OPTION, '');
        return $chosen !== '' ? $chosen : (string) ($config['manager_role'] ?? '');
    }

    /**
     * Retenir le rôle choisi sur l'écran de migration.
     *
     * @param string $role
     */
    public static function set_manager_role($role) {
        update_option(self::ROLE_OPTION, sanitize_text_field((string) $role), false);
    }

    /**
     * @param string $manager Noms séparés par des virgules.
     * @param string $role
     * @return array<int, array{role: string, name: string}>
     */
    public static function people_from_manager($manager, $role) {
        return People::from_legacy($manager, $role);
    }

    /**
     * Reprendre slug, libellés et apparence dans les réglages, sans écraser
     * une valeur déjà réglée.
     *
     * @param array $config
     * @return array
     */
    private function import_settings(array $config) {
        $labels = $config['labels'];
        if ($config['place_slug'] !== '') {
            $labels['place_slug'] = $config['place_slug'];
        }
        return array(
            'labels'     => self::fill_option(LabelsSettings::OPTION_NAME, LabelsSettings::defaults(), $labels, array(LabelsSettings::class, 'sanitize')),
            'appearance' => self::fill_option(AppearanceSettings::OPTION_NAME, AppearanceSettings::defaults(), $config['appearance'], array(AppearanceSettings::class, 'sanitize')),
        );
    }

    /**
     * Compléter les champs vides d'une option avec les valeurs importées.
     *
     * @param string   $option
     * @param array    $defaults
     * @param array    $values
     * @param callable $sanitize
     * @return string[] Champs remplis.
     */
    private static function fill_option($option, array $defaults, array $values, callable $sanitize) {
        $stored  = get_option($option, array());
        $current = array_merge($defaults, is_array($stored) ? $stored : array());
        $filled  = array();
        foreach ($values as $key => $value) {
            if (array_key_exists($key, $current) && ($current[$key] === '' || $current[$key] === $defaults[$key])) {
                $current[$key] = $value;
                $filled[]      = $key;
            }
        }
        if ($filled !== array()) {
            update_option($option, call_user_func($sanitize, $current));
        }
        return $filled;
    }

    /**
     * Désactiver l'ancien plugin s'il est actif.
     *
     * @param string $plugin Chemin relatif (dossier/fichier.php).
     * @return string deactivated | inactive | none
     */
    private function deactivate_plugin($plugin) {
        if ($plugin === '' || !function_exists('is_plugin_active')) {
            return 'none';
        }
        if (!is_plugin_active($plugin)) {
            return 'inactive';
        }
        deactivate_plugins($plugin, true);
        return 'deactivated';
    }
}
