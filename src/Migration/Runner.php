<?php
/**
 * Exécuteur de migrations de données, générique.
 *
 * Les étapes sont fournies par le filtre geofolio_migration_steps (un
 * préréglage ou un plugin compagnon y enregistre les siennes) ; le cœur n'en
 * déclare aucune. Chaque exécution est précédée d'un snapshot de
 * restauration et journalisée. Une étape déjà exécutée ne repart pas en
 * automatique ; le bouton d'administration les relance toutes (elles doivent
 * donc être idempotentes).
 */

namespace Geofolio\Migration;

use Geofolio\Domain\Schema;

if (!defined('ABSPATH')) {
    exit;
}

class Runner {

    /** Identifiants des étapes déjà exécutées. */
    const DONE_OPTION = 'geofolio_migrations_done';

    /** Rapport de la dernière exécution. */
    const LOG_OPTION = 'geofolio_migration_log_last';

    /** Verrou contre deux exécutions concurrentes. */
    const LOCK = 'geofolio_migration_lock';

    /** Durée du verrou, en secondes. */
    const LOCK_TTL = 30;

    /** Action admin-post du bouton de relance. */
    const ACTION = 'geofolio_run_migration';

    private static $instance = null;

    /** @var callable Crée un snapshot et retourne sa clé. */
    private $snapshot;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
            self::$instance->hook();
        }
        return self::$instance;
    }

    /**
     * @param callable|null $snapshot Fabrique de snapshot (tests).
     */
    public function __construct($snapshot = null) {
        $this->snapshot = $snapshot ?: array(Snapshot::class, 'create');
    }

    private function hook() {
        add_action('admin_init', array($this, 'maybe_run_auto'));
        add_action('admin_post_' . self::ACTION, array($this, 'admin_handle_run'));
        add_action('admin_notices', array($this, 'admin_notice'));
    }

    /**
     * Étapes enregistrées, valides et sans doublon, dans l'ordre du filtre.
     *
     * @return Step[]
     */
    public function steps() {
        $steps = array();
        foreach ((array) apply_filters('geofolio_migration_steps', array()) as $step) {
            if ($step instanceof Step && !isset($steps[$step->id()])) {
                $steps[$step->id()] = $step;
            }
        }
        return array_values($steps);
    }

    /**
     * Étapes jamais exécutées.
     *
     * @return Step[]
     */
    public function pending() {
        $done = (array) get_option(self::DONE_OPTION, array());
        return array_values(array_filter($this->steps(), static function ($step) use ($done) {
            return !in_array($step->id(), $done, true);
        }));
    }

    /**
     * Exécuter les étapes en attente quand un administrateur visite
     * l'administration. Le verrou transitoire évite une double exécution.
     */
    public function maybe_run_auto() {
        // Une étape à confirmer suspend tout : les suivantes supposent
        // souvent ses données, et tourner avant les marquerait faites à vide.
        if (!current_user_can('manage_options') || !$this->pending() || $this->awaits_confirmation() || get_transient(self::LOCK)) {
            return;
        }
        set_transient(self::LOCK, 1, self::LOCK_TTL);
        try {
            $this->run(true);
        } finally {
            delete_transient(self::LOCK);
        }
    }

    /**
     * Une étape en attente demande-t-elle une confirmation ?
     *
     * @return bool
     */
    public function awaits_confirmation() {
        foreach ($this->pending() as $step) {
            if ($step instanceof ConfirmedStep && $step->requires_confirmation()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Exécuter les étapes (toutes, ou seulement celles en attente). Une
     * étape en erreur est consignée sans interrompre les suivantes.
     *
     * @param bool $only_pending
     * @return array Rapport.
     */
    public function run($only_pending = false) {
        if (!current_user_can('manage_options')) {
            return array('error' => 'permission denied');
        }

        $steps  = $only_pending ? $this->pending() : $this->steps();
        $report = array(
            'snapshot' => $steps ? call_user_func($this->snapshot) : '',
            'started'  => current_time('mysql'),
            'steps'    => array(),
        );

        foreach ($steps as $step) {
            $report['steps'][$step->id()] = self::run_step($step);
        }
        $report['finished'] = current_time('mysql');

        $done = (array) get_option(self::DONE_OPTION, array());
        update_option(self::DONE_OPTION, array_values(array_unique(array_merge($done, array_keys($report['steps'])))), false);
        update_option(self::LOG_OPTION, $report, false);

        return $report;
    }

    /**
     * @param Step $step
     * @return array
     */
    private static function run_step(Step $step) {
        try {
            $result = $step->run();
            return is_array($result) ? $result : array('result' => $result);
        } catch (\Throwable $e) {
            return array('error' => $e->getMessage());
        }
    }

    /**
     * Bouton « Relancer la migration » : toutes les étapes.
     */
    public function admin_handle_run() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Permission denied', 'geofolio'));
        }
        check_admin_referer(self::ACTION);

        $this->run();

        wp_safe_redirect(add_query_arg(
            array(
                'page'      => 'geofolio-import',
                'post_type' => Schema::POST_TYPE,
                'migrated'  => '1',
            ),
            admin_url('edit.php')
        ));
        exit;
    }

    /**
     * Notice sur les écrans des établissements, avec le bouton de relance,
     * quand des étapes sont enregistrées et toutes exécutées.
     */
    public function admin_notice() {
        if (!current_user_can('manage_options') || !$this->steps() || $this->pending()) {
            return;
        }

        $current_screen = get_current_screen();
        if (!$current_screen || strpos($current_screen->id, Schema::POST_TYPE) === false) {
            return;
        }

        $url = wp_nonce_url(admin_url('admin-post.php?action=' . self::ACTION), self::ACTION);
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <strong>Geofolio</strong> :
                <?php esc_html_e('Data up to date.', 'geofolio'); ?>
                <a href="<?php echo esc_url($url); ?>"
                   class="button button-secondary"
                   style="margin-left:12px;">
                    <?php esc_html_e('Run the migration again (taxonomies + colours)', 'geofolio'); ?>
                </a>
            </p>
        </div>
        <?php
    }
}
