<?php
/**
 * Import depuis un ancien plugin de carte : étape générique du cœur,
 * paramétrée par le filtre mapped_places_legacy_import (aucune donnée d'un site
 * particulier dans le cœur).
 */

use PHPUnit\Framework\TestCase;
use MappedPlaces\Domain\FieldRegistry;
use MappedPlaces\Domain\Schema;
use MappedPlaces\Migration\Legacy\BlockRewriter;
use MappedPlaces\Migration\Legacy\Config;
use MappedPlaces\Migration\Legacy\ElementorRewriter;
use MappedPlaces\Migration\Legacy\Geofolio;
use MappedPlaces\Migration\Legacy\IconMatcher;
use MappedPlaces\Migration\Legacy\ImportStep;
use MappedPlaces\Migration\Legacy\ShortcodeRewriter;

final class LegacyImportTest extends TestCase {

    /** Configuration d'un ancien plugin fictif. */
    const CONFIG = array(
        'post_type'         => 'old_place',
        'taxonomies'        => array('old_type' => Schema::TAX_TYPE, 'old_group' => Schema::TAX_ENTITY),
        'post_meta'         => array('_old_city' => 'city', '_old_boss' => 'manager'),
        'term_meta'         => array('_old_color' => Schema::ENTITY_COLOR_META),
        'options'           => array('old_settings' => 'mapped_places_settings'),
        'elementor_widgets' => array('old_map'),
        'shortcodes'        => array('old-map'),
        'type_icons'        => array('library' => 'book', 'community centre' => 'people'),
        'manager_role'      => 'Head',
        'place_slug'        => 'venue',
        'labels'            => array('place_singular' => 'Venue', 'place_plural' => 'Venues'),
        'appearance'        => array('primary_color' => '#123456'),
        'plugin'            => 'old-map/old-map.php',
    );

    protected function setUp(): void {
        mapl_test_reset();
        mapl_test_reset_wpdb();
        mapl_test_reset_posts();
        $GLOBALS['mapl_test_cache_deleted'] = array();
        Geofolio::forget();
    }

    protected function tearDown(): void {
        mapl_test_reset_filters();
        Geofolio::forget();
    }

    /* ---------------------------------------------------------------- */
    /*  Configuration                                                    */
    /* ---------------------------------------------------------------- */

    public function test_sans_configuration_aucune_etape_n_est_proposee() {
        $this->assertSame(array(), Config::get());
        $this->assertSame(array(), ImportStep::register(array()));
    }

    public function test_la_configuration_est_normalisee() {
        add_filter('mapped_places_legacy_import', static function () {
            return array('post_type' => 'Old Place!', 'unknown' => 'x', 'shortcodes' => array('old-map', 42), 'type_icons' => 'bad');
        });
        $config = Config::get();

        $this->assertSame('oldplace', $config['post_type']);
        $this->assertArrayNotHasKey('unknown', $config);
        $this->assertSame(array('old-map'), $config['shortcodes']);
        $this->assertSame(array(), $config['type_icons']);
    }

    public function test_une_cible_de_taxonomie_ou_de_champ_inconnue_est_ignoree() {
        add_filter('mapped_places_legacy_import', static function () {
            return array('post_type' => 'old', 'taxonomies' => array('a' => 'not_a_mapped_places_taxonomy'), 'post_meta' => array('_x' => 'no_such_field'));
        });
        $config = Config::get();

        $this->assertSame(array(), $config['taxonomies']);
        $this->assertSame(array(), $config['post_meta']);
    }

    /* ---------------------------------------------------------------- */
    /*  Icônes                                                           */
    /* ---------------------------------------------------------------- */

    public function test_une_icone_est_trouvee_par_nom_exact_puis_partiel() {
        $catalog = self::CONFIG['type_icons'];

        $this->assertSame('book', IconMatcher::match($catalog, '  Library '));
        $this->assertSame('people', IconMatcher::match($catalog, 'Community centre for families'));
        $this->assertSame('', IconMatcher::match($catalog, 'Harbour'));
        $this->assertSame('', IconMatcher::match($catalog, ''));
    }

