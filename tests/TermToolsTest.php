<?php
/**
 * Tests des outils de termes génériques utilisés par les migrations.
 */

use PHPUnit\Framework\TestCase;
use MappedPlaces\Domain\Schema;
use MappedPlaces\Migration\TermTools;

final class TermToolsTest extends TestCase {

    protected function setUp(): void {
        mapl_test_reset_posts();
        mapl_test_reset_terms();
    }

    public function test_ensure_term_cree_puis_retrouve_le_terme() {
        $created = TermTools::ensure_term(Schema::TAX_TYPE, 'ITEP', 'itep');
        $this->assertTrue($created['created']);

        $again = TermTools::ensure_term(Schema::TAX_TYPE, 'ITEP', 'itep');
        $this->assertFalse($again['created']);
        $this->assertSame($created['term_id'], $again['term_id']);
    }

    public function test_un_terme_se_retrouve_par_slug_ou_par_nom() {
        $term = mapl_test_add_term(Schema::TAX_TYPE, 'Accueil de jour', 'accueil-de-jour');

        $this->assertSame($term, TermTools::find_term_by_slug_or_name(Schema::TAX_TYPE, 'accueil-de-jour'));
        $this->assertSame($term, TermTools::find_term_by_slug_or_name(Schema::TAX_TYPE, 'Accueil de jour'));
        $this->assertNull(TermTools::find_term_by_slug_or_name(Schema::TAX_TYPE, 'absent'));
    }

    public function test_la_fusion_reaffecte_les_articles_et_supprime_l_alias() {
        $canonical = mapl_test_add_term(Schema::TAX_TYPE, 'ITEP', 'itep');
        $alias     = mapl_test_add_term(Schema::TAX_TYPE, 'DITEP', 'ditep');
        $other     = mapl_test_add_term(Schema::TAX_TYPE, 'MECS', 'mecs');
        $post      = mapl_test_add_post(array('post_type' => Schema::POST_TYPE), array(), array(
            Schema::TAX_TYPE => array($alias->term_id, $other->term_id),
        ));

        $report = TermTools::merge_terms_by_aliases(Schema::TAX_TYPE, $canonical->term_id, array('ditep', 'absent'));

        $this->assertSame(1, $report['reassigned_posts']);
        $this->assertSame(1, $report['deleted_terms']);
        $this->assertFalse(get_term_by('slug', 'ditep', Schema::TAX_TYPE));
        $this->assertSame(
            array($other->term_id, $canonical->term_id),
            wp_get_post_terms($post->ID, Schema::TAX_TYPE, array('fields' => 'ids'))
        );
    }

    public function test_le_terme_canonique_n_est_jamais_fusionne_avec_lui_meme() {
        $canonical = mapl_test_add_term(Schema::TAX_TYPE, 'ITEP', 'itep');

        $report = TermTools::merge_terms_by_aliases(Schema::TAX_TYPE, $canonical->term_id, array('itep', 'ITEP'));

        $this->assertSame(array(), $report['merged_terms']);
        $this->assertNotFalse(get_term_by('slug', 'itep', Schema::TAX_TYPE));
    }

    public function test_une_couleur_existante_n_est_jamais_ecrasee() {
        $term = mapl_test_add_term(Schema::TAX_ENTITY, 'Pôle', 'pole', array(Schema::ENTITY_COLOR_META => '#111111'));
        $new  = mapl_test_add_term(Schema::TAX_ENTITY, 'Neuf', 'neuf');

        $this->assertFalse(TermTools::seed_term_color($term->term_id, '#222222'));
        $this->assertTrue(TermTools::seed_term_color($new->term_id, '#222222'));
        $this->assertFalse(TermTools::seed_term_color($new->term_id, 'pas une couleur'));
        $this->assertSame('#111111', get_term_meta($term->term_id, Schema::ENTITY_COLOR_META, true));
        $this->assertSame('#222222', get_term_meta($new->term_id, Schema::ENTITY_COLOR_META, true));
    }
}
