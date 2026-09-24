<?php
/**
 * Tests de l'exécuteur de migrations générique : les étapes viennent du
 * filtre geofolio_migration_steps, le cœur n'en déclare aucune.
 */

use PHPUnit\Framework\TestCase;
use Geofolio\Migration\Runner;
use Geofolio\Migration\Step;

final class FakeMigrationStep implements Step {
    public static $calls = array();
    private $id;
    private $fail;

    public function __construct($id, $fail = false) {
        $this->id   = $id;
        $this->fail = $fail;
    }

    public function id() {
        return $this->id;
    }

    public function run() {
        self::$calls[] = $this->id;
        if ($this->fail) {
            throw new \RuntimeException('étape en échec');
        }
        return array('done' => $this->id);
    }
}

final class MigrationRunnerTest extends TestCase {

    protected function setUp(): void {
        gfo_test_reset();
        gfo_test_reset_posts(array('manage_options'));
        FakeMigrationStep::$calls = array();
    }

    protected function tearDown(): void {
        gfo_test_reset_filters();
    }

    private static function runner(): Runner {
        return new Runner(static function () {
            return 'snapshot-test';
        });
    }

    private static function register(array $steps) {
        add_filter('geofolio_migration_steps', static function ($existing) use ($steps) {
            return array_merge($existing, $steps);
        });
    }

    public function test_le_coeur_n_enregistre_aucune_etape() {
        $this->assertSame(array(), self::runner()->steps());
        $this->assertSame(array(), self::runner()->pending());
    }

    public function test_les_etapes_s_executent_dans_l_ordre() {
        self::register(array(new FakeMigrationStep('a'), new FakeMigrationStep('b')));

        $report = self::runner()->run();

        $this->assertSame(array('a', 'b'), FakeMigrationStep::$calls);
        $this->assertSame(array('a' => array('done' => 'a'), 'b' => array('done' => 'b')), $report['steps']);
        $this->assertSame('snapshot-test', $report['snapshot']);
    }

    public function test_le_rapport_est_journalise() {
        self::register(array(new FakeMigrationStep('a')));

        $report = self::runner()->run();

        $this->assertSame($report, get_option(Runner::LOG_OPTION));
        $this->assertArrayHasKey('started', $report);
        $this->assertArrayHasKey('finished', $report);
    }

    public function test_une_etape_en_erreur_n_empeche_ni_les_suivantes_ni_le_rapport() {
        self::register(array(new FakeMigrationStep('a', true), new FakeMigrationStep('b')));

        $report = self::runner()->run();

        $this->assertSame(array('a', 'b'), FakeMigrationStep::$calls);
        $this->assertSame(array('error' => 'étape en échec'), $report['steps']['a']);
        $this->assertSame(array('done' => 'b'), $report['steps']['b']);
    }

    public function test_une_etape_executee_n_est_plus_en_attente() {
        self::register(array(new FakeMigrationStep('a'), new FakeMigrationStep('b')));
        $runner = self::runner();

        $this->assertCount(2, $runner->pending());
        $runner->run(true);
        $this->assertSame(array(), $runner->pending());
    }

    public function test_seules_les_etapes_en_attente_tournent_en_mode_automatique() {
        self::register(array(new FakeMigrationStep('a'), new FakeMigrationStep('b')));
        update_option(Runner::DONE_OPTION, array('a'));

        self::runner()->run(true);

        $this->assertSame(array('b'), FakeMigrationStep::$calls);
    }

    public function test_une_entree_invalide_ou_un_doublon_sont_ignores() {
        self::register(array(new FakeMigrationStep('a'), 'pas une étape', new \stdClass(), new FakeMigrationStep('a')));

        $ids = array_map(static function ($step) { return $step->id(); }, self::runner()->steps());
        $this->assertSame(array('a'), $ids);
    }

    public function test_sans_droit_d_administration_rien_ne_tourne() {
        gfo_test_reset_posts(array('edit_posts'));
        self::register(array(new FakeMigrationStep('a')));

        $this->assertSame(array('error' => 'permission denied'), self::runner()->run());
        $this->assertSame(array(), FakeMigrationStep::$calls);
    }
}