    /* ---------------------------------------------------------------- */
    /*  Elementor et shortcodes                                          */
    /* ---------------------------------------------------------------- */

    public function test_un_widget_imbrique_est_renomme_sans_perte() {
        $data = array(array(
            'elType'   => 'section',
            'settings' => array('gap' => 'no'),
            'elements' => array(array(
                'elType'   => 'column',
                'elements' => array(
                    array('elType' => 'widget', 'widgetType' => 'old_map', 'settings' => array('map_height' => array('size' => 100, 'unit' => 'vh'))),
                    array('elType' => 'widget', 'widgetType' => 'heading', 'settings' => array('title' => 'old_map')),
                ),
            )),
        ));
        list($rewritten, $count) = ElementorRewriter::rewrite($data, array('old_map'), 'mapped_places_map');

        $this->assertSame(1, $count);
        $widgets = $rewritten[0]['elements'][0]['elements'];
        $this->assertSame('mapped_places_map', $widgets[0]['widgetType']);
        $this->assertSame(array('map_height' => array('size' => 100, 'unit' => 'vh')), $widgets[0]['settings']);
        $this->assertSame('heading', $widgets[1]['widgetType']);
        $this->assertSame('old_map', $widgets[1]['settings']['title']); // texte non touché
        $this->assertSame(array('gap' => 'no'), $rewritten[0]['settings']);
    }

    public function test_les_widgets_sont_reecrits_ligne_par_ligne_par_identifiant_de_meta() {
        $wpdb = $GLOBALS['wpdb'];
        $wpdb->col_result = array('7', '9');
        $wpdb->rows = array(
            (object) array('post_id' => 12, 'meta_value' => wp_json_encode(array(array('elType' => 'widget', 'widgetType' => 'old_map', 'settings' => array('zoom' => 6))))),
            (object) array('post_id' => 13, 'meta_value' => wp_json_encode(array(array('elType' => 'widget', 'widgetType' => 'heading')))),
        );

        $this->assertSame(1, ElementorRewriter::apply(array('old_map'), 'mapped_places_map'));

        // Sélection des identifiants seulement, filtrée sur le nom du widget :
        // jamais toutes les structures Elementor du site en mémoire.
        $this->assertStringContainsString('SELECT meta_id FROM', $wpdb->queries[0]);
        $this->assertStringContainsString('"widgetType":"old\\_map"', $wpdb->queries[0]); // esc_like() protège le tiret bas
        $this->assertStringNotContainsString('meta_value FROM', $wpdb->queries[0]);
        $this->assertStringContainsString('WHERE meta_id = 7', $wpdb->queries[1]);
        $this->assertStringContainsString('WHERE meta_id = 9', $wpdb->queries[2]);
        // Écriture par meta_id : une révision garde sa propre meta.
        $this->assertCount(1, $wpdb->updates);
        $this->assertSame($wpdb->postmeta, $wpdb->updates[0][0]);
        $this->assertSame(array('meta_id' => 7), $wpdb->updates[0][2]);
        $this->assertStringContainsString('"widgetType":"mapped_places_map"', $wpdb->updates[0][1]['meta_value']);
        $this->assertStringContainsString('"zoom":6', $wpdb->updates[0][1]['meta_value']);
        $this->assertContains(array(12, 'post_meta'), $GLOBALS['mapl_test_cache_deleted']);
    }

    public function test_un_bloc_est_renomme_en_gardant_ses_attributs() {
        $old = array('old/map');
        $this->assertSame('<!-- wp:mapped-places/map {"align":"full","height":"80vh"} /-->', BlockRewriter::rewrite('<!-- wp:old/map {"align":"full","height":"80vh"} /-->', $old, 'mapped-places/map'));
        $this->assertSame('<!-- wp:mapped-places/map --><div></div><!-- /wp:mapped-places/map -->', BlockRewriter::rewrite('<!-- wp:old/map --><div></div><!-- /wp:old/map -->', $old, 'mapped-places/map'));
        $this->assertSame('<!-- wp:old/mapx /-->', BlockRewriter::rewrite('<!-- wp:old/mapx /-->', $old, 'mapped-places/map'));
        $this->assertSame('<p>wp:old/map</p>', BlockRewriter::rewrite('<p>wp:old/map</p>', $old, 'mapped-places/map'));
    }

