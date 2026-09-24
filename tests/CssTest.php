<?php
/**
 * Tests de la feuille de style du cœur : palette neutre, aucune trace de la
 * charte d'un client (qui vit dans un compagnon), aucun bloc mort.
 */

use PHPUnit\Framework\TestCase;

final class CssTest extends TestCase {

    const CSS = __DIR__ . '/../assets/css/geofolio.css';

    /** Classes supprimées : jamais produites par le gabarit ni par le JS. */
    const REMOVED_CLASSES = array(
        'gfo-category-card', 'gfo-filter-group', 'gfo-sidebar-filters', 'gfo-radius-select',
        'gfo-result-item', 'gfo-result-type', 'gfo-marker-pulse', 'gfo-popup-link', 'gfo-tag',
        'gfo-map-legend', 'gfo-sheet-peek', 'gfo-sr-only', 'gfo-dark',
    );

    private static function css(): string {
        return (string) file_get_contents(self::CSS);
    }

    public function test_aucune_trace_d_une_charte_client() {
        $css = self::css();

        $this->assertStringNotContainsString('Neulis', $css);
        $this->assertStringNotContainsStringIgnoringCase('#024266', $css);
        $this->assertStringNotContainsStringIgnoringCase('#FB6223', $css);
    }

    public function test_aucune_variable_de_type_ni_de_couleur_d_item() {
        $css = self::css();

        $this->assertStringNotContainsString('--type-', $css);
        $this->assertStringNotContainsString('--item-color', $css);
    }

    public function test_les_classes_mortes_ont_disparu() {
        $css = self::css();
        foreach (self::REMOVED_CLASSES as $class) {
            $this->assertDoesNotMatchRegularExpression('/\.' . preg_quote($class, '/') . '(?![\w-])/', $css, $class);
        }
    }

    public function test_les_variables_derivees_suivent_le_conteneur() {
        $css = self::css();
        preg_match('/:root\s*\{([^}]*)\}/', $css, $root);
        preg_match('/\n\.gfo-map-container\s*\{([^}]*)\}/', $css, $container);

        foreach (array('--gfo-cluster-bg', '--gfo-cluster-border', '--gfo-cluster-text', '--gfo-popup-accent') as $var) {
            $this->assertStringNotContainsString($var . ':', $root[1], $var);
            $this->assertStringContainsString($var . ':', $container[1], $var);
        }
    }

    public function test_la_palette_neutre_est_dans_root() {
        preg_match('/:root\s*\{([^}]*)\}/', self::css(), $root);

        $this->assertMatchesRegularExpression('/--gfo-primary:\s*#1F4E79;/i', $root[1]);
        $this->assertMatchesRegularExpression('/--gfo-accent:\s*#E07A1F;/i', $root[1]);
        $this->assertMatchesRegularExpression('/--gfo-font-title:\s*var\(--gfo-font\);/', $root[1]);
    }

    public function test_la_feuille_reste_sous_2100_lignes() {
        $this->assertLessThan(2100, substr_count(self::css(), "\n"));
    }

    public function test_les_accolades_sont_equilibrees() {
        $css = preg_replace('#/\*.*?\*/#s', '', self::css());
        $this->assertSame(substr_count($css, '{'), substr_count($css, '}'));
    }

    public function test_chaque_classe_produite_par_le_js_a_une_regle_css() {
        $js  = (string) file_get_contents(__DIR__ . '/../assets/js/geofolio.js');
        $css = self::css();
        preg_match_all('/gfo-[a-z0-9-]*[a-z0-9]/', $js, $matches);

        $missing = array_filter(array_unique($matches[0]), static function ($class) use ($css) {
            return !preg_match('/\\.' . preg_quote($class, '/') . '(?![\\w-])/', $css)
                && strpos($class, 'gfo-cluster-') !== 0  // gfo-cluster-{small,medium,large}
                && $class !== 'gfo-ac';                   // préfixe d'identifiant, pas une classe
        });
        $this->assertSame(array(), array_values($missing));
    }

    /** Hauteur fixée par le conteneur (widget Elementor) : le wrapper le remplit. */
    public function test_le_wrapper_remplit_un_conteneur_dimensionne() {
        $this->assertMatchesRegularExpression('/\.gfo-map-container--sized \.gfo-map-wrapper\s*\{[^}]*height:\s*100%/', self::css());
    }
}
