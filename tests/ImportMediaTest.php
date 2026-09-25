<?php
/**
 * Tests de l'import enrichi : valeurs multiples, couleur d'entité, icône de
 * type et photos du jeu livré avec l'extension.
 */

use PHPUnit\Framework\TestCase;
use MappedPlaces\Import\CsvMapping;
use MappedPlaces\Import\Importer;
use MappedPlaces\Import\MediaImporter;

final class ImportMediaTest extends TestCase {

    /** @var string */
    private $dir;

    protected function setUp(): void {
        $this->dir = sys_get_temp_dir() . '/mapl-media-' . uniqid();
        mkdir($this->dir);
        file_put_contents($this->dir . '/hall.jpg', 'jpeg');
        file_put_contents($this->dir . '/room.png', 'png');
        file_put_contents($this->dir . '/notes.txt', 'text');
    }

    protected function tearDown(): void {
        array_map('unlink', glob($this->dir . '/*'));
        rmdir($this->dir);
    }

    public function test_les_nouvelles_colonnes_sont_reconnues_en_anglais_et_en_francais() {
        $fields = CsvMapping::map_row(array(
            'Entity colour' => '#2E7D6B', 'Icône du type' => 'book', 'Image' => 'hall.jpg',
            'Galerie' => 'room.png', 'Accessibilité' => 'Wheelchair access', 'Région' => 'Occitanie',
        ));

        $this->assertSame('#2E7D6B', $fields['entity_color']);
        $this->assertSame('book', $fields['type_icon']);
        $this->assertSame('hall.jpg', $fields['image']);
        $this->assertSame('room.png', $fields['gallery']);
        $this->assertSame('Wheelchair access', $fields['accessibility']);
        $this->assertSame('Occitanie', $fields['region']);
    }

    public function test_une_liste_se_separe_par_point_virgule_ou_barre() {
        $this->assertSame(array('Wi-Fi', 'Meeting rooms', 'Café'), Importer::split_list(' Wi-Fi ; Meeting rooms| Café ;; '));
        $this->assertSame(array(), Importer::split_list(''));
    }

    public function test_les_fichiers_d_images_sont_cherches_dans_le_dossier_du_jeu() {
        $this->assertSame(
            array($this->dir . '/hall.jpg', $this->dir . '/room.png'),
            MediaImporter::resolve_files('hall.jpg; room.png', $this->dir)
        );
    }

    public function test_un_chemin_hors_du_dossier_ou_un_fichier_absent_est_ignore() {
        $this->assertSame(array($this->dir . '/hall.jpg'), MediaImporter::resolve_files('../hall.jpg; /etc/passwd; missing.jpg; hall.jpg', $this->dir));
    }

    public function test_seules_les_images_sont_retenues() {
        $this->assertSame(array(), MediaImporter::resolve_files('notes.txt', $this->dir));
    }

    public function test_sans_dossier_de_medias_aucune_image_n_est_importee() {
        $this->assertSame(array(), MediaImporter::resolve_files('hall.jpg', null));
    }

    public function test_une_couleur_d_entite_invalide_est_ignoree() {
        $this->assertSame('#2E7D6B', Importer::entity_color(array('entity_color' => '#2E7D6B')));
        $this->assertSame('', Importer::entity_color(array('entity_color' => 'red;x')));
        $this->assertSame('', Importer::entity_color(array()));
    }
}
