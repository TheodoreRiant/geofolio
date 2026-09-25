<?php
/**
 * Tests du bloc « Mapped Places Map » : attributs du bloc → attributs du rendu
 * partagé (même Renderer que le shortcode et le widget Elementor).
 */

use PHPUnit\Framework\TestCase;
use MappedPlaces\Blocks\MapBlock;
use MappedPlaces\Map\Defaults;

final class MapBlockTest extends TestCase {

    const BLOCK_JSON = __DIR__ . '/../blocks/map/src/block.json';

    protected function setUp(): void {
        mapl_test_reset();
    }

    protected function tearDown(): void {
        mapl_test_reset_filters();
    }

    private static function block(): array {
        return json_decode((string) file_get_contents(self::BLOCK_JSON), true);
    }

    public function test_le_bloc_porte_le_nom_et_le_domaine_du_plugin() {
        $block = self::block();

        $this->assertSame('mapped-places/map', $block['name']);
        $this->assertSame('mapped-places', $block['textdomain']);
        $this->assertSame('file:./render.php', $block['render']);
    }

    /** Chaque réglage du rendu partagé a son attribut de bloc (sauf l'ancien nom show_filters). */
    public function test_les_attributs_couvrent_les_valeurs_partagees() {
        $expected = array_values(array_diff(array_keys(Defaults::all()), array('show_filters')));
        $actual   = array_keys(self::block()['attributes']);
        sort($expected);
        sort($actual);

        $this->assertSame($expected, $actual);
    }

    public function test_les_booleens_deviennent_des_drapeaux_du_rendu() {
        $atts = MapBlock::attributes_to_atts(array('show_search' => false, 'show_list' => true, 'fit_bounds' => false));

        $this->assertSame('false', $atts['show_search']);
        $this->assertSame('true', $atts['show_list']);
        $this->assertSame('false', $atts['fit_bounds']);
    }

    public function test_les_nombres_et_textes_sont_convertis_en_chaines() {
        $atts = MapBlock::attributes_to_atts(array('zoom' => 9, 'center_lat' => 45.76, 'sidebar_title' => 'Nos lieux'));

        $this->assertSame('9', $atts['zoom']);
        $this->assertSame('45.76', $atts['center_lat']);
        $this->assertSame('Nos lieux', $atts['sidebar_title']);
    }

    public function test_un_attribut_absent_garde_la_valeur_partagee() {
        $atts = MapBlock::attributes_to_atts(array());

        $this->assertSame(Defaults::all()['height'], $atts['height']);
        $this->assertSame(Defaults::all()['sidebar_title'], $atts['sidebar_title']);
    }

    public function test_un_attribut_inconnu_est_ignore() {
        $this->assertArrayNotHasKey('onload', MapBlock::attributes_to_atts(array('onload' => 'x')));
    }

    public function test_un_tableau_a_la_place_d_un_texte_est_ignore() {
        $this->assertSame(Defaults::all()['sidebar_title'], MapBlock::attributes_to_atts(array('sidebar_title' => array('x')))['sidebar_title']);
    }
}
