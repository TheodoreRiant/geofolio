<?php
/**
 * Textes renvoyés par l'API en texte brut (VAD-12).
 *
 * get_the_title() passe par wptexturize, qui produit des entités HTML
 * (&rsquo;, &laquo;…). Le JS échappe tout ce qu'il affiche : l'API doit donc
 * renvoyer du texte brut, sinon l'entité s'affiche telle quelle.
 */

use PHPUnit\Framework\TestCase;
use Geofolio\Rest\PlaceMapper;

final class PlainTextTest extends TestCase {

    public function test_l_apostrophe_typographique_est_decodee() {
        $this->assertSame('Food Truck Les Saveurs d’Élise', PlaceMapper::plain_text('Food Truck Les Saveurs d&rsquo;Élise'));
    }

    public function test_les_guillemets_et_l_esperluette_sont_decodes() {
        // &nbsp; devient une espace insécable (U+00A0), pas une espace simple.
        $this->assertSame("«\u{00A0}Maison\u{00A0}» & “Jardin” — l'été", PlaceMapper::plain_text('&laquo;&nbsp;Maison&nbsp;&raquo; &amp; &#8220;Jardin&#8221; &#8212; l&#039;été'));
    }

    public function test_un_texte_deja_brut_est_inchange() {
        $this->assertSame('MECS Horizon', PlaceMapper::plain_text('MECS Horizon'));
    }

    public function test_une_balise_reste_du_texte_et_n_est_pas_retiree() {
        // Le JS l'échappera à l'affichage : elle s'affichera en texte.
        $this->assertSame('<b>Gras</b>', PlaceMapper::plain_text('&lt;b&gt;Gras&lt;/b&gt;'));
    }

    public function test_une_valeur_vide_ou_nulle_donne_une_chaine_vide() {
        $this->assertSame('', PlaceMapper::plain_text(null));
        $this->assertSame('', PlaceMapper::plain_text(''));
    }

    public function test_les_noms_de_termes_sont_decodes() {
        $this->assertSame(array('Accueil & hébergement', 'MECS'), PlaceMapper::plain_names(array('Accueil &amp; hébergement', 'MECS')));
    }

    public function test_une_erreur_de_termes_donne_une_liste_vide() {
        $this->assertSame(array(), PlaceMapper::plain_names(new \WP_Error('invalid_taxonomy', 'x')));
        $this->assertSame(array(), PlaceMapper::plain_names(false));
    }
}
