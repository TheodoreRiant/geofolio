<?php
/**
 * Personnes d'un lieu (rôle + nom) : lecture, nettoyage, repli sur l'ancien
 * champ « manager ».
 */

use Geofolio\Domain\FieldRegistry;
use Geofolio\Domain\People;
use PHPUnit\Framework\TestCase;

class PeopleTest extends TestCase {

    public function test_une_liste_json_est_lue_dans_l_ordre(): void {
        $people = People::parse('[{"role":"Directeur/trice","name":"Marie Beton"},{"role":"Secrétaire général","name":"Antonin Klark"}]');
        $this->assertSame(array(
            array('role' => 'Directeur/trice', 'name' => 'Marie Beton'),
            array('role' => 'Secrétaire général', 'name' => 'Antonin Klark'),
        ), $people);
    }

    public function test_les_entrees_sans_nom_sont_ecartees_et_le_html_nettoye(): void {
        $people = People::parse('[{"role":"Directeur","name":""},{"role":"<b>Chef</b>","name":"Jean <b>Dupont</b>"},"pas un objet"]');
        $this->assertCount(1, $people);
        $this->assertSame('Chef', $people[0]['role']);
        $this->assertSame('Jean Dupont', $people[0]['name']);
    }

    public function test_un_json_invalide_donne_une_liste_vide(): void {
        $this->assertSame(array(), People::parse('{pas du json'));
        $this->assertSame(array(), People::parse(''));
        $this->assertSame('[]', People::sanitize_json('{pas du json'));
    }

    public function test_le_nettoyage_du_registre_renvoie_du_json_valide(): void {
        $json = FieldRegistry::sanitize('people', '[{"role":"Directrice","name":"Marie Beton"}]');
        $this->assertSame('[{"role":"Directrice","name":"Marie Beton"}]', $json);
    }

    public function test_l_ancien_champ_manager_devient_des_personnes_avec_le_role_par_defaut(): void {
        $people = People::from_legacy('Marie Beton, Antonin Klark', 'Directeur/trice');
        $this->assertSame(array(
            array('role' => 'Directeur/trice', 'name' => 'Marie Beton'),
            array('role' => 'Directeur/trice', 'name' => 'Antonin Klark'),
        ), $people);
        $this->assertSame(array(), People::from_legacy('', 'Directeur/trice'));
    }

    public function test_les_noms_reconstituent_l_ancien_champ(): void {
        $people = People::parse('[{"role":"Directrice","name":"Marie Beton"},{"role":"Secrétaire général","name":"Antonin Klark"}]');
        $this->assertSame('Marie Beton, Antonin Klark', People::names($people));
    }

    public function test_la_liste_est_plafonnee(): void {
        $entries = array_fill(0, People::MAX + 5, array('role' => 'r', 'name' => 'n'));
        $this->assertCount(People::MAX, People::parse(wp_json_encode($entries)));
    }
}
