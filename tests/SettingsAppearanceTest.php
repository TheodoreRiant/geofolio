<?php
/**
 * Réglages « Apparence » et « Libellés et défauts » (onglets de la page de
 * réglages) : sanitisation, variables CSS, priorité filtre > réglage > code.
 */

use PHPUnit\Framework\TestCase;
use Geofolio\Admin\AppearanceSettings;
use Geofolio\Admin\LabelsSettings;
use Geofolio\Domain\PlacePostType;
use Geofolio\Domain\Schema;
use Geofolio\Map\Defaults;

final class SettingsAppearanceTest extends TestCase {

    protected function setUp(): void {
        gfo_test_reset();
    }

    protected function tearDown(): void {
        gfo_test_reset_filters();
    }

    /* ---------------------------------------------------------------- */
    /*  Apparence                                                        */
    /* ---------------------------------------------------------------- */

    public function test_une_couleur_valide_est_gardee_et_une_invalide_ignoree() {
        $clean = AppearanceSettings::sanitize(array('primary_color' => '#024266', 'accent_color' => 'red;background:url(x)'));

        $this->assertSame('#024266', $clean['primary_color']);
        $this->assertSame('', $clean['accent_color']);
    }

    public function test_la_police_ne_prend_que_les_valeurs_prevues() {
        $this->assertSame('theme', AppearanceSettings::sanitize(array('font' => 'theme'))['font']);
        $this->assertSame(AppearanceSettings::FONT_BUNDLED, AppearanceSettings::sanitize(array('font' => 'comic'))['font']);
    }

    public function test_l_arrondi_est_borne() {
        $this->assertSame('8', AppearanceSettings::sanitize(array('radius' => '8'))['radius']);
        $this->assertSame((string) AppearanceSettings::MAX_RADIUS, AppearanceSettings::sanitize(array('radius' => '500'))['radius']);
        $this->assertSame('', AppearanceSettings::sanitize(array('radius' => ''))['radius']);
    }

    public function test_sans_reglage_aucune_variable_css_n_est_emise() {
        $this->assertSame('', AppearanceSettings::css_variables(AppearanceSettings::defaults()));
    }

    public function test_les_reglages_deviennent_des_variables_du_conteneur() {
        $css = AppearanceSettings::css_variables(array(
            'primary_color' => '#024266', 'accent_color' => '#FB6223', 'font' => 'theme', 'radius' => '8',
        ));

        $this->assertStringStartsWith('.gfo-map-container{', $css);
        $this->assertStringContainsString('--gfo-primary:#024266;', $css);
        $this->assertStringContainsString('--gfo-accent:#FB6223;', $css);
        // « initial » rend la variable invalide : font-family retombe sur
        // unset et hérite de la page (« inherit » hériterait de la variable).
        $this->assertStringContainsString('--gfo-font:initial;', $css);
        $this->assertStringContainsString('--gfo-font-title:initial;', $css);
        $this->assertStringContainsString('--gfo-radius:8px;', $css);
        // Teintes dérivées : elles suivent la couleur choisie.
        $this->assertMatchesRegularExpression('/--gfo-primary-dark:color-mix\(in srgb,#024266/', $css);
    }

    public function test_la_couleur_principale_devient_la_couleur_de_repli_des_marqueurs() {
        gfo_test_reset(array(AppearanceSettings::OPTION_NAME => array('primary_color' => '#024266')));
        $this->assertSame('#024266', Defaults::color());

        // Le filtre garde le dernier mot.
        add_filter('geofolio_default_color', static function () { return '#111111'; });
        $this->assertSame('#111111', Defaults::color());
    }

    /* ---------------------------------------------------------------- */
    /*  Libellés et défauts                                              */
    /* ---------------------------------------------------------------- */

    public function test_le_slug_est_nettoye() {
        $this->assertSame('etablissement', LabelsSettings::sanitize(array('place_slug' => ' Établissement '))['place_slug']);
    }

    public function test_les_valeurs_de_carte_sont_typees() {
        $clean = LabelsSettings::sanitize(array('center_lat' => '45,764', 'center_lng' => 'abc', 'zoom' => '40', 'fit_bounds' => 'false'));

        $this->assertSame('45.764', $clean['center_lat']);
        $this->assertSame('', $clean['center_lng']);
        $this->assertSame('18', $clean['zoom']);
        $this->assertSame('false', $clean['fit_bounds']);
    }

    public function test_le_reglage_l_emporte_sur_la_valeur_du_code() {
        gfo_test_reset(array(LabelsSettings::OPTION_NAME => array('sidebar_title' => 'Nos établissements', 'zoom' => '9')));
        $defaults = Defaults::all();

        $this->assertSame('Nos établissements', $defaults['sidebar_title']);
        $this->assertSame('9', $defaults['zoom']);
        $this->assertSame(Defaults::CENTER_LAT, $defaults['center_lat']); // non réglé : valeur du code
    }

    public function test_le_filtre_l_emporte_sur_le_reglage() {
        gfo_test_reset(array(LabelsSettings::OPTION_NAME => array('sidebar_title' => 'Réglage')));
        add_filter('geofolio_defaults', static function ($d) { $d['sidebar_title'] = 'Filtre'; return $d; });

        $this->assertSame('Filtre', Defaults::all()['sidebar_title']);
    }

    public function test_le_slug_regle_s_applique_avant_le_filtre() {
        gfo_test_reset(array(LabelsSettings::OPTION_NAME => array('place_slug' => 'etablissement')));
        $this->assertSame('etablissement', Schema::place_slug());

        add_filter('geofolio_place_slug', static function () { return 'lieu'; });
        $this->assertSame('lieu', Schema::place_slug());
    }

    public function test_les_noms_regles_remplacent_les_libelles_principaux() {
        gfo_test_reset(array(LabelsSettings::OPTION_NAME => array('place_singular' => 'Établissement', 'place_plural' => 'Établissements')));
        $labels = PlacePostType::labels();

        $this->assertSame('Établissements', $labels['name']);
        $this->assertSame('Établissement', $labels['singular_name']);
        $this->assertSame('Établissements', $labels['menu_name']);
    }

    public function test_les_noms_de_l_entite_sont_reglables() {
        gfo_test_reset(array(LabelsSettings::OPTION_NAME => array('entity_singular' => 'Pôle', 'entity_plural' => 'Pôles')));
        $labels = LabelsSettings::entity_labels(array('name' => 'Entities', 'singular_name' => 'Entity', 'menu_name' => 'Entities'));

        $this->assertSame(array('name' => 'Pôles', 'singular_name' => 'Pôle', 'menu_name' => 'Pôles'), $labels);
    }
}
