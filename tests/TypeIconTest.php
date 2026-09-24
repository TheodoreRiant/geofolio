<?php
/**
 * Tests de la résolution de l'icône et du libellé d'un type de lieu :
 * meta de terme, puis filtre, puis épingle.
 */

use PHPUnit\Framework\TestCase;
use Geofolio\Domain\Schema;
use Geofolio\Domain\Icons;
use Geofolio\Domain\Taxonomies;
use Geofolio\Rest\PlacesController;

final class TypeIconTest extends TestCase {

    protected function setUp(): void {
        gfo_test_reset_terms();
    }

    protected function tearDown(): void {
        gfo_test_reset_filters();
    }

    public function test_sans_meta_ni_filtre_l_icone_est_l_epingle() {
        $term = gfo_test_add_term(Schema::TAX_TYPE, 'Foyer', 'foyer');

        $this->assertSame('pin', Icons::term_icon($term));
    }

    public function test_le_filtre_fournit_l_icone_d_un_terme_sans_meta() {
        $term = gfo_test_add_term(Schema::TAX_TYPE, 'Foyer', 'foyer');
        add_filter('geofolio_type_icon', static function ($icon, $slug, $name) {
            return $slug === 'foyer' && $name === 'Foyer' ? 'home' : $icon;
        }, 10, 3);

        $this->assertSame('home', Icons::term_icon($term));
    }

    public function test_la_meta_du_terme_est_prioritaire_sur_le_filtre() {
        $term = gfo_test_add_term(Schema::TAX_TYPE, 'Foyer', 'foyer', array(Icons::TERM_META => 'sun'));
        add_filter('geofolio_type_icon', static function () {
            return 'home';
        });

        $this->assertSame('sun', Icons::term_icon($term));
    }

    public function test_une_meta_ou_un_filtre_inconnus_retombent_sur_l_epingle() {
        $term = gfo_test_add_term(Schema::TAX_TYPE, 'Foyer', 'foyer', array(Icons::TERM_META => 'disparue'));
        add_filter('geofolio_type_icon', static function () {
            return 'inexistante';
        });

        $this->assertSame('pin', Icons::term_icon($term));
    }

    public function test_le_libelle_est_le_nom_en_texte_brut_sauf_filtre() {
        $term = gfo_test_add_term(Schema::TAX_TYPE, 'Soins &amp; accueil', 'soins');
        $this->assertSame('Soins & accueil', Icons::term_label($term));

        add_filter('geofolio_type_label', static function ($label, $filtered_term) {
            return $filtered_term->slug === 'soins' ? 'SOINS' : $label;
        }, 10, 2);
        $this->assertSame('SOINS', Icons::term_label($term));
    }

    public function test_la_description_d_un_type_contient_ses_cinq_champs() {
        $term = gfo_test_add_term(Schema::TAX_TYPE, 'Foyer', 'foyer', array(Icons::TERM_META => 'home'));

        $this->assertSame(array(
            'slug'  => 'foyer',
            'name'  => 'Foyer',
            'label' => 'Foyer',
            'icon'  => 'home',
            'path'  => Icons::path('home'),
        ), Icons::describe_term($term));
    }

    public function test_le_catalogue_des_types_suit_l_ordre_des_termes_et_passe_par_un_filtre() {
        gfo_test_add_term(Schema::TAX_TYPE, 'Foyer', 'foyer');
        gfo_test_add_term(Schema::TAX_TYPE, 'Atelier', 'atelier');
        gfo_test_add_term(Schema::TAX_TYPE, 'Vide', 'vide', array(), 0);

        $slugs = array_column(Icons::type_catalog(), 'slug');
        $this->assertSame(array('atelier', 'foyer'), $slugs);

        add_filter('geofolio_type_catalog', static function ($catalog) {
            return array_reverse($catalog);
        });
        $this->assertSame(array('foyer', 'atelier'), array_column(Icons::type_catalog(), 'slug'));
    }

    public function test_l_enregistrement_du_formulaire_refuse_une_icone_inconnue() {
        $term = gfo_test_add_term(Schema::TAX_TYPE, 'Foyer', 'foyer', array(Icons::TERM_META => 'home'));

        Taxonomies::store_type_icon($term->term_id, 'sun');
        $this->assertSame('sun', get_term_meta($term->term_id, Icons::TERM_META, true));

        Taxonomies::store_type_icon($term->term_id, '"><script>');
        $this->assertSame('', get_term_meta($term->term_id, Icons::TERM_META, true));
    }

    public function test_la_route_filters_complete_chaque_type() {
        gfo_test_add_term(Schema::TAX_TYPE, 'Foyer', 'foyer', array(Icons::TERM_META => 'home'));
        $types = PlacesController::describe_filter_types(array(
            array('id' => 1, 'slug' => 'foyer', 'name' => 'Foyer', 'count' => 1),
            array('id' => 2, 'slug' => 'absent', 'name' => 'Absent', 'count' => 1),
        ));

        $this->assertSame('home', $types[0]['icon']);
        $this->assertSame(Icons::path('home'), $types[0]['path']);
        $this->assertSame(1, $types[0]['id']);
        $this->assertSame('pin', $types[1]['icon']);
        $this->assertSame('Absent', $types[1]['label']);
    }
}
