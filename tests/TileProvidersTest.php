<?php
/**
 * Tests du registre des fonds de carte.
 */

use PHPUnit\Framework\TestCase;
use Geofolio\Map\TileProviders;

final class TileProvidersTest extends TestCase {

    protected function setUp(): void {
        gfo_test_reset();
    }

    /* ---------------------------------------------------------------- */
    /*  Registre                                                         */
    /* ---------------------------------------------------------------- */

    public function test_le_registre_expose_les_fonds_attendus() {
        $all = TileProviders::all();

        $this->assertArrayHasKey('ign-plan', $all);
        $this->assertArrayHasKey('positron', $all);
        $this->assertArrayHasKey('jawg-light', $all);
        $this->assertArrayHasKey(TileProviders::CUSTOM_ID, $all);
    }

    public function test_le_registre_ne_partage_aucun_etat_mutable() {
        $first = TileProviders::all();
        unset($first['ign-plan']);

        $this->assertArrayHasKey('ign-plan', TileProviders::all());
    }

    public function test_chaque_fond_declare_les_champs_requis() {
        foreach (TileProviders::all() as $id => $provider) {
            foreach (array('id', 'label', 'type', 'url', 'attribution', 'subdomains', 'requires_key') as $field) {
                $this->assertArrayHasKey($field, $provider, "Champ $field manquant pour $id");
            }
            $this->assertSame($id, $provider['id']);
            $this->assertContains($provider['type'], array('raster', 'vector'));
        }
    }

    public function test_tout_fond_a_cle_porte_le_jeton_key() {
        foreach (TileProviders::all() as $id => $provider) {
            if (!empty($provider['requires_key'])) {
                $this->assertStringContainsString('{key}', $provider['url'], "Le fond $id exige une clé sans jeton {key}");
            }
        }
    }

    public function test_labels_renvoie_un_libelle_par_fond() {
        $labels = TileProviders::labels();

        $this->assertSame(array_keys(TileProviders::all()), array_keys($labels));
        $this->assertNotSame('', $labels['ign-plan']);
    }

    public function test_exists_et_requires_key() {
        $this->assertTrue(TileProviders::exists('positron'));
        $this->assertFalse(TileProviders::exists('inconnu'));
        $this->assertFalse(TileProviders::exists(''));

        $this->assertTrue(TileProviders::requires_key('jawg-light'));
        $this->assertFalse(TileProviders::requires_key('ign-plan'));
        $this->assertFalse(TileProviders::requires_key('inconnu'));
    }

    /* ---------------------------------------------------------------- */
    /*  Validation des gabarits d'URL                                    */
    /* ---------------------------------------------------------------- */

    public function test_un_gabarit_valide_est_accepte() {
        $this->assertTrue(
            TileProviders::is_valid_url_template('https://exemple.fr/{z}/{x}/{y}.png?key={key}')
        );
    }

    public function test_un_gabarit_non_https_est_refuse() {
        $this->assertFalse(
            TileProviders::is_valid_url_template('http://exemple.fr/{z}/{x}/{y}.png')
        );
    }

    public function test_un_gabarit_sans_coordonnees_est_refuse() {
        $this->assertFalse(TileProviders::is_valid_url_template('https://exemple.fr/{z}/{x}.png'));
        $this->assertFalse(TileProviders::is_valid_url_template('https://exemple.fr/tuiles.png'));
        $this->assertFalse(TileProviders::is_valid_url_template(''));
    }

    /* ---------------------------------------------------------------- */
    /*  Résolution                                                       */
    /* ---------------------------------------------------------------- */

    public function test_un_fond_sans_cle_est_resolu_tel_quel() {
        $resolved = TileProviders::resolve('osm-fr');

        $this->assertSame('osm-fr', $resolved['id']);
        $this->assertSame('', $resolved['fallbackReason']);
        $this->assertStringContainsString('openstreetmap.fr', $resolved['url']);
        $this->assertSame('abc', $resolved['subdomains']);
    }

    /**
     * CARTO estampille « API KEY REQUIRED » sur ses tuiles anonymes depuis
     * 2026, tout en repondant en HTTP 200 : le plugin doit donc les traiter
     * comme des fonds a cle et retomber sur le Plan IGN sans clé.
     */
    public function test_les_fonds_carto_exigent_une_cle() {
        foreach (array('positron', 'voyager', 'darkmatter') as $id) {
            $this->assertTrue(TileProviders::requires_key($id), "$id devrait exiger une clé");

            $resolved = TileProviders::resolve($id, '');
            $this->assertSame(TileProviders::FALLBACK_ID, $resolved['id']);
            $this->assertSame('missing_key', $resolved['fallbackReason']);
        }
    }

