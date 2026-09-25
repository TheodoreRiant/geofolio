<?php
/**
 * Tests de l'ordre des images du popup (VAD-6).
 *
 * L'image à la une doit rester la première image affichée, les photos de la
 * galerie venant ensuite — et jamais en double.
 */

use PHPUnit\Framework\TestCase;
use MappedPlaces\Rest\PlaceMapper;

final class PopupImagesTest extends TestCase {

    protected function setUp(): void {
        // Les IDs 5, 10, 11 et 12 sont des images ; 99 ne l'est pas.
        $GLOBALS['mapl_test_images'] = array(5, 10, 11, 12);
    }

    public function test_la_couverture_passe_en_tete_devant_la_galerie() {
        $this->assertSame(array(5, 10, 11), PlaceMapper::build_popup_image_ids(5, array(10, 11)));
    }

    public function test_la_couverture_deja_dans_la_galerie_n_est_pas_dupliquee() {
        $this->assertSame(array(11, 10, 12), PlaceMapper::build_popup_image_ids(11, array(10, 11, 12)));
    }

    public function test_sans_couverture_la_galerie_est_inchangee() {
        $this->assertSame(array(10, 11), PlaceMapper::build_popup_image_ids(0, array(10, 11)));
    }

    public function test_la_couverture_seule_donne_une_liste_d_une_image() {
        $this->assertSame(array(5), PlaceMapper::build_popup_image_ids(5, array()));
    }

    public function test_aucune_image_donne_une_liste_vide() {
        $this->assertSame(array(), PlaceMapper::build_popup_image_ids(0, array()));
    }

    public function test_une_couverture_qui_n_est_pas_une_image_est_ignoree() {
        $this->assertSame(array(10), PlaceMapper::build_popup_image_ids(99, array(10)));
    }

    public function test_un_id_de_couverture_textuel_est_accepte() {
        // get_post_thumbnail_id() peut renvoyer une chaîne selon la version de WP.
        $this->assertSame(array(5, 10), PlaceMapper::build_popup_image_ids('5', array(10)));
    }
}
