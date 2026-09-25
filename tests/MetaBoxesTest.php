<?php
/**
 * Tests des identifiants de meta box et de la lecture de la galerie.
 */

use PHPUnit\Framework\TestCase;
use MappedPlaces\Admin\MetaBoxes;
use MappedPlaces\Domain\FieldRegistry;

final class MetaBoxesTest extends TestCase {

    protected function setUp(): void {
        // Les IDs 10, 11 et 12 sont des images ; 99 ne l'est pas.
        $GLOBALS['mapl_test_images'] = array(10, 11, 12);
    }

    /* ---------------------------------------------------------------- */
    /*  Lecture des IDs de photos                                        */
    /* ---------------------------------------------------------------- */

    public function test_une_liste_valide_est_lue_dans_l_ordre() {
        $this->assertSame(array(11, 10, 12), FieldRegistry::parse_gallery_ids('[11,10,12]'));
    }

    public function test_l_ordre_est_preserve_car_la_premiere_photo_est_la_couverture() {
        $avant = FieldRegistry::parse_gallery_ids('[12,11,10]');
        $apres = FieldRegistry::parse_gallery_ids('[10,11,12]');

        $this->assertNotSame($avant, $apres);
        $this->assertSame(12, $avant[0]);
    }

    public function test_les_pieces_jointes_qui_ne_sont_pas_des_images_sont_ecartees() {
        $this->assertSame(array(10), FieldRegistry::parse_gallery_ids('[10,99]'));
    }

    public function test_les_doublons_sont_supprimes() {
        $this->assertSame(array(10, 11), FieldRegistry::parse_gallery_ids('[10,11,10,11]'));
    }

    public function test_les_ids_textuels_sont_convertis() {
        $this->assertSame(array(10, 11), FieldRegistry::parse_gallery_ids('["10","11"]'));
    }

    public function test_les_valeurs_nulles_ou_negatives_sont_ecartees() {
        $this->assertSame(array(10), FieldRegistry::parse_gallery_ids('[0,10,null,false]'));
    }

    public function test_un_json_invalide_ne_casse_rien() {
        $this->assertSame(array(), FieldRegistry::parse_gallery_ids('pas du json'));
        $this->assertSame(array(), FieldRegistry::parse_gallery_ids('{"a":1}'));
        $this->assertSame(array(), FieldRegistry::parse_gallery_ids('""'));
        $this->assertSame(array(), FieldRegistry::parse_gallery_ids(''));
        $this->assertSame(array(), FieldRegistry::parse_gallery_ids(null));
    }

    /**
     * Vider la galerie doit produire une liste vide, pas laisser l'ancienne
     * valeur en place.
     */
    public function test_une_galerie_videe_donne_une_liste_vide() {
        $this->assertSame(array(), FieldRegistry::parse_gallery_ids('[]'));
        $this->assertSame('[]', wp_json_encode(FieldRegistry::parse_gallery_ids('[]')));
    }

    /* ---------------------------------------------------------------- */
    /*  Identifiants                                                     */
    /* ---------------------------------------------------------------- */

    /**
     * WordPress utilise l'identifiant passe a add_meta_box() comme id du
     * conteneur `<div class="postbox">`. Si un champ du formulaire porte le
     * meme id, document.getElementById() renvoie le CONTENEUR (premier dans le
     * DOM) et non le champ : la lecture retourne vide et la valeur ecrite
     * atterrit sur un <div>, qui n'est jamais soumis. C'etait le cas jusqu'en
     * 2.7.0, où les deux portaient le même nom.
     */
    public function test_l_id_de_meta_box_ne_collisionne_pas_avec_l_id_de_champ() {
        $this->assertNotSame(
            MetaBoxes::GALLERY_META_BOX_ID,
            MetaBoxes::GALLERY_FIELD_ID,
            'L\'id de la meta box et celui du champ cache doivent differer.'
        );
    }

    /**
     * Le `name` du champ est la cle lue dans $_POST par save_meta_boxes() :
     * le renommer casserait l'enregistrement des galeries deja saisies.
     */
    public function test_le_nom_du_champ_reste_stable() {
        $this->assertSame('mapped_places_gallery', MetaBoxes::GALLERY_FIELD_ID);
    }

    /**
     * Le JS cible le champ par son attribut name : les deux doivent rester
     * synchronises.
     */
    public function test_le_js_cible_le_champ_par_son_name() {
        $js = file_get_contents(dirname(__DIR__) . '/assets/js/mapped-places-gallery.js');

        $this->assertStringContainsString(
            "input[name=\"" . MetaBoxes::GALLERY_FIELD_ID . "\"]",
            $js,
            'Le selecteur JS doit viser le name du champ, pas son id.'
        );
        $this->assertStringNotContainsString(
            "getElementById('" . MetaBoxes::GALLERY_FIELD_ID . "')",
            $js,
            'Cibler le champ par id rouvre la collision avec le conteneur de meta box.'
        );
    }

    /**
     * wp.media() ne construit ses etats qu'a l'ouverture : frame.state() vaut
     * `undefined` avant frame.open(). La pre-selection doit donc vivre dans un
     * gestionnaire 'open', sinon la mediatheque ne s'ouvre jamais.
     */
    public function test_la_preselection_attend_l_ouverture_de_la_mediatheque() {
        $js = file_get_contents(dirname(__DIR__) . '/assets/js/mapped-places-gallery.js');

        $position_handler = strpos($js, "frame.on('open'");
        $position_open    = strpos($js, 'frame.open();');

        $this->assertNotFalse($position_handler, 'Aucun gestionnaire frame.on(\'open\').');
        $this->assertNotFalse($position_open, 'Aucun appel a frame.open().');
        $this->assertLessThan(
            $position_open,
            $position_handler,
            'La pre-selection doit etre enregistree sur l\'evenement open, avant l\'appel a open().'
        );
    }
}
