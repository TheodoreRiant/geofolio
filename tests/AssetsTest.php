<?php
/**
 * Tests des bibliothèques cartographiques embarquées : plus aucun appel au
 * CDN, et fichiers de assets/vendor/ conformes à l'inventaire VERSIONS.md.
 */

use PHPUnit\Framework\TestCase;

final class AssetsTest extends TestCase {

    const ROOT = __DIR__ . '/..';

    const VENDOR_DIR = self::ROOT . '/assets/vendor';

    /**
     * Lignes de l'inventaire : chemin relatif à assets/vendor/ => sha256.
     *
     * @return array<string, string>
     */
    private static function inventory(): array {
        $markdown = (string) file_get_contents(self::VENDOR_DIR . '/VERSIONS.md');
        preg_match_all('/^\|\s*`([^`]+)`\s*\|.*\|\s*([0-9a-f]{64})\s*\|\s*$/m', $markdown, $matches, PREG_SET_ORDER);

        $files = array();
        foreach ($matches as $match) {
            $files[$match[1]] = $match[2];
        }
        return $files;
    }

    /**
     * Fichiers source du plugin chargés en production.
     *
     * @return string[]
     */
    private static function source_files(): array {
        $files = array(self::ROOT . '/mapped-places.php');
        foreach (array('src', 'assets/js') as $dir) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::ROOT . '/' . $dir));
            foreach ($iterator as $file) {
                if ($file->isFile() && preg_match('/\.(php|js)$/', $file->getFilename())) {
                    $files[] = $file->getPathname();
                }
            }
        }
        return $files;
    }

    public function test_aucune_bibliotheque_n_est_chargee_depuis_unpkg() {
        $offenders = array_filter(self::source_files(), static function ($file) {
            return strpos((string) file_get_contents($file), 'unpkg.com') !== false;
        });

        $this->assertSame(array(), array_values($offenders));
    }

    public function test_l_inventaire_liste_les_quatre_bibliotheques() {
        $dirs = array_unique(array_map(static function ($path) {
            return explode('/', $path)[0];
        }, array_keys(self::inventory())));
        sort($dirs);

        $this->assertSame(array(
            'leaflet-1.9.4',
            'leaflet.markercluster-1.4.1',
            'maplibre-gl-3.6.2',
            'maplibre-gl-leaflet-0.0.22',
        ), $dirs);
    }

    public function test_chaque_fichier_inventorie_existe_avec_le_bon_sha256() {
        foreach (self::inventory() as $path => $sha256) {
            $file = self::VENDOR_DIR . '/' . $path;
            $this->assertFileExists($file);
            $this->assertSame($sha256, hash_file('sha256', $file), $path);
        }
    }

    public function test_chaque_fichier_embarque_est_inventorie() {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::VENDOR_DIR, FilesystemIterator::SKIP_DOTS));
        $on_disk  = array();
        foreach ($iterator as $file) {
            $relative = substr($file->getPathname(), strlen(self::VENDOR_DIR) + 1);
            if ($relative !== 'VERSIONS.md') {
                $on_disk[] = $relative;
            }
        }
        sort($on_disk);
        $listed = array_keys(self::inventory());
        sort($listed);

        $this->assertSame($listed, $on_disk);
    }
}
