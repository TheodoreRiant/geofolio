<?php
/**
 * Tests de la duplication d'un établissement (VAD-7).
 */

use PHPUnit\Framework\TestCase;
use Geofolio\Domain\Schema;
use Geofolio\Admin\Duplicate;

final class DuplicateTest extends TestCase {

    /** @var WP_Post */
    private $source;

    protected function setUp(): void {
        gfo_test_reset_posts();

        $this->source = gfo_test_add_post(
            array(
                'post_type'    => Schema::POST_TYPE,
                'post_status'  => 'publish',
                'post_title'   => 'Prévention spécialisée',
                'post_content' => 'Mission "d\'accueil" C:\\dossier',
                'post_excerpt' => '120 jeunes',
                'post_author'  => 1,
            ),
            array(
                '_gfo_adresse'   => '12 rue de la Paix',
                '_gfo_latitude'  => '45.76',
                '_gfo_directeur' => 'Jeanne Martin, Paul Durand',
                '_gfo_horaires'  => 'Lun-Ven 9h-17h',
                '_gfo_gallery'   => '[10,11]',
                '_thumbnail_id'    => '5',
                '_edit_lock'       => '1700000000:1',
                '_edit_last'       => '1',
                '_wp_old_slug'     => 'ancien-slug',
                '_gfo_liste'     => array('a' => 1),
            ),
            array(
                Schema::TAX_ENTITY => array(3),
                Schema::TAX_TYPE   => array(8, 9),
            )
        );
    }

    private function copy_of($id) {
        return get_post($id);
    }

    /* ---------------------------------------------------------------- */
    /*  Service de duplication                                           */
    /* ---------------------------------------------------------------- */

    public function test_la_copie_est_un_brouillon_suffixe_copie() {
        $copy = $this->copy_of(Duplicate::duplicate($this->source));

        $this->assertSame(Schema::POST_TYPE, $copy->post_type);
        $this->assertSame('draft', $copy->post_status);
        $this->assertSame('Prévention spécialisée (copy)', $copy->post_title);
        $this->assertSame(7, $copy->post_author, 'La copie appartient à la personne qui duplique.');
    }

    public function test_le_contenu_et_l_extrait_sont_repris_a_l_identique() {
        $copy = $this->copy_of(Duplicate::duplicate($this->source));

        // Guillemets et antislash survivent à l'aller-retour d'échappement.
        $this->assertSame($this->source->post_content, $copy->post_content);
        $this->assertSame('120 jeunes', $copy->post_excerpt);
    }

    public function test_les_metas_du_lieu_sont_copiees() {
        $id = Duplicate::duplicate($this->source);

        $this->assertSame('12 rue de la Paix', get_post_meta($id, '_gfo_adresse', true));
        $this->assertSame('45.76', get_post_meta($id, '_gfo_latitude', true));
        $this->assertSame('Jeanne Martin, Paul Durand', get_post_meta($id, '_gfo_directeur', true));
        $this->assertSame('Lun-Ven 9h-17h', get_post_meta($id, '_gfo_horaires', true));
    }

    public function test_couverture_et_galerie_reprennent_les_memes_pieces_jointes() {
        $id = Duplicate::duplicate($this->source);

        $this->assertSame('5', get_post_meta($id, '_thumbnail_id', true));
        $this->assertSame('[10,11]', get_post_meta($id, '_gfo_gallery', true));
    }

    public function test_une_meta_serialisee_est_recopiee_sans_double_serialisation() {
        $id = Duplicate::duplicate($this->source);

        $this->assertSame(array('a' => 1), get_post_meta($id, '_gfo_liste', true));
    }

    public function test_les_metas_techniques_ne_sont_pas_copiees() {
        $id   = Duplicate::duplicate($this->source);
        $keys = array_keys(get_post_meta($id));

        $this->assertNotContains('_edit_lock', $keys);
        $this->assertNotContains('_edit_last', $keys);
        $this->assertNotContains('_wp_old_slug', $keys);
    }

    public function test_les_taxonomies_sont_copiees() {
        $id = Duplicate::duplicate($this->source);

        $this->assertSame(array(3), wp_get_object_terms($id, Schema::TAX_ENTITY));
        $this->assertSame(array(8, 9), wp_get_object_terms($id, Schema::TAX_TYPE));
    }

    public function test_l_original_n_est_pas_modifie() {
        Duplicate::duplicate($this->source);

        $this->assertSame('publish', get_post($this->source->ID)->post_status);
        $this->assertSame('Prévention spécialisée', get_post($this->source->ID)->post_title);
        $this->assertSame('1700000000:1', get_post_meta($this->source->ID, '_edit_lock', true));
    }

