<?php
/**
 * Import depuis un ancien plugin de carte : étape générique du cœur,
 * paramétrée par le filtre geofolio_legacy_import (aucune donnée d'un site
 * particulier dans le cœur).
 */

use PHPUnit\Framework\TestCase;
use Geofolio\Domain\FieldRegistry;
use Geofolio\Domain\Schema;
use Geofolio\Migration\Legacy\Config;
use Geofolio\Migration\Legacy\ElementorRewriter;
use Geofolio\Migration\Legacy\IconMatcher;
use Geofolio\Migration\Legacy\ImportStep;
use Geofolio\Migration\Legacy\ShortcodeRewriter;

final class LegacyImportTest extends TestCase {

    /** Configuration d'un ancien plugin fictif. */
    const CONFIG = array(
        'post_type'         => 'old_place',
        'taxonomies'        => array('old_type' => Schema::TAX_TYPE, 'old_group' => Schema::TAX_ENTITY),
        'post_meta'         => array('_old_city' => 'city', '_old_boss' => 'manager'),
        'term_meta'         => array('_old_color' => Schema::ENTITY_COLOR_META),
        'options'           => array('old_settings' => 'geofolio_settings'),
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
        gfo_test_reset();
        gfo_test_reset_wpdb();
        gfo_test_reset_posts();
    }

    protected function tearDown(): void {
        gfo_test_reset_filters();
    }

    /* ---------------------------------------------------------------- */
    /*  Configuration                                                    */
    /* ---------------------------------------------------------------- */

    public function test_sans_configuration_aucune_etape_n_est_proposee() {
        $this->assertSame(array(), Config::get());
        $this->assertSame(array(), ImportStep::register(array()));
    }

    public function test_la_configuration_est_normalisee() {
        add_filter('geofolio_legacy_import', static function () {
            return array('post_type' => 'Old Place!', 'unknown' => 'x', 'shortcodes' => array('old-map', 42), 'type_icons' => 'bad');
        });
        $config = Config::get();

        $this->assertSame('oldplace', $config['post_type']);
        $this->assertArrayNotHasKey('unknown', $config);
        $this->assertSame(array('old-map'), $config['shortcodes']);
        $this->assertSame(array(), $config['type_icons']);
    }

    public function test_une_cible_de_taxonomie_ou_de_champ_inconnue_est_ignoree() {
        add_filter('geofolio_legacy_import', static function () {
            return array('post_type' => 'old', 'taxonomies' => array('a' => 'not_a_geofolio_taxonomy'), 'post_meta' => array('_x' => 'no_such_field'));
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
        list($rewritten, $count) = ElementorRewriter::rewrite($data, array('old_map'), 'geofolio_map');

        $this->assertSame(1, $count);
        $widgets = $rewritten[0]['elements'][0]['elements'];
        $this->assertSame('geofolio_map', $widgets[0]['widgetType']);
        $this->assertSame(array('map_height' => array('size' => 100, 'unit' => 'vh')), $widgets[0]['settings']);
        $this->assertSame('heading', $widgets[1]['widgetType']);
        $this->assertSame('old_map', $widgets[1]['settings']['title']); // texte non touché
        $this->assertSame(array('gap' => 'no'), $rewritten[0]['settings']);
    }

    public function test_un_shortcode_est_reecrit_avec_ses_attributs() {
        $this->assertSame('<p>[geofolio height="600px"]</p>', ShortcodeRewriter::rewrite('<p>[old-map height="600px"]</p>', array('old-map'), 'geofolio'));
        $this->assertSame('[geofolio]', ShortcodeRewriter::rewrite('[old-map]', array('old-map'), 'geofolio'));
        $this->assertSame('[old-mapper]', ShortcodeRewriter::rewrite('[old-mapper]', array('old-map'), 'geofolio'));
    }

    /* ---------------------------------------------------------------- */
    /*  Étape                                                            */
    /* ---------------------------------------------------------------- */

    public function test_l_etape_n_est_proposee_que_si_l_ancien_plugin_a_laisse_des_donnees() {
        add_filter('geofolio_legacy_import', static function () { return self::CONFIG; });

        $this->assertSame(array(), ImportStep::register(array()));

        gfo_test_reset(array('old_settings' => array('api_key' => 'k')));
        $steps = ImportStep::register(array());
        $this->assertCount(1, $steps);
        $this->assertInstanceOf(ImportStep::class, $steps[0]);
    }

    public function test_le_renommage_cible_chaque_ligne_par_une_condition() {
        add_filter('geofolio_legacy_import', static function () { return self::CONFIG; });
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
        add_filter('geofolio_legacy_import', static function () { return self::CONFIG; });
        gfo_test_reset(array('geofolio_labels' => array('place_plural' => 'Déjà réglé')));

        (new ImportStep())->run();

        $labels = get_option('geofolio_labels');
        $this->assertSame('Déjà réglé', $labels['place_plural']);
        $this->assertSame('Venue', $labels['place_singular']);
        $this->assertSame('venue', $labels['place_slug']);
        $this->assertSame('#123456', get_option('geofolio_appearance')['primary_color']);
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
        add_filter('geofolio_legacy_import', static function () { return self::CONFIG; });
        $old   = gfo_test_add_post(array('post_type' => 'old_place'), array(FieldRegistry::meta_key('manager') => 'Ada'));
        $other = gfo_test_add_post(array('post_type' => Schema::POST_TYPE), array(FieldRegistry::meta_key('manager') => 'Grace'));
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
        add_filter('geofolio_legacy_import', static function () { return self::CONFIG; });
        ImportStep::set_manager_role('Directrice');

        $this->assertSame('Directrice', ImportStep::manager_role(Config::get()));
    }

    public function test_l_import_demande_une_confirmation() {
        $this->assertTrue((new ImportStep())->requires_confirmation());
    }

    /** L'écran affiche ce qui va être fait, d'après les paramètres reçus. */
    public function test_le_resume_decrit_les_parametres_recus() {
        $lines = \Geofolio\Migration\Legacy\ImportScreen::summary(Config::normalize(self::CONFIG));
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
        add_filter('geofolio_legacy_import', static function () use ($config) { return $config; });
        $old   = gfo_test_add_post(array('post_type' => 'old_place'), array('_old_department' => '69'));
        $other = gfo_test_add_post(array('post_type' => 'page'), array('_old_department' => 'garder'));
        $GLOBALS['wpdb']->col_result = array($old->ID);

        $this->assertSame(array('_old_department'), Config::get()['delete_post_meta']);
        $report = (new ImportStep())->run();

        $this->assertSame(array('_old_department' => 1), $report['deleted_meta']);
        $this->assertSame('', (string) get_post_meta($old->ID, '_old_department', true));
        $this->assertSame('garder', get_post_meta($other->ID, '_old_department', true));
    }
}