    public function test_un_shortcode_est_reecrit_avec_ses_attributs() {
        $this->assertSame('<p>[mapped-places height="600px"]</p>', ShortcodeRewriter::rewrite('<p>[old-map height="600px"]</p>', array('old-map'), 'mapped-places'));
        $this->assertSame('[mapped-places]', ShortcodeRewriter::rewrite('[old-map]', array('old-map'), 'mapped-places'));
        $this->assertSame('[old-mapper]', ShortcodeRewriter::rewrite('[old-mapper]', array('old-map'), 'mapped-places'));
    }

    /* ---------------------------------------------------------------- */
    /*  Geofolio 1.x, l'ancien nom du plugin                             */
    /* ---------------------------------------------------------------- */

    public function test_sans_trace_de_geofolio_le_coeur_ne_decrit_rien() {
        Geofolio::register();

        $this->assertSame(array(), Config::get());
        $this->assertSame(array(), ImportStep::register(array()));
    }

    public function test_le_coeur_decrit_geofolio_des_qu_une_de_ses_options_existe() {
        Geofolio::register();
        mapl_test_reset(array('geofolio_settings' => array('api_key' => 'k', 'tile_style' => 'carto-positron')));

        $config = Config::get();

        $this->assertSame('gfo_place', $config['post_type']);
        $this->assertSame(Schema::TAX_TYPE, $config['taxonomies']['gfo_type']);
        $this->assertSame(Schema::TAX_ENTITY, $config['taxonomies']['gfo_entity']);
        $this->assertCount(5, $config['taxonomies']);
        // Chaque champ de lieu : ancienne meta _gfo_<champ> vers le champ.
        $this->assertSame(FieldRegistry::fields(), array_values($config['post_meta']));
        $this->assertSame('latitude', $config['post_meta']['_gfo_latitude']);
        $this->assertSame(Schema::ENTITY_COLOR_META, $config['term_meta']['_gfo_color']);
        $this->assertSame(Schema::TYPE_ICON_META, $config['term_meta']['_gfo_icon']);
        $this->assertSame('mapped_places_settings', $config['options']['geofolio_settings']);
        $this->assertSame('mapped_places_appearance', $config['options']['geofolio_appearance']);
        $this->assertSame('mapped_places_labels', $config['options']['geofolio_labels']);
        $this->assertSame(array('geofolio_map'), $config['elementor_widgets']);
        $this->assertSame(array('geofolio'), $config['shortcodes']);
        $this->assertSame(array('geofolio/map'), $config['blocks']);
        $this->assertSame('geofolio/geofolio.php', $config['plugin']);
        // Rien à deviner : icônes, personnes et réglages sont déjà en place.
        $this->assertSame(array(), $config['type_icons']);
        $this->assertSame('', $config['manager_role']);

        $steps = ImportStep::register(array());
        $this->assertCount(1, $steps);
        $this->assertInstanceOf(ImportStep::class, $steps[0]);
    }

    public function test_le_coeur_decrit_geofolio_des_qu_un_lieu_de_son_type_existe() {
        Geofolio::register();
        $GLOBALS['wpdb']->col_result = array('41');

        $this->assertSame('gfo_place', Config::get()['post_type']);
        $this->assertStringContainsString("post_type = 'gfo_place'", $GLOBALS['wpdb']->queries[0]);
    }

    public function test_un_compagnon_qui_decrit_un_autre_plugin_garde_la_main() {
        Geofolio::register();
        mapl_test_reset(array('geofolio_settings' => array('api_key' => 'k')));
        add_filter('mapped_places_legacy_import', static function () { return self::CONFIG; });

        $this->assertSame('old_place', Config::get()['post_type']);
    }

