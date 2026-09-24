<?php
/**
 * Chaque filtre ou action `geofolio_*` du code est documenté dans
 * docs/hooks.md, et la documentation ne cite aucun hook disparu.
 */

use PHPUnit\Framework\TestCase;

class HooksDocTest extends TestCase {

    const ROOT = __DIR__ . '/..';

    /**
     * @return string[] Noms des hooks déclarés dans src/, triés.
     */
    private static function hooks_in_code(): array {
        $names = array();
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::ROOT . '/src'));
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }
            preg_match_all("/(?:apply_filters|do_action)\\(\\s*'(geofolio_[a-z0-9_]+)'/", (string) file_get_contents($file->getPathname()), $matches);
            $names = array_merge($names, $matches[1]);
        }
        $names = array_unique($names);
        sort($names);
        return $names;
    }

    /**
     * @return string[] Noms des hooks cités en première colonne des tableaux de docs/hooks.md.
     */
    private static function hooks_in_doc(): array {
        preg_match_all('/^\| `(geofolio_[a-z0-9_]+)`/m', (string) file_get_contents(self::ROOT . '/docs/hooks.md'), $matches);
        $names = array_unique($matches[1]);
        sort($names);
        return $names;
    }

    public function test_chaque_hook_du_code_est_documente(): void {
        $missing = array_values(array_diff(self::hooks_in_code(), self::hooks_in_doc()));
        $this->assertSame(array(), $missing, "Hooks sans entrée dans docs/hooks.md :\n" . implode("\n", $missing));
    }

    public function test_la_documentation_ne_cite_aucun_hook_disparu(): void {
        $stale = array_values(array_diff(self::hooks_in_doc(), self::hooks_in_code()));
        $this->assertSame(array(), $stale, "Hooks documentés mais absents du code :\n" . implode("\n", $stale));
    }
}
