<?php
/**
 * Tests du cache des réponses REST : clé, périmètre, invalidation.
 */

use PHPUnit\Framework\TestCase;
use Geofolio\Domain\Schema;
use Geofolio\Rest\ResponseCache;

final class ResponseCacheTest extends TestCase {

    protected function setUp(): void {
        gfo_test_reset();
        gfo_test_reset_posts();
        $GLOBALS['gfo_test_transients'] = array();
        $GLOBALS['gfo_test_locale']     = 'en_US';
    }

    protected function tearDown(): void {
        gfo_test_reset_filters();
    }

    /* ---------------------------------------------------------------- */
    /*  Clé                                                              */
    /* ---------------------------------------------------------------- */

    public function test_la_cle_porte_le_prefixe_du_plugin() {
        // uninstall.php supprime les transients geofolio_*.
        $this->assertStringStartsWith('geofolio_rest_', ResponseCache::key('places', array()));
    }

    public function test_l_ordre_des_parametres_ne_change_pas_la_cle() {
        $this->assertSame(
            ResponseCache::key('places', array('type' => 'a', 'region' => 'b')),
            ResponseCache::key('places', array('region' => 'b', 'type' => 'a'))
        );
    }

    public function test_route_parametres_et_langue_changent_la_cle() {
        $base = ResponseCache::key('places', array('type' => 'a'));

        $this->assertNotSame($base, ResponseCache::key('filters', array('type' => 'a')));
        $this->assertNotSame($base, ResponseCache::key('places', array('type' => 'b')));

        // Les libellés de types sont traduits : une réponse par langue.
        $GLOBALS['gfo_test_locale'] = 'fr_FR';
        $this->assertNotSame($base, ResponseCache::key('places', array('type' => 'a')));
    }

    public function test_une_invalidation_change_toutes_les_cles() {
        $before = ResponseCache::key('places', array());
        ResponseCache::flush();

        $this->assertNotSame($before, ResponseCache::key('places', array()));
    }

    /* ---------------------------------------------------------------- */
    /*  Périmètre                                                        */
    /* ---------------------------------------------------------------- */

    public function test_la_liste_complete_et_les_filtres_par_terme_sont_mis_en_cache() {
        $this->assertTrue(ResponseCache::is_cacheable(array()));
        $this->assertTrue(ResponseCache::is_cacheable(array('type' => 'library', 'radius' => 50)));
    }

    /** Recherche libre et proximité : autant de clés que de visiteurs. */
    public function test_recherche_et_proximite_ne_sont_pas_mises_en_cache() {
        $this->assertFalse(ResponseCache::is_cacheable(array('search' => 'lyon')));
        $this->assertFalse(ResponseCache::is_cacheable(array('lat' => 45.7, 'lng' => 4.8)));
    }

    /* ---------------------------------------------------------------- */
    /*  Mémorisation                                                     */
    /* ---------------------------------------------------------------- */

    public function test_la_reponse_n_est_construite_qu_une_fois() {
        $calls = 0;
        $build = static function () use (&$calls) {
            $calls++;
            return array('count' => 1);
        };

        $key = ResponseCache::key('places', array());
        $this->assertSame(array('count' => 1), ResponseCache::remember($key, $build));
        $this->assertSame(array('count' => 1), ResponseCache::remember($key, $build));
        $this->assertSame(1, $calls);
    }

    public function test_apres_invalidation_la_reponse_est_reconstruite() {
        $calls = 0;
        $build = static function () use (&$calls) {
            return array('count' => ++$calls);
        };

        ResponseCache::remember(ResponseCache::key('places', array()), $build);
        ResponseCache::flush();

        $this->assertSame(array('count' => 2), ResponseCache::remember(ResponseCache::key('places', array()), $build));
    }

    /* ---------------------------------------------------------------- */
    /*  Invalidation                                                     */
    /* ---------------------------------------------------------------- */

    public function test_les_ecritures_de_lieux_de_termes_et_de_reglages_invalident() {
        ResponseCache::register();

        foreach (array(
            'save_post_' . Schema::POST_TYPE, 'deleted_post', 'set_object_terms',
            'added_post_meta', 'updated_post_meta', 'deleted_post_meta',
            'created_term', 'edited_term', 'delete_term', 'updated_term_meta',
            'update_option_geofolio_settings',
        ) as $hook) {
            $this->assertTrue(has_filter($hook), $hook);
        }
    }

    public function test_une_meta_d_un_autre_type_de_contenu_n_invalide_pas() {
        $page  = gfo_test_add_post(array('post_type' => 'page'));
        $place = gfo_test_add_post(array('post_type' => Schema::POST_TYPE));
        $key   = ResponseCache::key('places', array());

        ResponseCache::on_post_meta_change(1, $page->ID, 'color');
        $this->assertSame($key, ResponseCache::key('places', array()));

        // Le verrou d'édition change à chaque ouverture de l'éditeur.
        ResponseCache::on_post_meta_change(1, $place->ID, '_edit_lock');
        $this->assertSame($key, ResponseCache::key('places', array()));

        ResponseCache::on_post_meta_change(1, $place->ID, '_geofolio_city');
        $this->assertNotSame($key, ResponseCache::key('places', array()));
    }

    public function test_un_terme_d_une_autre_taxonomie_n_invalide_pas() {
        $key = ResponseCache::key('places', array());

        ResponseCache::on_term_change(3, 3, 'category');
        $this->assertSame($key, ResponseCache::key('places', array()));

        ResponseCache::on_term_change(3, 3, Schema::TAX_ENTITY);
        $this->assertNotSame($key, ResponseCache::key('places', array()));
    }
}