    /**
     * CARTO attend « ?key= », pas « ?api_key= » (cf. CartoDB/basemap-styles).
     * Un parametre mal nomme est ignore SANS erreur : on recoit la tuile
     * estampillee, donc la cle du client semblerait ne servir a rien.
     */
    public function test_les_fonds_carto_passent_la_cle_sous_le_bon_parametre() {
        foreach (array('positron', 'voyager', 'darkmatter') as $id) {
            $url = TileProviders::resolve($id, 'ma-cle')['url'];

            $this->assertStringContainsString('?key=ma-cle', $url, "Mauvais parametre de cle pour $id");
            $this->assertStringNotContainsString('api_key=', $url);
        }
    }

    /**
     * Clone libre de Positron : meme rendu, sans cle ni inscription.
     */
    public function test_positron_est_disponible_sans_cle_via_openfreemap() {
        $resolved = TileProviders::resolve('openfreemap-positron', '');

        $this->assertSame('openfreemap-positron', $resolved['id']);
        $this->assertSame('', $resolved['fallbackReason']);
        $this->assertSame('vector', $resolved['type']);
        $this->assertFalse(TileProviders::requires_key('openfreemap-positron'));
    }

    /**
     * Le fond par defaut peut exiger une cle (Positron reste le rendu
     * historique du site) : ce qui compte, c'est que SANS cle il produise
     * quand meme un fond affichable, jamais le filigrane du fournisseur.
     */
    public function test_le_fond_par_defaut_reste_affichable_sans_cle() {
        $resolved = TileProviders::resolve(TileProviders::DEFAULT_ID, '');

        $this->assertNotSame('', $resolved['url']);
        $this->assertFalse(TileProviders::requires_key($resolved['id']));
    }

    /**
     * Avec une cle, le fond par defaut sert bien le rendu historique.
     */
    public function test_le_fond_par_defaut_sert_positron_avec_une_cle() {
        $resolved = TileProviders::resolve(TileProviders::DEFAULT_ID, 'une-cle');

        $this->assertSame('positron', $resolved['id']);
        $this->assertSame('', $resolved['fallbackReason']);
    }

    public function test_un_fond_inconnu_retombe_sur_le_plan_ign() {
        $resolved = TileProviders::resolve('fournisseur-fantaisiste');

        $this->assertSame(TileProviders::FALLBACK_ID, $resolved['id']);
        $this->assertSame('unknown', $resolved['fallbackReason']);
        $this->assertSame('fournisseur-fantaisiste', $resolved['requestedId']);
    }

    public function test_un_fond_a_cle_sans_cle_retombe_sur_le_plan_ign() {
        $resolved = TileProviders::resolve('jawg-light', '');

        $this->assertSame(TileProviders::FALLBACK_ID, $resolved['id']);
        $this->assertSame('missing_key', $resolved['fallbackReason']);
        $this->assertStringNotContainsString('{key}', $resolved['url']);
    }

    public function test_le_repli_ne_contient_jamais_de_jeton_de_cle() {
        $resolved = TileProviders::resolve('maptiler-streets', '   ');

        $this->assertSame('missing_key', $resolved['fallbackReason']);
        $this->assertStringContainsString('tiles.openfreemap.org', $resolved['url']);
    }

    public function test_la_cle_est_injectee_dans_le_gabarit() {
        $resolved = TileProviders::resolve('jawg-light', 'ma-cle-123');

        $this->assertSame('jawg-light', $resolved['id']);
        $this->assertSame('', $resolved['fallbackReason']);
        $this->assertStringContainsString('access-token=ma-cle-123', $resolved['url']);
        $this->assertStringNotContainsString('{key}', $resolved['url']);
    }

    public function test_la_cle_est_encodee_pour_l_url() {
        $resolved = TileProviders::resolve('maptiler-streets', 'cle avec espace&amp');

        $this->assertStringContainsString('cle%20avec%20espace%26amp', $resolved['url']);
    }

    public function test_les_espaces_autour_de_la_cle_sont_ignores() {
        $resolved = TileProviders::resolve('jawg-light', "  ma-cle-123\n");

        $this->assertStringContainsString('access-token=ma-cle-123', $resolved['url']);
    }

