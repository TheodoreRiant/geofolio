<?php
/**
 * Tests des valeurs par défaut de la carte, partagées par le shortcode et
 * le widget Elementor, et surchargeables par le filtre geofolio_defaults.
 */

use PHPUnit\Framework\TestCase;
use Geofolio\Map\Defaults;

final class DefaultsTest extends TestCase {

    protected function tearDown(): void {
        gfo_test_reset_filters();
    }

    public function test_les_cles_attendues_sont_presentes() {
        $expected = array(
            'height', 'center_lat', 'center_lng', 'zoom', 'show_search', 'show_filter',
            'show_filters', 'show_list', 'show_fullscreen', 'sidebar_position',
            'sidebar_title', 'sidebar_subtitle', 'tile_style', 'fit_bounds',
        );
        $this->assertSame($expected, array_keys(Defaults::all()));
    }

    public function test_les_valeurs_du_coeur_sont_neutres() {
        $defaults = Defaults::all();

        $this->assertSame('600px', $defaults['height']);
        $this->assertSame('46.6034', $defaults['center_lat']);
        $this->assertSame('1.8883', $defaults['center_lng']);
        $this->assertSame('6', $defaults['zoom']);
        $this->assertSame('', $defaults['sidebar_subtitle']);
        $this->assertSame('Our locations', $defaults['sidebar_title']);
    }

    public function test_un_filtre_remplace_le_sous_titre() {
        add_filter('geofolio_defaults', static function ($defaults) {
            return array_merge($defaults, array('sidebar_subtitle' => 'Sous-titre du préréglage'));
        });

        $this->assertSame('Sous-titre du préréglage', Defaults::all()['sidebar_subtitle']);
    }

    public function test_un_filtre_ne_peut_pas_retirer_une_cle() {
        add_filter('geofolio_defaults', static function () {
            return array('zoom' => '9');
        });

        $defaults = Defaults::all();
        $this->assertSame('9', $defaults['zoom']);
        $this->assertSame('600px', $defaults['height']);
    }

    public function test_la_couleur_par_defaut_est_une_constante_filtrable() {
        $this->assertSame(Defaults::COLOR, Defaults::color());

        add_filter('geofolio_default_color', static function () {
            return '#123456';
        });
        $this->assertSame('#123456', Defaults::color());
    }

    public function test_une_couleur_invalide_du_filtre_est_ignoree() {
        add_filter('geofolio_default_color', static function () {
            return 'red;background:url(x)';
        });
        $this->assertSame(Defaults::COLOR, Defaults::color());
    }
}
