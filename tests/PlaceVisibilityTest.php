<?php
/**
 * Visibilité de la route détail /etablissement/{id}.
 *
 * La route est publique : un brouillon (ex. une copie tout juste dupliquée,
 * VAD-7) ne doit pas y être lisible par un visiteur anonyme.
 */

use PHPUnit\Framework\TestCase;
use Geofolio\Domain\Schema;
use Geofolio\Rest\PlaceMapper;

final class PlaceVisibilityTest extends TestCase {

    protected function setUp(): void {
        gfo_test_reset_posts(array());
    }

    public function test_un_etablissement_publie_est_visible_de_tous() {
        $post = gfo_test_add_post(array('post_type' => Schema::POST_TYPE, 'post_status' => 'publish'));

        $this->assertTrue(PlaceMapper::is_visible($post));
    }

    public function test_un_brouillon_est_invisible_pour_un_visiteur() {
        $post = gfo_test_add_post(array('post_type' => Schema::POST_TYPE, 'post_status' => 'draft'));

        $this->assertFalse(PlaceMapper::is_visible($post));
    }

    public function test_un_brouillon_reste_visible_pour_qui_peut_le_lire() {
        $post = gfo_test_add_post(array('post_type' => Schema::POST_TYPE, 'post_status' => 'draft'));
        $GLOBALS['gfo_test_caps'] = array('read_post');

        $this->assertTrue(PlaceMapper::is_visible($post));
    }

    public function test_un_autre_type_de_contenu_n_est_jamais_expose() {
        $post = gfo_test_add_post(array('post_type' => 'page', 'post_status' => 'publish'));

        $this->assertFalse(PlaceMapper::is_visible($post));
        $this->assertFalse(PlaceMapper::is_visible(null));
    }
}
