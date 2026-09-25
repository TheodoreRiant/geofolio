<?php
/**
 * Onglet « Apparence » de la page de réglages.
 *
 * @var array $view
 */

if (!defined('ABSPATH')) {
    exit;
}

$mapped_places_name = \MappedPlaces\Admin\AppearanceSettings::OPTION_NAME;
$mapped_places_set  = $view['appearance'];
?>
<div class="wrap">
    <h1><?php esc_html_e('Map configuration', 'mapped-places'); ?></h1>
    <?php include __DIR__ . '/settings-tabs.php'; ?>

    <?php settings_errors($mapped_places_name); ?>

    <form method="post" action="options.php">
        <?php settings_fields(\MappedPlaces\Admin\AppearanceSettings::OPTION_GROUP); ?>

        <p class="description"><?php esc_html_e('Applies to every map of the site. Leave a field empty to keep the plugin default. A map built with the Elementor widget can still override these values in its own style settings.', 'mapped-places'); ?></p>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="mapped_places_primary_color"><?php esc_html_e('Main colour', 'mapped-places'); ?></label></th>
                <td>
                    <input type="text" id="mapped_places_primary_color" class="mapl-color-field"
                           name="<?php echo esc_attr($mapped_places_name); ?>[primary_color]"
                           value="<?php echo esc_attr($mapped_places_set['primary_color']); ?>"
                           data-default-color="<?php echo esc_attr($view['defaults']['primary_color']); ?>" />
                    <p class="description"><?php esc_html_e('Titles, buttons, clusters, and markers of places without an entity colour.', 'mapped-places'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="mapped_places_accent_color"><?php esc_html_e('Accent colour', 'mapped-places'); ?></label></th>
                <td>
                    <input type="text" id="mapped_places_accent_color" class="mapl-color-field"
                           name="<?php echo esc_attr($mapped_places_name); ?>[accent_color]"
                           value="<?php echo esc_attr($mapped_places_set['accent_color']); ?>" />
                    <p class="description"><?php esc_html_e('Links, phone numbers and highlights.', 'mapped-places'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Font', 'mapped-places'); ?></th>
                <td>
                    <fieldset>
                        <label><input type="radio" name="<?php echo esc_attr($mapped_places_name); ?>[font]" value="<?php echo esc_attr(\MappedPlaces\Admin\AppearanceSettings::FONT_BUNDLED); ?>"<?php checked($mapped_places_set['font'], \MappedPlaces\Admin\AppearanceSettings::FONT_BUNDLED); ?> />
                            <?php esc_html_e('Poppins, served by the plugin', 'mapped-places'); ?></label><br />
                        <label><input type="radio" name="<?php echo esc_attr($mapped_places_name); ?>[font]" value="<?php echo esc_attr(\MappedPlaces\Admin\AppearanceSettings::FONT_THEME); ?>"<?php checked($mapped_places_set['font'], \MappedPlaces\Admin\AppearanceSettings::FONT_THEME); ?> />
                            <?php esc_html_e('The font of your theme', 'mapped-places'); ?></label>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="mapped_places_radius"><?php esc_html_e('Corner radius', 'mapped-places'); ?></label></th>
                <td>
                    <input type="number" id="mapped_places_radius" class="small-text" min="0"
                           max="<?php echo esc_attr((string) \MappedPlaces\Admin\AppearanceSettings::MAX_RADIUS); ?>"
                           name="<?php echo esc_attr($mapped_places_name); ?>[radius]"
                           value="<?php echo esc_attr($mapped_places_set['radius']); ?>" placeholder="10" /> px
                    <p class="description"><?php esc_html_e('Rounding of cards, fields, popups and pills. 0 gives square corners.', 'mapped-places'); ?></p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
