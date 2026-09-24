<?php
/**
 * Tests de la validation des attributs du shortcode [geofolio].
 *
 * La hauteur est injectée dans un attribut style : seule une longueur CSS
 * simple doit passer, jamais une règle supplémentaire.
 */

use PHPUnit\Framework\TestCase;
use Geofolio\Map\Defaults;
use Geofolio\Map\Renderer;
use Geofolio\Map\Shortcode;

final class ShortcodeTest extends TestCase {

    private static function length($value) {
        return Renderer::sanitize_css_length($value, Defaults::HEIGHT);
    }

    public function test_la_hauteur_par_defaut_vaut_600px() {
        $this->assertSame('600px', Defaults::HEIGHT);
    }

    public function test_les_longueurs_valides_sont_conservees() {
        foreach (array('600px', '100vh', '50%', '37.5rem', '2em', '80vw') as $value) {
            $this->assertSame($value, self::length($value), $value);
        }
    }

    public function test_les_espaces_autour_de_la_valeur_sont_retires() {
        $this->assertSame('100vh', self::length(' 100vh '));
    }

    public function test_une_regle_css_ajoutee_est_refusee() {
        $this->assertSame('600px', self::length('600px;background:url(x)'));
    }

    public function test_une_expression_est_refusee() {
        $this->assertSame('600px', self::length('expression(1)'));
    }

    public function test_une_valeur_vide_ou_sans_unite_est_refusee() {
        $this->assertSame('600px', self::length(''));
        $this->assertSame('600px', self::length('600'));
        $this->assertSame('600px', self::length('px'));
        $this->assertSame('600px', self::length(null));
    }

    public function test_une_unite_inconnue_ou_un_calcul_sont_refuses() {
        $this->assertSame('600px', self::length('600pt'));
        $this->assertSame('600px', self::length('calc(100vh - 10px)'));
        $this->assertSame('600px', self::length("100vh\n;color:red"));
    }

    public function test_le_defaut_fourni_est_retourne_tel_quel() {
        $this->assertSame('100vh', Renderer::sanitize_css_length('nope', '100vh'));
    }

    public function test_les_attributs_types_et_regions_ne_sont_plus_emis() {
        $shortcode = (new \ReflectionClass(Shortcode::class))->newInstanceWithoutConstructor();
        $html      = $shortcode->render_shortcode(array('types' => 'mecs', 'regions' => 'ara'));

        $this->assertStringNotContainsString('data-types', $html);
        $this->assertStringNotContainsString('data-regions', $html);
    }

    public function test_le_shortcode_complete_avec_les_valeurs_par_defaut() {
        $shortcode = (new \ReflectionClass(Shortcode::class))->newInstanceWithoutConstructor();
        $html      = $shortcode->render_shortcode('');

        $this->assertStringContainsString('data-zoom="' . Defaults::ZOOM . '"', $html);
    }
}
