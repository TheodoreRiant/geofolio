<?php
/**
 * Tests de la bibliothèque d'icônes génériques des types de lieux.
 */

use PHPUnit\Framework\TestCase;
use MappedPlaces\Domain\Icons;

final class IconsTest extends TestCase {

    protected function tearDown(): void {
        mapl_test_reset_filters();
    }

    public function test_la_bibliotheque_compte_une_vingtaine_d_icones() {
        $this->assertGreaterThanOrEqual(18, count(Icons::all()));
    }

    public function test_chaque_icone_a_une_cle_en_minuscules_et_un_trace() {
        foreach (Icons::all() as $key => $path) {
            $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $key);
            $this->assertNotSame('', trim($path), $key);
            $this->assertStringStartsWith('<', $path, $key);
        }
    }

    public function test_l_epingle_de_repli_existe() {
        $this->assertArrayHasKey(Icons::FALLBACK, Icons::all());
    }

    public function test_aucune_cle_ne_porte_un_nom_metier() {
        $keys = implode(' ', array_keys(Icons::all()));
        foreach (array('mecs', 'itep', 'safren', 'foyer') as $word) {
            $this->assertStringNotContainsString($word, $keys);
        }
    }

    public function test_le_filtre_peut_ajouter_une_icone() {
        add_filter('mapped_places_icons', static function ($icons) {
            return array_merge($icons, array('tree' => '<circle cx="12" cy="12" r="4"/>'));
        });

        $this->assertSame('<circle cx="12" cy="12" r="4"/>', Icons::path('tree'));
    }

    public function test_une_icone_ajoutee_avec_une_cle_invalide_est_ignoree() {
        add_filter('mapped_places_icons', static function ($icons) {
            return array_merge($icons, array('Bad Key' => '<circle r="1"/>', 'empty' => ''));
        });

        $icons = Icons::all();
        $this->assertArrayNotHasKey('Bad Key', $icons);
        $this->assertArrayNotHasKey('empty', $icons);
    }

    public function test_le_trace_d_une_icone_ne_garde_que_des_formes_svg() {
        add_filter('mapped_places_icons', static function ($icons) {
            return array_merge($icons, array('evil' => '<script>alert(1)</script><path d="M1 1"/>'));
        });

        $path = Icons::path('evil');
        $this->assertStringNotContainsString('<script', $path);
        $this->assertStringContainsString('<path', $path);
    }

    public function test_une_cle_inconnue_retombe_sur_l_epingle() {
        $this->assertSame(Icons::path(Icons::FALLBACK), Icons::path('inconnue'));
    }

    public function test_is_valid_reconnait_les_cles_de_la_bibliotheque() {
        $this->assertTrue(Icons::is_valid('home'));
        $this->assertFalse(Icons::is_valid('inconnue'));
        $this->assertFalse(Icons::is_valid(array('home')));
    }
}
