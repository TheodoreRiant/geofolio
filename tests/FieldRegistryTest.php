<?php
/**
 * Tests du registre des champs d'un lieu : clés de meta et nettoyage,
 * partagés par les meta boxes, l'API REST et l'import.
 */

use PHPUnit\Framework\TestCase;
use MappedPlaces\Domain\FieldRegistry;

final class FieldRegistryTest extends TestCase {

    protected function setUp(): void {
        $GLOBALS['mapl_test_images'] = array(10, 11, 12);
    }

    public function test_le_registre_couvre_les_douze_champs() {
        $this->assertSame(array(
            'address', 'postal_code', 'city', 'latitude', 'longitude', 'phone',
            'email', 'website', 'manager', 'people',
            'opening_hours', 'gallery',
        ), FieldRegistry::fields());
    }

    public function test_chaque_champ_a_une_cle_de_meta_distincte() {
        $keys = array_map(array(FieldRegistry::class, 'meta_key'), FieldRegistry::fields());

        $this->assertCount(count(FieldRegistry::fields()), array_unique($keys));
        foreach ($keys as $key) {
            $this->assertStringStartsWith('_', $key);
        }
    }

    public function test_un_champ_inconnu_leve_une_exception() {
        $this->expectException(InvalidArgumentException::class);
        FieldRegistry::meta_key('post_author');
    }

    public function test_chaque_champ_a_le_nettoyage_de_son_type() {
        $table = FieldRegistry::sanitizers();

        $this->assertSame('esc_url_raw', $table[FieldRegistry::meta_key('website')]);
        $this->assertSame('sanitize_email', $table[FieldRegistry::meta_key('email')]);
        $this->assertSame('sanitize_textarea_field', $table[FieldRegistry::meta_key('opening_hours')]);
        $this->assertSame(array(FieldRegistry::class, 'sanitize_coordinate'), $table[FieldRegistry::meta_key('latitude')]);
        $this->assertSame(array(FieldRegistry::class, 'sanitize_coordinate'), $table[FieldRegistry::meta_key('longitude')]);
        $this->assertSame(array(FieldRegistry::class, 'sanitize_gallery_json'), $table[FieldRegistry::meta_key('gallery')]);
        foreach (array('address', 'postal_code', 'city', 'manager', 'phone') as $field) {
            $this->assertSame('sanitize_text_field', $table[FieldRegistry::meta_key($field)], $field);
        }
        foreach ($table as $key => $callback) {
            $this->assertTrue(is_callable($callback), $key);
        }
    }

    public function test_le_nettoyage_d_un_champ_passe_par_le_registre() {
        $this->assertSame('', FieldRegistry::sanitize('website', 'javascript:alert(1)'));
        $this->assertSame('45.7', FieldRegistry::sanitize('latitude', '45,7'));
    }

    public function test_le_champ_de_formulaire_porte_le_prefixe_du_plugin() {
        $this->assertSame('mapped_places_website', FieldRegistry::form_field('website'));
        $this->assertSame('mapped_places_gallery', FieldRegistry::form_field('gallery'));
    }

    public function test_une_coordonnee_decimale_est_conservee() {
        $this->assertSame('45.764', FieldRegistry::sanitize_coordinate('45.764'));
        $this->assertSame('-4.5', FieldRegistry::sanitize_coordinate(' -4.5 '));
    }

    public function test_une_coordonnee_non_numerique_donne_une_valeur_vide() {
        foreach (array('abc', '45.7<script>', '', null, array(1)) as $value) {
            $this->assertSame('', FieldRegistry::sanitize_coordinate($value));
        }
    }

    public function test_la_galerie_ne_garde_que_les_images_en_json() {
        $this->assertSame('[10]', FieldRegistry::sanitize_gallery_json('[10,99,"x"]'));
        $this->assertSame('[]', FieldRegistry::sanitize_gallery_json('<script>'));
    }
}