    public function test_l_import_de_geofolio_renomme_les_lignes_et_deplace_les_options() {
        Geofolio::register();
        mapl_test_reset(array(
            'geofolio_settings'          => array('api_key' => 'k', 'tile_style' => ''),
            'geofolio_appearance'        => array('primary_color' => '#024266'),
            'geofolio_rest_cache_generation' => 5,
        ));
        $wpdb = $GLOBALS['wpdb'];

        $report = (new ImportStep())->run();

        $this->assertContains(array($wpdb->posts, array('post_type' => Schema::POST_TYPE), array('post_type' => 'gfo_place')), $wpdb->updates);
        $this->assertContains(array($wpdb->term_taxonomy, array('taxonomy' => Schema::TAX_TYPE), array('taxonomy' => 'gfo_type')), $wpdb->updates);
        $this->assertContains(array($wpdb->postmeta, array('meta_key' => FieldRegistry::meta_key('gallery')), array('meta_key' => '_gfo_gallery')), $wpdb->updates);
        $this->assertContains(array($wpdb->termmeta, array('meta_key' => Schema::TYPE_ICON_META), array('meta_key' => '_gfo_icon')), $wpdb->updates);
        $this->assertSame('moved', $report['renamed']['options']['geofolio_settings']);
        $this->assertSame(array('api_key' => 'k', 'tile_style' => ''), get_option('mapped_places_settings'));
        $this->assertSame('#024266', get_option('mapped_places_appearance')['primary_color']);
        $this->assertFalse(get_option('geofolio_settings'));
        // Les caches et journaux de l'ancien nom ne sont pas repris.
        $this->assertSame(5, get_option('geofolio_rest_cache_generation'));
        $this->assertSame('absent', $report['renamed']['options']['geofolio_labels']);
    }

    /* ---------------------------------------------------------------- */
    /*  Étape                                                            */
    /* ---------------------------------------------------------------- */

    public function test_l_etape_n_est_proposee_que_si_l_ancien_plugin_a_laisse_des_donnees() {
        add_filter('mapped_places_legacy_import', static function () { return self::CONFIG; });

        $this->assertSame(array(), ImportStep::register(array()));

        mapl_test_reset(array('old_settings' => array('api_key' => 'k')));
        $steps = ImportStep::register(array());
        $this->assertCount(1, $steps);
        $this->assertInstanceOf(ImportStep::class, $steps[0]);
    }

    public function test_le_renommage_cible_chaque_ligne_par_une_condition() {
        add_filter('mapped_places_legacy_import', static function () { return self::CONFIG; });
        $wpdb = $GLOBALS['wpdb'];

        (new ImportStep())->run();

        $this->assertNotEmpty($wpdb->updates);
        foreach ($wpdb->updates as $update) {
            $this->assertNotEmpty($update[2], 'Chaque UPDATE porte une condition.');
        }
        $this->assertContains(array($wpdb->posts, array('post_type' => Schema::POST_TYPE), array('post_type' => 'old_place')), $wpdb->updates);
        $this->assertContains(array($wpdb->postmeta, array('meta_key' => FieldRegistry::meta_key('city')), array('meta_key' => '_old_city')), $wpdb->updates);
        $this->assertContains(array($wpdb->term_taxonomy, array('taxonomy' => Schema::TAX_ENTITY), array('taxonomy' => 'old_group')), $wpdb->updates);
    }

    public function test_les_reglages_sont_repris_sans_ecraser_ceux_deja_faits() {
        add_filter('mapped_places_legacy_import', static function () { return self::CONFIG; });
        mapl_test_reset(array('mapped_places_labels' => array('place_plural' => 'Déjà réglé')));

        (new ImportStep())->run();

        $labels = get_option('mapped_places_labels');
        $this->assertSame('Déjà réglé', $labels['place_plural']);
        $this->assertSame('Venue', $labels['place_singular']);
        $this->assertSame('venue', $labels['place_slug']);
        $this->assertSame('#123456', get_option('mapped_places_appearance')['primary_color']);
    }

    public function test_un_ancien_responsable_devient_une_personne_avec_son_role() {
        $this->assertSame(
            array(array('role' => 'Head', 'name' => 'Ada Lovelace'), array('role' => 'Head', 'name' => 'Alan Turing')),
            ImportStep::people_from_manager('Ada Lovelace, Alan Turing', 'Head')
        );
        $this->assertSame(array(), ImportStep::people_from_manager('', 'Head'));
    }

