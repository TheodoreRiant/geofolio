<?php
/**
 * Garde-fous de conformité au répertoire wordpress.org (Plugin Check et
 * règles du répertoire), vérifiables sans WordPress : sorties échappées,
 * aucune opération fichier directe, services externes documentés,
 * désinstallation protégée.
 */

use PHPUnit\Framework\TestCase;

class PluginCheckTest extends TestCase {

    const ROOT = __DIR__ . '/..';

    /**
     * @return string[] Fichiers PHP du cœur (src/ et views/), sans le compagnon.
     */
    private static function core_php_files(): array {
        $files = array();
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::ROOT . '/src'));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        return array_merge($files, glob(self::ROOT . '/views/*.php'));
    }

    /**
     * @return array<string, string> Chemin relatif => lignes en violation.
     */
    private static function violations(string $pattern): array {
        $found = array();
        foreach (self::core_php_files() as $path) {
            $lines = file($path);
            foreach ($lines as $number => $line) {
                if (preg_match($pattern, $line)) {
                    $found[] = substr($path, strlen(self::ROOT) + 1) . ':' . ($number + 1) . ' ' . trim($line);
                }
            }
        }
        return $found;
    }

    public function test_aucune_fonction_d_affichage_non_echappee(): void {
        $pattern = '/(?<![A-Za-z0-9_])_e\(|echo\s+__\(|(?<![a-z])printf\(\s*(\/\*.*?\*\/\s*)?__\(|wp_die\(\s*__\(/';
        $this->assertSame(array(), self::violations($pattern), "Utiliser esc_html_e(), esc_attr_e() ou esc_html__() :\n" . implode("\n", self::violations($pattern)));
    }

    public function test_aucune_operation_fichier_directe(): void {
        $pattern = '/(?<![A-Za-z0-9_>])(fopen|fclose|unlink|file_put_contents)\(/';
        $this->assertSame(array(), self::violations($pattern), "Passer par SplFileObject, WP_Filesystem ou wp_delete_file() :\n" . implode("\n", self::violations($pattern)));
    }

    public function test_les_superglobales_sont_lues_apres_verification_du_nonce(): void {
        // La couleur d'entité et l'import lisent $_POST : chaque fonction qui
        // le fait doit vérifier un nonce dans son propre corps.
        $taxonomies = (string) file_get_contents(self::ROOT . '/src/Domain/Taxonomies.php');
        $this->assertMatchesRegularExpression('/function save_entity_color\(.*?wp_verify_nonce/s', $taxonomies);
        $this->assertStringContainsString("wp_nonce_field('geofolio_entity_color'", $taxonomies);

        $importer = (string) file_get_contents(self::ROOT . '/src/Import/Importer.php');
        $this->assertMatchesRegularExpression('/function handle_import\(\).*?wp_verify_nonce/s', $importer);
        $this->assertStringNotContainsString("\$_GET['", $importer, 'Les résultats d\'import passent par un transient, plus par l\'URL.');
    }

    public function test_le_readme_documente_les_services_externes(): void {
        $readme = (string) file_get_contents(self::ROOT . '/readme.txt');
        $this->assertStringContainsString('= External services =', $readme);
        foreach (array('openfreemap.org', 'openstreetmap.org', 'geoservices.ign.fr', 'carto.com', 'jawg.io', 'maptiler.com', 'stadiamaps.com', 'thunderforest.com', 'adresse.data.gouv.fr') as $host) {
            $this->assertStringContainsString($host, $readme, "Service externe non documenté : $host");
        }
        $this->assertStringContainsString('= Privacy =', $readme);
        $this->assertStringContainsString('github.com/TheodoreRiant/geofolio', $readme, 'Lien vers le code source (règle 4).');
    }

    public function test_la_desinstallation_est_protegee(): void {
        $path = self::ROOT . '/uninstall.php';
        $this->assertFileExists($path);
        $uninstall = (string) file_get_contents($path);
        $this->assertStringContainsString("defined('WP_UNINSTALL_PLUGIN')", $uninstall);
        foreach (array('geofolio_settings', 'geofolio_migrations_done', 'geofolio_migration_log_last', 'geofolio_snapshot_') as $key) {
            $this->assertStringContainsString($key, $uninstall, "Option non nettoyée : $key");
        }
        $this->assertStringContainsString('GEOFOLIO_UNINSTALL_DATA', $uninstall, 'La suppression des lieux doit rester un choix explicite.');
    }

    public function test_les_notices_d_administration_sont_fermables(): void {
        $settings = (string) file_get_contents(self::ROOT . '/src/Admin/SettingsPage.php');
        $this->assertMatchesRegularExpression('/notice notice-warning[^"]*is-dismissible/', $settings);
    }
}
