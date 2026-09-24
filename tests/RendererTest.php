<?php
/**
 * Tests du rendu HTML de la carte, commun au shortcode et au widget.
 */

use PHPUnit\Framework\TestCase;
use Geofolio\Admin\SettingsPage;
use Geofolio\Map\Defaults;
use Geofolio\Map\Renderer;
use Geofolio\Map\TileProviders;

final class RendererTest extends TestCase {

    protected function setUp(): void {
        gfo_test_reset();
    }

    protected function tearDown(): void {
        gfo_test_reset_filters();
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
        gfo_test_reset(array(
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
        $this->assertStringContainsString('gfo-place-list', self::render());
        $this->assertStringNotContainsString('gfo-place-list', self::render(array('show_list' => 'false')));
    }

    public function test_sans_recherche_filtre_ni_liste_pas_de_sidebar() {
        $html = self::render(array('show_search' => 'false', 'show_filter' => 'false', 'show_list' => 'false'));

        $this->assertStringNotContainsString('gfo-map-sidebar', $html);
        $this->assertStringContainsString('gfo-map-canvas', $html);
    }

    public function test_les_attributs_du_conteneur_ont_des_types_imposes() {
        $attributes = Renderer::container_attributes(array_merge(Defaults::all(), array(
            'center_lat'       => '45.5abc',
            'zoom'             => '-7',
            'sidebar_position' => 'right',
        )), 'gfo-map-x');

        $this->assertSame('gfo-map-x', $attributes['id']);
        $this->assertSame('gfo-map-container gfo-sidebar-right', $attributes['class']);
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

        $this->assertStringNotContainsString('gfo-filter-select', $html);
    }

    public function test_les_attributs_absents_prennent_les_valeurs_par_defaut() {
        $html = Renderer::render(array('sidebar_title' => 'Seul titre'));

        $this->assertStringContainsString('Seul titre', $html);
        $this->assertStringContainsString('height: 600px;', $html);
    }

    /** Le nombre de résultats change à chaque filtre : il est annoncé aux lecteurs d'écran. */
    public function test_le_compteur_de_resultats_est_annonce() {
        $this->assertMatchesRegularExpression('/<span class="gfo-results-count" aria-live="polite"[^>]*>/', self::render());
    }
}
