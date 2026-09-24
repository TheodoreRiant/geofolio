<?php
/**
 * Geofolio doit fonctionner sans Elementor.
 *
 * Jusqu'en 1.2.0, Plugin::elementor_widget_names() lisait MapWidget::NAME :
 * l'accès à la constante chargeait MapWidget, qui hérite de
 * \Elementor\Widget_Base, et toute page individuelle d'un site sans
 * Elementor finissait en erreur fatale.
 */

use PHPUnit\Framework\TestCase;
use Geofolio\Elementor\Integration;
use Geofolio\Elementor\MapWidget;
use Geofolio\Plugin;

final class ElementorIndependenceTest extends TestCase {

    /** Classes qui héritent d'une classe Elementor : à ne charger qu'avec Elementor. */
    const ELEMENTOR_CLASSES = array('MapWidget');

    public function test_hors_du_widget_aucune_constante_d_une_classe_elementor_n_est_lue() {
        $offenders = array();
        $iterator  = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../src'));
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php' || $file->getFilename() === 'MapWidget.php') {
                continue;
            }
            // Code seul : les commentaires peuvent citer MapWidget::register_controls().
            $code = preg_replace('#/\*.*?\*/|//[^\n]*#s', '', (string) file_get_contents($file->getPathname()));
            foreach (self::ELEMENTOR_CLASSES as $class) {
                // MapWidget::class est résolu à la compilation, sans chargement.
                if (preg_match('/\b' . $class . '::(?!class\b)/', $code)) {
                    $offenders[] = $file->getFilename();
                }
            }
        }
        $this->assertSame(array(), $offenders);
    }

    public function test_le_nom_du_widget_vient_de_l_integration() {
        $this->assertSame('geofolio_map', Integration::WIDGET_NAME);
        $this->assertSame(Integration::WIDGET_NAME, MapWidget::NAME);
        $this->assertContains(Integration::WIDGET_NAME, Plugin::elementor_widget_names());
    }
}
