<?php
/**
 * Tests du widget Elementor : chaque sélecteur CSS d'un contrôle doit viser
 * une classe réellement produite par le gabarit ou par le JS (aucun contrôle
 * mort), et les valeurs par défaut viennent de Defaults.
 */

use PHPUnit\Framework\TestCase;
use Geofolio\Elementor\MapWidget;
use Geofolio\Map\Defaults;

final class ElementorWidgetTest extends TestCase {

    const ROOT = __DIR__ . '/..';

    /** Contrôles retirés car morts ou cassés (revue de code du 24/09/2026). */
    const REMOVED_CONTROLS = array(
        'popup_link_bg', 'popup_link_color', 'scrollbar_width', 'scrollbar_thumb_color',
        'scrollbar_track_color', 'marker_size', 'map_width',
    );

    /** Contrôles de style dont Elementor ne doit rien écrire par défaut. */
    const STYLE_CONTROLS_WITHOUT_DEFAULT = array(
        'sidebar_width', 'search_radius', 'badge_radius', 'popup_radius', 'primary_color', 'accent_color',
    );

    protected function tearDown(): void {
        gfo_test_reset_filters();
    }

    private static function controls(): array {
        return (new MapWidget())->captured_controls;
    }

    /**
     * Classes gfo-* citées dans les sélecteurs des contrôles.
     *
     * @return string[]
     */
    private static function selector_classes(): array {
        $classes = array();
        foreach (self::controls() as $args) {
            $selectors = array_keys($args['selectors'] ?? array());
            if (isset($args['selector'])) {
                $selectors[] = $args['selector'];
            }
            foreach ($selectors as $selector) {
                preg_match_all('/\.(gfo-[a-z0-9_-]+)/', $selector, $matches);
                $classes = array_merge($classes, $matches[1]);
            }
        }
        return array_values(array_unique($classes));
    }

    public function test_chaque_classe_ciblee_existe_dans_le_gabarit_ou_le_js() {
        $sources = file_get_contents(self::ROOT . '/views/map.php')
            . file_get_contents(self::ROOT . '/assets/js/geofolio.js');

        $classes = self::selector_classes();
        $this->assertNotEmpty($classes);

        $missing = array_filter($classes, static function ($class) use ($sources) {
            return !preg_match('/(?<![a-z0-9_-])' . preg_quote($class, '/') . '(?![a-z0-9_-])/', $sources);
        });
        $this->assertSame(array(), array_values($missing));
    }

    public function test_les_controles_morts_ont_disparu() {
        $this->assertSame(array(), array_values(array_intersect(self::REMOVED_CONTROLS, array_keys(self::controls()))));
    }

    public function test_les_controles_de_style_n_ont_pas_de_defaut() {
        $controls = self::controls();
        foreach (self::STYLE_CONTROLS_WITHOUT_DEFAULT as $id) {
            $this->assertArrayHasKey($id, $controls, $id);
            $this->assertArrayNotHasKey('default', $controls[$id], $id);
        }
    }

    public function test_la_largeur_de_sidebar_pilote_la_variable_css() {
        $selectors = self::controls()['sidebar_width']['selectors'];

        $this->assertSame(array('{{WRAPPER}} .gfo-map-container' => '--gfo-sidebar-width: {{SIZE}}{{UNIT}};'), $selectors);
    }

    public function test_les_defauts_des_controles_viennent_des_valeurs_partagees() {
        add_filter('geofolio_defaults', static function ($defaults) {
            return array_merge($defaults, array('sidebar_title' => 'Titre filtré', 'zoom' => '11', 'center_lat' => '45.1'));
        });
        $controls = self::controls();

        $this->assertSame('Titre filtré', $controls['sidebar_title']['default']);
        $this->assertSame(11, $controls['zoom']['default']['size']);
        $this->assertSame(45.1, $controls['center_lat']['default']);
    }

    public function test_le_rendu_n_utilise_plus_de_chaine_shortcode() {
        $widget = new MapWidget();
        $widget->test_settings = array(
            'sidebar_title' => 'Nos lieux ] et "plus"',
            'map_height'    => array('size' => 500, 'unit' => 'px'),
            'zoom'          => array('size' => 9),
        );
        $html = $widget->test_render();

        $this->assertStringContainsString('Nos lieux ] et &quot;plus&quot;', $html);
        $this->assertStringContainsString('height: 500px;', $html);
        $this->assertStringContainsString('data-zoom="9"', $html);
    }

    public function test_le_rendu_sans_reglage_utilise_les_valeurs_partagees() {
        $widget = new MapWidget();
        $html   = $widget->test_render();

        $this->assertStringContainsString('data-center-lat="46.6034"', $html);
        $this->assertStringContainsString('height: 600px;', $html);
    }
}