    /* ---------------------------------------------------------------- */
    /*  Fond personnalisé                                                */
    /* ---------------------------------------------------------------- */

    public function test_le_fond_personnalise_utilise_l_url_fournie() {
        $resolved = TileProviders::resolve(
            TileProviders::CUSTOM_ID,
            '',
            array(
                'url'         => 'https://exemple.fr/{z}/{x}/{y}.png',
                'attribution' => '&copy; Exemple',
            )
        );

        $this->assertSame(TileProviders::CUSTOM_ID, $resolved['id']);
        $this->assertSame('', $resolved['fallbackReason']);
        $this->assertSame('https://exemple.fr/{z}/{x}/{y}.png', $resolved['url']);
        $this->assertSame('&copy; Exemple', $resolved['attribution']);
    }

    public function test_le_fond_personnalise_sans_url_valide_retombe() {
        $resolved = TileProviders::resolve(
            TileProviders::CUSTOM_ID,
            '',
            array('url' => 'ftp://exemple.fr/{z}/{x}/{y}.png')
        );

        $this->assertSame(TileProviders::FALLBACK_ID, $resolved['id']);
        $this->assertSame('invalid_custom_url', $resolved['fallbackReason']);
    }

    public function test_le_fond_personnalise_attendant_une_cle_retombe_sans_elle() {
        $resolved = TileProviders::resolve(
            TileProviders::CUSTOM_ID,
            '',
            array('url' => 'https://exemple.fr/{z}/{x}/{y}.png?token={key}')
        );

        $this->assertSame('missing_key', $resolved['fallbackReason']);
    }

    public function test_le_fond_personnalise_recoit_la_cle() {
        $resolved = TileProviders::resolve(
            TileProviders::CUSTOM_ID,
            'abc',
            array('url' => 'https://exemple.fr/{z}/{x}/{y}.png?token={key}')
        );

        $this->assertSame('', $resolved['fallbackReason']);
        $this->assertStringContainsString('token=abc', $resolved['url']);
    }

    /* ---------------------------------------------------------------- */
    /*  Liens d'attribution                                              */
    /* ---------------------------------------------------------------- */

    /**
     * Un lien target="_blank" sans rel="noopener" donne à la page ouverte
     * un accès à window.opener.
     */
    public function test_toute_attribution_en_nouvel_onglet_porte_noopener() {
        $checked = 0;
        foreach (array_keys(TileProviders::all()) as $id) {
            $attribution = TileProviders::resolve($id, 'ma-cle')['attribution'];
            if (strpos($attribution, 'target="_blank"') === false) {
                continue;
            }
            $checked++;
            $this->assertSame(
                substr_count($attribution, 'target="_blank"'),
                substr_count($attribution, 'noopener'),
                $id
            );
        }
        $this->assertGreaterThan(0, $checked, 'Aucune attribution en nouvel onglet vérifiée.');
    }

    public function test_l_attribution_du_fond_personnalise_porte_noopener() {
        $resolved = TileProviders::resolve(TileProviders::CUSTOM_ID, '', array(
            'url'         => 'https://exemple.fr/{z}/{x}/{y}.png',
            'attribution' => '<a href="https://exemple.fr" target="_blank">Exemple</a>',
        ));

        $this->assertStringContainsString('noopener', $resolved['attribution']);
    }

    public function test_noopener_s_ajoute_a_un_rel_existant() {
        $this->assertSame(
            '<a href="https://a.fr" rel="noopener nofollow" target="_blank">A</a>',
            TileProviders::add_noopener('<a href="https://a.fr" rel="nofollow" target="_blank">A</a>')
        );
    }

    public function test_un_lien_sans_target_est_inchange() {
        $html = '&copy; <a href="https://a.fr">A</a>';
        $this->assertSame($html, TileProviders::add_noopener($html));
    }

    public function test_noopener_n_est_pas_ajoute_deux_fois() {
        $html = '<a href="https://a.fr" target="_blank" rel="noopener">A</a>';
        $this->assertSame($html, TileProviders::add_noopener($html));
    }

    public function test_le_repli_est_le_positron_sans_cle_a_couverture_mondiale() {
        $all = TileProviders::all();

        $this->assertSame('openfreemap-positron', TileProviders::FALLBACK_ID);
        $this->assertFalse(TileProviders::requires_key(TileProviders::FALLBACK_ID));
        $this->assertStringStartsWith('https://tiles.openfreemap.org/', $all[TileProviders::FALLBACK_ID]['url']);
    }
}