    public function test_un_echec_de_creation_est_remonte_sans_copie_partielle() {
        $GLOBALS['gfo_test_insert_error'] = new \WP_Error('db_insert_error', 'Erreur base');

        $result = Duplicate::duplicate($this->source);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertCount(1, $GLOBALS['gfo_test_posts']);
    }

    public function test_copyable_meta_filtre_seulement_la_liste_d_exclusion() {
        $kept = Duplicate::copyable_meta(array(
            '_gfo_city' => array('Lyon'),
            '_edit_lock'   => array('x'),
            '_thumbnail_id' => array('5'),
        ));

        $this->assertSame(array('_gfo_city', '_thumbnail_id'), array_keys($kept));
    }

    /* ---------------------------------------------------------------- */
    /*  Contrôle de la requête (nonce + droits)                          */
    /* ---------------------------------------------------------------- */

    private function valid_query() {
        return array(
            'post'     => (string) $this->source->ID,
            '_wpnonce' => 'valid-' . Duplicate::nonce_action($this->source->ID),
        );
    }

    public function test_une_requete_valide_cree_la_copie() {
        $result = Duplicate::process_request($this->valid_query());

        $this->assertIsInt($result);
        $this->assertSame('draft', get_post($result)->post_status);
    }

    public function test_une_requete_sans_nonce_est_refusee() {
        $query = $this->valid_query();
        unset($query['_wpnonce']);

        $result = Duplicate::process_request($query);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('geofolio_duplicate_nonce', $result->get_error_code());
        $this->assertCount(1, $GLOBALS['gfo_test_posts']);
    }

    public function test_un_nonce_emis_pour_un_autre_etablissement_est_refuse() {
        $query = $this->valid_query();
        $query['_wpnonce'] = 'valid-' . Duplicate::nonce_action(999);

        $this->assertInstanceOf(\WP_Error::class, Duplicate::process_request($query));
    }

    public function test_un_utilisateur_sans_droit_d_edition_est_refuse() {
        $GLOBALS['gfo_test_caps'] = array();

        $result = Duplicate::process_request($this->valid_query());

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('geofolio_duplicate_forbidden', $result->get_error_code());
    }

    public function test_un_autre_type_de_contenu_est_refuse() {
        $page  = gfo_test_add_post(array('post_type' => 'page', 'post_title' => 'Accueil'));
        $query = array(
            'post'     => (string) $page->ID,
            '_wpnonce' => 'valid-' . Duplicate::nonce_action($page->ID),
        );

        $this->assertInstanceOf(\WP_Error::class, Duplicate::process_request($query));
    }

    /* ---------------------------------------------------------------- */
    /*  Lien « Dupliquer » dans la liste                                 */
    /* ---------------------------------------------------------------- */

    public function test_le_lien_dupliquer_est_ajoute_pour_un_editeur() {
        $actions = Duplicate::get_instance()->add_row_action(array('edit' => 'Modifier'), $this->source);

        $this->assertArrayHasKey('geofolio_duplicate', $actions);
        $this->assertStringContainsString('action=geofolio_duplicate', $actions['geofolio_duplicate']);
        $this->assertStringContainsString('_wpnonce=valid-', $actions['geofolio_duplicate']);
        $this->assertSame('Modifier', $actions['edit']);
    }

    public function test_l_url_de_duplication_est_brute_et_signee_pour_cet_etablissement() {
        $url = Duplicate::duplicate_url($this->source->ID);

        $this->assertStringNotContainsString('&amp;', $url, 'Utilisée telle quelle en JS : pas d\'entités HTML.');
        $this->assertStringContainsString('post=' . $this->source->ID, $url);
        $this->assertStringContainsString('_wpnonce=valid-geofolio_duplicate_' . $this->source->ID, $url);
    }

    public function test_le_lien_dupliquer_est_absent_sans_droit_d_edition() {
        $GLOBALS['gfo_test_caps'] = array();

        $actions = Duplicate::get_instance()->add_row_action(array('edit' => 'Modifier'), $this->source);

        $this->assertArrayNotHasKey('geofolio_duplicate', $actions);
    }

    public function test_le_lien_dupliquer_est_absent_sur_les_autres_types() {
        $page = gfo_test_add_post(array('post_type' => 'page'));

        $actions = Duplicate::get_instance()->add_row_action(array(), $page);

        $this->assertArrayNotHasKey('geofolio_duplicate', $actions);
    }
}