    /** Seuls les lieux venus de l'ancien plugin reçoivent le rôle de l'import. */
    public function test_seuls_les_lieux_de_l_ancien_plugin_sont_convertis_en_personnes() {
        add_filter('mapped_places_legacy_import', static function () { return self::CONFIG; });
        $old   = mapl_test_add_post(array('post_type' => 'old_place'), array(FieldRegistry::meta_key('manager') => 'Ada'));
        $other = mapl_test_add_post(array('post_type' => Schema::POST_TYPE), array(FieldRegistry::meta_key('manager') => 'Grace'));
        $wpdb  = $GLOBALS['wpdb'];
        $wpdb->col_result = array($old->ID);   // identifiants relevés avant le renommage

        $report = (new ImportStep())->run();

        $this->assertSame(1, $report['people']);
        // Liste relevée par type de contenu avant le renommage, pas par un
        // balayage de tous les lieux qui ont un responsable.
        $scans = array_filter($wpdb->queries, static function ($q) {
            return strpos($q, "meta_key = '" . FieldRegistry::meta_key('manager') . "'") !== false;
        });
        $this->assertSame(array(), array_values($scans));
        $this->assertSame('', (string) get_post_meta($other->ID, FieldRegistry::meta_key('people'), true));
        $this->assertStringContainsString('"role":"Head"', (string) get_post_meta($old->ID, FieldRegistry::meta_key('people'), true));
    }

    public function test_le_role_choisi_a_l_ecran_remplace_celui_du_filtre() {
        add_filter('mapped_places_legacy_import', static function () { return self::CONFIG; });
        ImportStep::set_manager_role('Directrice');

        $this->assertSame('Directrice', ImportStep::manager_role(Config::get()));
    }

    public function test_l_import_demande_une_confirmation() {
        $this->assertTrue((new ImportStep())->requires_confirmation());
    }

    /** L'écran affiche ce qui va être fait, d'après les paramètres reçus. */
    public function test_le_resume_decrit_les_parametres_recus() {
        $lines = \MappedPlaces\Migration\Legacy\ImportScreen::summary(Config::normalize(self::CONFIG));
        $text  = implode("\n", $lines);

        $this->assertStringContainsString('old_place', $text);
        $this->assertStringContainsString('old_map', $text);
        $this->assertStringContainsString('old-map', $text);
        $this->assertStringContainsString('venue', $text);
        $this->assertStringContainsString('old-map/old-map.php', $text);
    }

    /** Les noms de types diffèrent souvent par les accents ou les apostrophes. */
    public function test_la_correspondance_ignore_accents_casse_et_apostrophes() {
        $catalog = Config::normalize(array('type_icons' => array(
            'centre educatif ferme' => 'shield',
            "service d'investigations educatives" => 'search',
        )))['type_icons'];

        $this->assertSame('shield', IconMatcher::match($catalog, 'Centre Éducatif Fermé'));
        $this->assertSame('search', IconMatcher::match($catalog, 'Service d’Investigations Éducatives'));
    }

    public function test_les_anciennes_metas_sans_equivalent_sont_supprimees_des_lieux_importes() {
        $config = self::CONFIG + array('delete_post_meta' => array('_old_department', 42));
        add_filter('mapped_places_legacy_import', static function () use ($config) { return $config; });
        $old   = mapl_test_add_post(array('post_type' => 'old_place'), array('_old_department' => '69'));
        $other = mapl_test_add_post(array('post_type' => 'page'), array('_old_department' => 'garder'));
        $GLOBALS['wpdb']->col_result = array($old->ID);

        $this->assertSame(array('_old_department'), Config::get()['delete_post_meta']);
        $report = (new ImportStep())->run();

        $this->assertSame(array('_old_department' => 1), $report['deleted_meta']);
        $this->assertSame('', (string) get_post_meta($old->ID, '_old_department', true));
        $this->assertSame('garder', get_post_meta($other->ID, '_old_department', true));
    }
}
