<?php
/**
 * Tests du rendu HTML de la carte, commun au shortcode et au widget.
 */

use PHPUnit\Framework\TestCase;
use MappedPlaces\Admin\SettingsPage;
use MappedPlaces\Map\Defaults;
use MappedPlaces\Map\Renderer;
use MappedPlaces\Map\TileProviders;

final class RendererTest extends TestCase {

    protected function setUp(): void {
        mapl_test_reset();
    }

    protected function tearDown(): void {
        mapl_test_reset_filters();
    }

    private static function render(array $atts = array()) {
        return Renderer::render(array_merge(Defaults::all(), $atts));
    }

    public function test_un_titre_avec_crochet_et_guillemet_est_echappe() {
        $html = self::render(array('sidebar_title' => 'Titre ] "cité" <b>'));

        $this->assertStringContainsString('Titre ] &quot;cité&quot; &lt;b&gt;', $html);
        $this->assertStringNotContainsString('<b>', $html);
    }

    public function test_sans_fond_demande_le_reglage_du_site_s_applique() {
        mapl_test_reset(array(
            SettingsPage::OPTION_NAME => array('tile_style' => 'osm'),
        ));

        $this->assertSame('osm', Renderer::resolve_tile_style(''));
    }

    public function test_sans_fond_ni_reglage_le_fond_par_defaut_s_applique() {
        $this->assertSame(TileProviders::DEFAULT_ID, Renderer::resolve_tile_style(''));
    }

    public function test_un_fond_demande_par_la_page_est_conserve() {
        $this->assertSame('osm', Renderer::resolve_tile_style('osm'));
    }

    public function test_show_list_faux_n_emet_pas_la_liste() {
        $this->assertStringContainsString('mapl-place-list', self::render());
        $this->assertStringNotContainsString('mapl-place-list', self::render(array('show_list' => 'false')));
    }

    public function test_sans_recherche_filtre_ni_liste_pas_de_sidebar() {
        $html = self::render(array('show_search' => 'false', 'show_filter' => 'false', 'show_list' => 'false'));

        $this->assertStringNotContainsString('mapl-map-sidebar', $html);
        $this->assertStringContainsString('mapl-map-canvas', $html);
    }

    public function test_les_attributs_du_conteneur_ont_des_types_imposes() {
        $attributes = Renderer::container_attributes(array_merge(Defaults::all(), array(
            'center_lat'       => '45.5abc',
            'zoom'             => '-7',
            'sidebar_position' => 'right',
        )), 'mapl-map-x');

        $this->assertSame('mapl-map-x', $attributes['id']);
        $this->assertSame('mapl-map-container mapl-sidebar-right', $attributes['class']);
        $this->assertSame('45.5', $attributes['data-center-lat']);
        $this->assertSame('7', $attributes['data-zoom']);
        $this->assertArrayNotHasKey('data-types', $attributes);
        $this->assertArrayNotHasKey('data-regions', $attributes);
    }

    public function test_une_hauteur_invalide_retombe_sur_la_valeur_par_defaut() {
        $html = self::render(array('height' => '1px;background:red'));

        $this->assertStringContainsString('height: 600px;', $html);
    }

    public function test_l_ancien_attribut_show_filters_reste_compris() {
        $html = self::render(array('show_filter' => '', 'show_filters' => 'false', 'show_search' => 'true'));

        $this->assertStringNotContainsString('mapl-filter-select', $html);
    }

    public function test_les_attributs_absents_prennent_les_valeurs_par_defaut() {
        $html = Renderer::render(array('sidebar_title' => 'Seul titre'));

        $this->assertStringContainsString('Seul titre', $html);
        $this->assertStringContainsString('height: 600px;', $html);
    }

    /** Le nombre de résultats change à chaque filtre : il est annoncé aux lecteurs d'écran. */
    public function test_le_compteur_de_resultats_est_annonce() {
        $this->assertMatchesRegularExpression('/<span class="mapl-results-count" aria-live="polite"[^>]*>/', self::render());
    }

    public function test_par_defaut_la_hauteur_est_posee_sur_le_wrapper() {
        $this->assertStringContainsString('class="mapl-map-wrapper" style="height: 600px;"', self::render());
    }

    /** Hauteur gérée par le conteneur (widget Elementor responsive). */
    public function test_la_hauteur_container_laisse_le_conteneur_decider() {
        $html = self::render(array('height' => 'container'));

        $this->assertStringContainsString('<div class="mapl-map-wrapper">', $html);
        $this->assertMatchesRegularExpression('/class="mapl-map-container[^"]*mapl-map-container--sized/', $html);
    }

    public function test_une_hauteur_vide_retombe_sur_la_valeur_par_defaut() {
        $this->assertStringContainsString('style="height: 600px;"', self::render(array('height' => '')));
    }

    public function test_la_vue_s_ajuste_aux_lieux_par_defaut() {
        $this->assertStringContainsString('data-fit-bounds="true"', self::render());
    }

    public function test_sans_ajustement_la_vue_garde_centre_et_zoom() {
        $this->assertStringContainsString('data-fit-bounds="false"', self::render(array('fit_bounds' => 'false')));
    }
}
