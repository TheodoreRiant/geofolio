<?php
/**
 * Écran d'import depuis un ancien plugin de carte : ce qui va être fait
 * (d'après le filtre geofolio_legacy_import), le rôle des personnes issues de
 * l'ancien champ « responsable », puis le lancement sur confirmation.
 *
 * @package Geofolio
 */

namespace Geofolio\Migration\Legacy;

use Geofolio\Domain\Schema;
use Geofolio\Migration\Runner;

if (!defined('ABSPATH')) {
    exit;
}

class ImportScreen {

    /** Action admin-post du formulaire. */
    const ACTION = 'geofolio_legacy_import';

    /**
     * Brancher l'écran.
     */
    public static function register() {
        add_action('admin_notices', array(__CLASS__, 'render'));
        add_action('admin_post_' . self::ACTION, array(__CLASS__, 'handle'));
    }

    /**
     * Résumé lisible de ce que l'import va faire.
     *
     * @param array $config Configuration normalisée (Config).
     * @return string[]
     */
    public static function summary(array $config) {
        $lines = array();
        if ($config['post_type'] !== '') {
            /* translators: 1: old post type, 2: Geofolio post type */
            $lines[] = sprintf(__('Posts of type %1$s become %2$s, with their meta, terms and photos.', 'geofolio'), $config['post_type'], Schema::POST_TYPE);
        }
        foreach ($config['taxonomies'] as $old => $new) {
            /* translators: 1: old taxonomy, 2: Geofolio taxonomy */
            $lines[] = sprintf(__('Taxonomy %1$s becomes %2$s.', 'geofolio'), $old, $new);
        }
        if ($config['type_icons'] !== array()) {
            /* translators: %d: number of type names in the icon catalogue */
            $lines[] = sprintf(__('Types without an icon get one from a catalogue of %d names; unmatched types are listed in the report.', 'geofolio'), count($config['type_icons']));
        }
        if ($config['elementor_widgets'] !== array()) {
            /* translators: %s: old Elementor widget names */
            $lines[] = sprintf(__('Elementor widgets %s are renamed on every page, revisions included.', 'geofolio'), implode(', ', $config['elementor_widgets']));
        }
        if ($config['shortcodes'] !== array()) {
            /* translators: %s: old shortcode names */
            $lines[] = sprintf(__('Shortcodes %s are replaced by [geofolio].', 'geofolio'), implode(', ', $config['shortcodes']));
        }
        if ($config['place_slug'] !== '') {
            /* translators: %s: URL slug */
            $lines[] = sprintf(__('Place URLs keep the slug %s.', 'geofolio'), $config['place_slug']);
        }
        if ($config['labels'] !== array() || $config['appearance'] !== array()) {
            $lines[] = __('Names, colours and font are set only where you have not set them yet.', 'geofolio');
        }
        if ($config['plugin'] !== '') {
            /* translators: %s: plugin file */
            $lines[] = sprintf(__('The old plugin %s is deactivated at the end.', 'geofolio'), $config['plugin']);
        }
        return $lines;
    }

    /**
     * Encadré sur les écrans des lieux et des extensions, tant que l'import
     * attend sa confirmation.
     */
    public static function render() {
        if (!current_user_can('manage_options') || !Runner::get_instance()->awaits_confirmation()) {
            return;
        }
        $config = Config::get();
        if ($config === array()) {
            return;
        }
        ?>
        <div class="notice notice-warning">
            <h2><?php esc_html_e('Geofolio: import the data of your previous map plugin', 'geofolio'); ?></h2>
            <p><?php esc_html_e('A snapshot of the data is taken first. The import will:', 'geofolio'); ?></p>
            <ul style="list-style:disc;margin-left:1.5em">
                <?php foreach (self::summary($config) as $geofolio_line) : ?>
                    <li><?php echo esc_html($geofolio_line); ?></li>
                <?php endforeach; ?>
            </ul>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION); ?>" />
                <?php wp_nonce_field(self::ACTION); ?>
                <p>
                    <label for="geofolio_legacy_role"><?php esc_html_e('Role given to the people of the former manager field:', 'geofolio'); ?></label>
                    <input type="text" id="geofolio_legacy_role" name="geofolio_legacy_role" class="regular-text"
                           value="<?php echo esc_attr(ImportStep::manager_role($config)); ?>" />
                </p>
                <p><?php submit_button(__('Run the import', 'geofolio'), 'primary', 'submit', false); ?></p>
            </form>
        </div>
        <?php
    }

    /**
     * Lancer l'import confirmé, puis toutes les étapes en attente.
     */
    public static function handle() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Permission denied', 'geofolio'));
        }
        check_admin_referer(self::ACTION);

        if (isset($_POST['geofolio_legacy_role'])) {
            ImportStep::set_manager_role(sanitize_text_field(wp_unslash($_POST['geofolio_legacy_role'])));
        }
        Runner::get_instance()->run(true);

        wp_safe_redirect(add_query_arg(
            array('page' => 'geofolio-import', 'post_type' => Schema::POST_TYPE, 'migrated' => '1'),
            admin_url('edit.php')
        ));
        exit;
    }
}
