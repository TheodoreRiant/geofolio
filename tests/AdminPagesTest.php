<?php
/**
 * Pages d'administration du cœur : rattachées au type de contenu Geofolio.
 *
 * Jusqu'en 1.1.0, Réglages et Import visaient edit.php?post_type=etablissement
 * (type du plugin d'origine) : sur un site Geofolio seul, WordPress répondait
 * 403 et les pages n'apparaissaient dans aucun menu.
 */

use PHPUnit\Framework\TestCase;
use Geofolio\Admin\SettingsPage;
use Geofolio\Domain\Schema;
use Geofolio\Import\Importer;

final class AdminPagesTest extends TestCase {

    public function test_le_menu_parent_est_celui_du_type_de_contenu() {
        $this->assertSame('edit.php?post_type=' . Schema::POST_TYPE, Schema::ADMIN_PARENT);
    }

    public function test_la_page_d_import_est_sous_le_type_de_contenu() {
        $this->assertStringStartsWith(Schema::ADMIN_PARENT . '&page=', Importer::IMPORT_PAGE);
    }

    public function test_aucun_type_de_contenu_code_en_dur_dans_les_urls_d_administration() {
        $offenders = array();
        $iterator  = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../src'));
        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match('/post_type=[a-z]/', (string) file_get_contents($file->getPathname()))) {
                $offenders[] = $file->getFilename();
            }
        }
        $this->assertSame(array(), $offenders);
    }
}
