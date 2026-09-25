<?php
/**
 * Écran d'import depuis un ancien plugin de carte : ce qui va être fait
 * (d'après le filtre mapped_places_legacy_import), le rôle des personnes issues de
 * l'ancien champ « responsable », puis le lancement sur confirmation.
 *
 * @package Mapped Places
 */

namespace MappedPlaces\Migration\Legacy;

use MappedPlaces\Admin\Screen;
use MappedPlaces\Domain\Schema;
use MappedPlaces\Migration\Runner;

if (!defined('ABSPATH')) {
    exit;
}

class ImportScreen {

    /** Action admin-post du formulaire. */
    const ACTION = 'mapped_places_legacy_import';

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
            /* translators: 1: old post type, 2: Mapped Places post type */
            $lines[] = sprintf(__('Posts of type %1$s become %2$s, with their meta, terms and photos.', 'mapped-places'), $config['post_type'], Schema::POST_TYPE);
        }
        foreach ($config['taxonomies'] as $old => $new) {
            /* translators: 1: old taxonomy, 2: Mapped Places taxonomy */
            $lines[] = sprintf(__('Taxonomy %1$s becomes %2$s.', 'mapped-places'), $old, $new);
        }
        if ($config['type_icons'] !== array()) {
            /* translators: %d: number of type names in the icon catalogue */
            $lines[] = sprintf(__('Types without an icon get one from a catalogue of %d names; unmatched types are listed in the report.', 'mapped-places'), count($config['type_icons']));
        }
        if ($config['elementor_widgets'] !== array()) {
            /* translators: %s: old Elementor widget names */
            $lines[] = sprintf(__('Elementor widgets %s are renamed on every page, revisions included.', 'mapped-places'), implode(', ', $config['elementor_widgets']));
        }
        if ($config['shortcodes'] !== array()) {
            /* translators: %s: old shortcode names */
            $lines[] = sprintf(__('Shortcodes %s are replaced by [mapped-places].', 'mapped-places'), implode(', ', $config['shortcodes']));
        }
        if ($config['blocks'] !== array()) {
            /* translators: %s: old block names */
            $lines[] = sprintf(__('Blocks %s become the Mapped Places Map block, with their settings.', 'mapped-places'), implode(', ', $config['blocks']));
        }
        if ($config['place_slug'] !== '') {
            /* translators: %s: URL slug */
            $lines[] = sprintf(__('Place URLs keep the slug %s.', 'mapped-places'), $config['place_slug']);
        }
        if ($config['labels'] !== array() || $config['appearance'] !== array()) {
            $lines[] = __('Names, colours and font are set only where you have not set them yet.', 'mapped-places');
        }
        if ($config['plugin'] !== '') {
            /* translators: %s: plugin file */
            $lines[] = sprintf(__('The old plugin %s is deactivated at the end.', 'mapped-places'), $config['plugin']);
        }
        return $lines;
    }

    /**
     * Encadré sur les écrans des lieux (liste, réglages, import), tant que
     * l'import attend sa confirmation. Nulle part ailleurs dans l'admin.
     */
    public static function render() {
        if (!current_user_can('manage_options') || !Screen::is_plugin_screen() || !Runner::get_instance()->awaits_confirmation()) {
            return;
        }
        $config = Config::get();
        if ($config === array()) {
            return;
        }
        ?>
        <div class="notice notice-warning">
            <h2><?php esc_html_e('Mapped Places: import the data of your previous map plugin', 'mapped-places'); ?></h2>
            <p><?php esc_html_e('A snapshot of the data is taken first. The import will:', 'mapped-places'); ?></p>
            <ul style="list-style:disc;margin-left:1.5em">
                <?php foreach (self::summary($config) as $mapped_places_line) : ?>
                    <li><?php echo esc_html($mapped_places_line); ?></li>
                <?php endforeach; ?>
            </ul>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="<?php echo esc_attr(self::ACTION); ?>" />
                <?php wp_nonce_field(self::ACTION); ?>
                <p>
                    <label for="mapped_places_legacy_role"><?php esc_html_e('Role given to the people of the former manager field:', 'mapped-places'); ?></label>
                    <input type="text" id="mapped_places_legacy_role" name="mapped_places_legacy_role" class="regular-text"
                           value="<?php echo esc_attr(ImportStep::manager_role($config)); ?>" />
                </p>
                <p><?php submit_button(__('Run the import', 'mapped-places'), 'primary', 'submit', false); ?></p>
            </form>
        </div>
        <?php
    }

    /**
     * Lancer l'import confirmé, puis toutes les étapes en attente.
     */
    public static function handle() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Permission denied', 'mapped-places'));
        }
        check_admin_referer(self::ACTION);

        if (isset($_POST['mapped_places_legacy_role'])) {
            ImportStep::set_manager_role(sanitize_text_field(wp_unslash($_POST['mapped_places_legacy_role'])));
        }
        Runner::get_instance()->run(true);

        wp_safe_redirect(add_query_arg(
            array('page' => 'mapped-places-import', 'post_type' => Schema::POST_TYPE, 'migrated' => '1'),
            admin_url('edit.php')
        ));
        exit;
    }
}
