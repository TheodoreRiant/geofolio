<?php
/**
 * Tests de la normalisation de texte utilisée pour comparer des libellés
 * (en-têtes CSV, règles d'import).
 */

use PHPUnit\Framework\TestCase;
use MappedPlaces\Support\Text;

final class TextTest extends TestCase {

    public function test_les_accents_sont_retires() {
        $this->assertSame('eleve a noel ca', Text::normalize('élève à Noël ça'));
        $this->assertSame('oeuvre aerien', Text::normalize('Œuvre aérien'));
    }

    public function test_la_casse_est_ramenee_en_minuscules() {
        $this->assertSame('centre educatif ferme', Text::normalize('CENTRE ÉDUCATIF FERMÉ'));
    }

    public function test_les_apostrophes_typographiques_deviennent_droites() {
        $this->assertSame("domaine d'intervention", Text::normalize("Domaine d\u{2019}intervention"));
        $this->assertSame("l'autre", Text::normalize("l\u{2018}autre"));
    }

    public function test_les_espaces_sont_resserres() {
        $this->assertSame('code postal', Text::normalize("  Code \t  Postal "));
    }

    public function test_une_valeur_non_textuelle_donne_une_chaine() {
        $this->assertSame('', Text::normalize(null));
        $this->assertSame('42', Text::normalize(42));
    }

    public function test_la_normalisation_est_idempotente() {
        $once = Text::normalize("Chantier d\u{2019}insertion par l'Activité");
        $this->assertSame($once, Text::normalize($once));
    }
}
