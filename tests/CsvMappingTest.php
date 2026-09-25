<?php
/**
 * Tests de la correspondance entre colonnes CSV et champs d'un lieu.
 */

use PHPUnit\Framework\TestCase;
use MappedPlaces\Import\CsvMapping;

final class CsvMappingTest extends TestCase {

    protected function tearDown(): void {
        mapl_test_reset_filters();
    }

    public function test_les_en_tetes_anglais_sont_reconnus() {
        $fields = CsvMapping::map_row(array(
            'name' => 'Linden House', 'address' => '1 Main St', 'type' => 'Library',
            'description' => 'Books', 'capacity' => '20', 'department' => '69',
        ));

        $this->assertSame('Linden House', $fields['name']);
        $this->assertSame('1 Main St', $fields['address']);
        $this->assertSame('Library', $fields['type']);
        $this->assertSame('Books', $fields['description']);
        $this->assertSame('20', $fields['capacity']);
        $this->assertSame('69', $fields['department']);
    }

    public function test_les_en_tetes_francais_sont_reconnus_avec_ou_sans_accent() {
        $fields = CsvMapping::map_row(array(
            'nom' => 'Maison', 'adresse' => '2 rue', 'capacité' => '12', 'dép' => '38',
            'code postal' => '38000', 'ville' => 'Grenoble', 'téléphone' => '04',
        ));

        $this->assertSame('Maison', $fields['name']);
        $this->assertSame('2 rue', $fields['address']);
        $this->assertSame('12', $fields['capacity']);
        $this->assertSame('38', $fields['department']);
        $this->assertSame('38000', $fields['postal_code']);
        $this->assertSame('Grenoble', $fields['city']);
        $this->assertSame('04', $fields['phone']);
    }

    public function test_une_colonne_inconnue_est_ignoree() {
        $fields = CsvMapping::map_row(array('nom' => 'A', 'couleur préférée' => 'bleu'));

        $this->assertSame(array('name' => 'A'), $fields);
    }

    public function test_les_valeurs_sont_rognees_et_les_vides_omises() {
        $fields = CsvMapping::map_row(array('nom' => '  A  ', 'ville' => '   '));

        $this->assertSame(array('name' => 'A'), $fields);
    }

    public function test_la_premiere_colonne_non_vide_d_un_champ_l_emporte() {
        $fields = CsvMapping::map_row(array('name' => '', 'nom' => 'B', 'titre' => 'C'));

        $this->assertSame('B', $fields['name']);
    }

    public function test_un_filtre_ajoute_une_colonne() {
        add_filter('mapped_places_import_columns', static function ($columns) {
            return array_merge($columns, array('adresse postale' => 'address'));
        });

        $fields = CsvMapping::map_row(array('Adresse postale' => '3 route neuve'));
        $this->assertSame('3 route neuve', $fields['address']);
    }

    public function test_un_filtre_vers_un_champ_inconnu_est_ignore() {
        add_filter('mapped_places_import_columns', static function ($columns) {
            return array_merge($columns, array('secret' => 'post_author'));
        });

        $this->assertSame(array(), CsvMapping::map_row(array('secret' => '1')));
    }

    public function test_aucune_colonne_du_coeur_n_est_propre_a_un_client() {
        $columns = implode(' ', array_keys(CsvMapping::columns()));
        foreach (array('jeunes', 'structure', 'intervention', 'postale', 'mission') as $word) {
            $this->assertStringNotContainsString($word, $columns);
        }
    }
}
