<?php
/**
 * Onglet « Apparence » de la page de réglages.
 *
 * @var array $view
 */

if (!defined('ABSPATH')) {
    exit;
}

$geofolio_name = \Geofolio\Admin\AppearanceSettings::OPTION_NAME;
$geofolio_set  = $view['appearance'];
?>
<div class="wrap">
    <h1><?php esc_html_e('Map configuration', 'geofolio'); ?></h1>
    <?php include __DIR__ . '/settings-tabs.php'; ?>

    <?php settings_errors($geofolio_name); ?>

    <form method="post" action="options.php">
        <?php settings_fields(\Geofolio\Admin\AppearanceSettings::OPTION_GROUP); ?>

        <p class="description"><?php esc_html_e('Applies to every map of the site. Leave a field empty to keep the plugin default. A map built with the Elementor widget can still override these values in its own style settings.', 'geofolio'); ?></p>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="geofolio_primary_color"><?php esc_html_e('Main colour', 'geofolio'); ?></label></th>
                <td>
                    <input type="text" id="geofolio_primary_color" class="gfo-color-field"
                           name="<?php echo esc_attr($geofolio_name); ?>[primary_color]"
                           value="<?php echo esc_attr($geofolio_set['primary_color']); ?>"
                           data-default-color="<?php echo esc_attr($view['defaults']['primary_color']); ?>" />
                    <p class="description"><?php esc_html_e('Titles, buttons, clusters, and markers of places without an entity colour.', 'geofolio'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="geofolio_accent_color"><?php esc_html_e('Accent colour', 'geofolio'); ?></label></th>
                <td>
                    <input type="text" id="geofolio_accent_color" class="gfo-color-field"
                           name="<?php echo esc_attr($geofolio_name); ?>[accent_color]"
                           value="<?php echo esc_attr($geofolio_set['accent_color']); ?>" />
                    <p class="description"><?php esc_html_e('Links, phone numbers and highlights.', 'geofolio'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Font', 'geofolio'); ?></th>
                <td>
                    <fieldset>
                        <label><input type="radio" name="<?php echo esc_attr($geofolio_name); ?>[font]" value="<?php echo esc_attr(\Geofolio\Admin\AppearanceSettings::FONT_BUNDLED); ?>"<?php checked($geofolio_set['font'], \Geofolio\Admin\AppearanceSettings::FONT_BUNDLED); ?> />
                            <?php esc_html_e('Poppins, served by the plugin', 'geofolio'); ?></label><br />
                        <label><input type="radio" name="<?php echo esc_attr($geofolio_name); ?>[font]" value="<?php echo esc_attr(\Geofolio\Admin\AppearanceSettings::FONT_THEME); ?>"<?php checked($geofolio_set['font'], \Geofolio\Admin\AppearanceSettings::FONT_THEME); ?> />
                            <?php esc_html_e('The font of your theme', 'geofolio'); ?></label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="geofolio_radius"><?php esc_html_e('Corner radius', 'geofolio'); ?></label></th>
                <td>
                    <input type="number" id="geofolio_radius" class="small-text" min="0"
                           max="<?php echo esc_attr((string) \Geofolio\Admin\AppearanceSettings::MAX_RADIUS); ?>"
                           name="<?php echo esc_attr($geofolio_name); ?>[radius]"
                           value="<?php echo esc_attr($geofolio_set['radius']); ?>" placeholder="10" /> px
                    <p class="description"><?php esc_html_e('Rounding of cards, fields, popups and pills. 0 gives square corners.', 'geofolio'); ?></p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
