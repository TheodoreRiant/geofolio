<?php
/**
 * Tests de l'import CSV : lecture tolérante du fichier, contenu échappé,
 * contrôle du fichier téléversé.
 */

use PHPUnit\Framework\TestCase;
use MappedPlaces\Domain\FieldRegistry;
use MappedPlaces\Import\CsvMapping;
use MappedPlaces\Import\Importer;

final class ImportTest extends TestCase {

    /** @var string[] Fichiers temporaires à supprimer. */
    private $files = array();

    protected function tearDown(): void {
        foreach ($this->files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        $this->files = array();
    }

    private function csv(string $content): string {
        $path = tempnam(sys_get_temp_dir(), 'mapl-import-');
        file_put_contents($path, $content);
        $this->files[] = $path;
        return $path;
    }

    /* ---------------------------------------------------------------- */
    /*  Lecture du fichier                                               */
    /* ---------------------------------------------------------------- */

    public function test_le_jeu_d_exemple_compte_seize_lieux_bien_formes() {
        $result = Importer::parse_csv(dirname(__DIR__) . '/data/sample/places.csv');

        $this->assertSame(0, $result['skipped']);
        $this->assertCount(16, $result['rows']);
    }

    public function test_le_jeu_d_exemple_montre_toutes_les_possibilites() {
        $rows   = Importer::parse_csv(dirname(__DIR__) . '/data/sample/places.csv')['rows'];
        $fields = array_map(array(CsvMapping::class, 'map_row'), $rows);

        $this->assertCount(5, array_unique(array_column($fields, 'type')));
        $this->assertCount(3, array_unique(array_column($fields, 'entity')));
        $required = array(
            'description', 'audience', 'capacity', 'type_icon', 'entity_color', 'address', 'postal_code', 'city',
            'region', 'phone', 'email', 'website', 'manager', 'opening_hours', 'service', 'accessibility', 'image', 'gallery',
        );
        foreach ($fields as $place) {
            foreach ($required as $field) {
                $this->assertArrayHasKey($field, $place, $place['name'] . ' : ' . $field);
            }
            $this->assertNotSame('', FieldRegistry::sanitize_coordinate($place['latitude'] ?? ''), $place['name']);
            $this->assertTrue(\MappedPlaces\Domain\Icons::is_valid($place['type_icon']), $place['name']);
            $this->assertNotSame('', Importer::entity_color($place), $place['name']);
            // Numéros réservés par l'ARCEP aux œuvres de fiction.
            $this->assertMatchesRegularExpression('/^0(1 99 00|2 61 91|3 53 01|4 65 71|5 36 49) \d\d \d\d$/', $place['phone'], $place['name']);
            $this->assertStringEndsWith('.example.org', $place['email'], $place['name']);
        }
    }

    public function test_chaque_photo_citee_par_le_jeu_d_exemple_est_livree() {
        $dir    = dirname(__DIR__) . '/data/sample/photos';
        $fields = array_map(array(CsvMapping::class, 'map_row'), Importer::parse_csv(dirname(__DIR__) . '/data/sample/places.csv')['rows']);
        foreach ($fields as $place) {
            $cited = count(Importer::split_list($place['image'] . ';' . $place['gallery']));
            $this->assertCount($cited, \MappedPlaces\Import\MediaImporter::resolve_files($place['image'] . ';' . $place['gallery'], $dir), $place['name']);
        }
    }


    public function test_une_ligne_avec_une_colonne_en_trop_est_ignoree() {
        $result = Importer::parse_csv($this->csv(
            "Nom,Mission\nA,Accueil\nB,Accueil,en trop\nC,Soin\n"
        ));

        $this->assertSame(1, $result['skipped']);
        $this->assertSame(array('A', 'C'), array_column($result['rows'], 'nom'));
    }

    public function test_une_ligne_avec_une_colonne_en_moins_est_ignoree() {
        $result = Importer::parse_csv($this->csv("Nom,Mission,Dep\nA,Accueil,69\nB,Accueil\n"));

        $this->assertSame(1, $result['skipped']);
        $this->assertCount(1, $result['rows']);
        $this->assertSame('69', $result['rows'][0]['dep']);
    }

    public function test_les_en_tetes_sont_normalises() {
        $result = Importer::parse_csv($this->csv(
            " Nom ,Domaine d\u{2019}intervention,TYPE DE STRUCTURE\nA,Protection,MECS\n"
        ));

        $this->assertSame(
            array('nom', "domaine d'intervention", 'type de structure'),
            array_keys($result['rows'][0])
        );
    }

    public function test_un_fichier_vide_ne_retourne_aucune_ligne() {
        $result = Importer::parse_csv($this->csv(''));

        $this->assertSame(array('rows' => array(), 'skipped' => 0), $result);
    }

    public function test_un_en_tete_seul_ne_retourne_aucune_ligne() {
        $result = Importer::parse_csv($this->csv("Nom,Mission\n"));

        $this->assertSame(array('rows' => array(), 'skipped' => 0), $result);
    }

    public function test_un_fichier_introuvable_ne_retourne_aucune_ligne() {
        $result = Importer::parse_csv(sys_get_temp_dir() . '/mapl-absent-' . uniqid() . '.csv');

        $this->assertSame(array('rows' => array(), 'skipped' => 0), $result);
    }

    public function test_une_barre_oblique_inverse_est_lue_telle_quelle() {
        $result = Importer::parse_csv($this->csv("Nom,Mission\nA,\"Accueil \\\"jour\\\"\"\n"));

        $this->assertSame(0, $result['skipped']);
        $this->assertSame('A', $result['rows'][0]['nom']);
    }

    /* ---------------------------------------------------------------- */
    /*  Contenu de l'établissement                                       */
    /* ---------------------------------------------------------------- */

    public function test_une_balise_dans_la_mission_est_echappee() {
        $content = Importer::build_content('<script>alert(1)</script>', '');

        $this->assertStringContainsString('&lt;script&gt;', $content);
        $this->assertStringNotContainsString('<script>', $content);
    }

    public function test_une_balise_dans_la_capacite_est_echappee() {
        $content = Importer::build_content('', '<img src=x onerror=alert(1)>');

        $this->assertStringNotContainsString('<img', $content);
    }

    /** the_excerpt() n'échappe pas : aucune balise ne doit atteindre l'extrait. */
    public function test_l_extrait_ne_contient_aucune_balise() {
        $excerpt = Importer::build_excerpt('<script>alert(1)</script>Accueil <b>jour</b>', '');

        $this->assertStringNotContainsString('<', $excerpt);
        $this->assertStringContainsString('Accueil', $excerpt);
    }

    public function test_l_extrait_reprend_la_capacite_sans_mission() {
        $this->assertSame('12 places', Importer::build_excerpt('', '<i>12 places</i>'));
    }

    public function test_mission_et_capacite_vides_donnent_un_contenu_vide() {
        $this->assertSame('', Importer::build_content('', ''));
    }

    /* ---------------------------------------------------------------- */
    /*  Fichier téléversé                                                */
    /* ---------------------------------------------------------------- */

    private function upload(string $name, int $size): array {
        return array('name' => $name, 'size' => $size, 'error' => UPLOAD_ERR_OK, 'tmp_name' => '/tmp/x');
    }

    public function test_un_csv_de_taille_raisonnable_est_accepte() {
        $this->assertSame('', Importer::upload_error($this->upload('etablissements.CSV', 1024)));
    }

    public function test_un_fichier_trop_lourd_est_refuse() {
        $file = $this->upload('etablissements.csv', Importer::MAX_FILE_SIZE + 1);

        $this->assertNotSame('', Importer::upload_error($file));
    }

    public function test_une_autre_extension_est_refusee() {
        $this->assertNotSame('', Importer::upload_error($this->upload('shell.php', 10)));
        $this->assertNotSame('', Importer::upload_error($this->upload('liste.csv.php', 10)));
    }

    public function test_un_echec_de_televersement_est_refuse() {
        $file = array('name' => 'a.csv', 'size' => 0, 'error' => UPLOAD_ERR_NO_FILE, 'tmp_name' => '');

        $this->assertNotSame('', Importer::upload_error($file));
    }

    /* ---------------------------------------------------------------- */
    /*  Règles d'import (filtres)                                         */
    /* ---------------------------------------------------------------- */

    public function test_sans_filtre_l_entite_vient_de_la_colonne_ou_de_rien() {
        $this->assertNull(Importer::resolve_entity(array('name' => 'A')));
        $this->assertSame(
            array('slug' => '', 'name' => 'North Network'),
            Importer::resolve_entity(array('name' => 'A', 'entity' => 'North Network'))
        );
    }

    public function test_un_filtre_fournit_l_entite() {
        add_filter('mapped_places_import_entity', static function ($entity, $fields) {
            return $fields['type'] === 'Restaurant' ? array('slug' => 'restos', 'name' => 'Restos') : $entity;
        }, 10, 2);

        $this->assertSame(array('slug' => 'restos', 'name' => 'Restos'), Importer::resolve_entity(array('type' => 'Restaurant')));
        mapl_test_reset_filters();
    }

    public function test_sans_filtre_aucune_region_n_est_deduite() {
        $this->assertSame('', Importer::resolve_region(array('department' => '69')));
    }

    public function test_un_filtre_deduit_la_region_du_departement() {
        add_filter('mapped_places_import_region', static function ($region, $department) {
            return $department === '69' ? 'Auvergne-Rhône-Alpes' : $region;
        }, 10, 2);

        $this->assertSame('Auvergne-Rhône-Alpes', Importer::resolve_region(array('department' => '69')));
        mapl_test_reset_filters();
    }

    public function test_la_table_des_regions_francaises_couvre_le_rhone() {
        $regions = include dirname(__DIR__) . '/data/regions-fr.php';
        $this->assertSame('Auvergne-Rhône-Alpes', $regions['69']);
        $this->assertSame('Île-de-France', $regions['75']);
    }

    public function test_les_metas_viennent_des_champs_et_sont_nettoyees() {
        $meta = Importer::meta_from_fields(array(
            'name' => 'A', 'address' => '1 rue <b>X</b>', 'latitude' => '45,5', 'longitude' => 'abc',
            'website' => 'javascript:alert(1)', 'city' => 'Lyon',
        ));

        $this->assertSame('1 rue X', $meta['address']);
        $this->assertSame('45.5', $meta['latitude']);
        $this->assertArrayNotHasKey('longitude', $meta);
        $this->assertArrayNotHasKey('website', $meta);
        $this->assertSame('Lyon', $meta['city']);
        $this->assertArrayNotHasKey('name', $meta);
    }

    public function test_l_url_du_geocodeur_est_filtrable() {
        $this->assertStringStartsWith('https://api-adresse.data.gouv.fr/search/?q=', Importer::geocoder_url('1 rue X'));

        add_filter('mapped_places_geocoder_url', static function () {
            return 'https://nominatim.example/search?format=geojson';
        });
        $this->assertSame('https://nominatim.example/search?format=geojson&q=1+rue+X&limit=1', Importer::geocoder_url('1 rue X'));
        mapl_test_reset_filters();
    }

    public function test_le_jeu_par_defaut_est_l_exemple_sauf_filtre() {
        $this->assertStringEndsWith('data/sample/places.csv', Importer::default_dataset());

        add_filter('mapped_places_default_dataset', static function () {
            return '/tmp/autre.csv';
        });
        $this->assertSame('/tmp/autre.csv', Importer::default_dataset());
        mapl_test_reset_filters();
    }
}
