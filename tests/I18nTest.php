<?php
/**
 * Tests de l'internationalisation : chaînes source en anglais, domaine de
 * traduction unique, traduction française fournie.
 */

use PHPUnit\Framework\TestCase;

final class I18nTest extends TestCase {

    const ROOT = __DIR__ . '/..';

    /** Appel de traduction : fonction, chaîne source, suite des arguments. */
    const CALL = "/\\b(__|_e|esc_html__|esc_html_e|esc_attr__|esc_attr_e|_x|_n|esc_html_x|esc_attr_x|_ex)\\(\\s*'((?:[^'\\\\\\\\]|\\\\\\\\.)*)'([^;]*)/";

    /**
     * @return array<int, array{0: string, 1: string, 2: string}> Fichier, chaîne, reste de l'appel.
     */
    private static function calls(): array {
        $calls = array();
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::ROOT . '/src'));
        $paths = array_merge(iterator_to_array($files), glob(self::ROOT . '/views/*.php'));
        foreach ($paths as $file) {
            $path = (string) $file;
            if (substr($path, -4) !== '.php') {
                continue;
            }
            preg_match_all(self::CALL, (string) file_get_contents($path), $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $calls[] = array(basename($path), $match[2], $match[3]);
            }
        }
        return $calls;
    }

    public function test_aucune_chaine_source_n_est_accentuee() {
        $french = array_filter(self::calls(), static function ($call) {
            return preg_match('/[À-ÿ]/u', $call[1]);
        });
        $this->assertSame(array(), array_values(array_map(static function ($call) {
            return $call[0] . ': ' . $call[1];
        }, $french)));
    }

    public function test_chaque_appel_utilise_le_domaine_mapped_places() {
        foreach (self::calls() as $call) {
            $this->assertStringContainsString("'mapped-places'", $call[2], $call[0] . ': ' . $call[1]);
        }
    }

    public function test_la_traduction_francaise_est_livree() {
        $this->assertFileExists(self::ROOT . '/languages/mapped-places.pot');
        $this->assertFileExists(self::ROOT . '/languages/mapped-places-fr_FR.po');
        $this->assertFileExists(self::ROOT . '/languages/mapped-places-fr_FR.mo');
    }

    public function test_chaque_chaine_source_a_sa_traduction_francaise() {
        $po = (string) file_get_contents(self::ROOT . '/languages/mapped-places-fr_FR.po');
        preg_match_all('/^msgid "(.*)"\nmsgstr "(.*)"$/m', $po, $entries, PREG_SET_ORDER);
        $translated = array();
        foreach ($entries as $entry) {
            if ($entry[1] !== '' && $entry[2] !== '') {
                $translated[stripcslashes($entry[1])] = true;
            }
        }

        $missing = array();
        foreach (self::calls() as $call) {
            $source = str_replace("\\'", "'", $call[1]);
            if (!isset($translated[$source])) {
                $missing[] = $source;
            }
        }
        $this->assertSame(array(), array_values(array_unique($missing)));
    }
}
