<?php
/**
 * Onglet « Libellés et défauts » de la page de réglages.
 *
 * @var array $view
 */

if (!defined('ABSPATH')) {
    exit;
}

$geofolio_name = \Geofolio\Admin\LabelsSettings::OPTION_NAME;
$geofolio_set  = $view['labels'];
$geofolio_def  = $view['defaults'];
$geofolio_text = static function ($key, $label, $placeholder = '', $help = '') use ($geofolio_name, $geofolio_set) {
    ?>
    <tr>
        <th scope="row"><label for="geofolio_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
        <td>
            <input type="text" id="geofolio_<?php echo esc_attr($key); ?>" class="regular-text"
                   name="<?php echo esc_attr($geofolio_name); ?>[<?php echo esc_attr($key); ?>]"
                   value="<?php echo esc_attr($geofolio_set[$key]); ?>"
                   placeholder="<?php echo esc_attr($placeholder); ?>" />
            <?php if ($help !== '') : ?><p class="description"><?php echo esc_html($help); ?></p><?php endif; ?>
        </td>
    </tr>
    <?php
};
?>
<div class="wrap">
    <h1><?php esc_html_e('Map configuration', 'geofolio'); ?></h1>
    <?php include __DIR__ . '/settings-tabs.php'; ?>

    <?php settings_errors($geofolio_name); ?>

    <form method="post" action="options.php">
        <?php settings_fields(\Geofolio\Admin\LabelsSettings::OPTION_GROUP); ?>

        <h2><?php esc_html_e('Names', 'geofolio'); ?></h2>
        <p class="description"><?php esc_html_e('How places and entities are called in the admin menus. Leave empty to keep the default names.', 'geofolio'); ?></p>
        <table class="form-table" role="presentation">
            <?php
            $geofolio_text('place_singular', __('Place, singular', 'geofolio'), __('Place', 'geofolio'));
            $geofolio_text('place_plural', __('Place, plural', 'geofolio'), __('Places', 'geofolio'));
            $geofolio_text('entity_singular', __('Entity, singular', 'geofolio'), __('Entity', 'geofolio'));
            $geofolio_text('entity_plural', __('Entity, plural', 'geofolio'), __('Entities', 'geofolio'));
            $geofolio_text('place_slug', __('URL slug of places', 'geofolio'), $geofolio_def['place_slug'], __('The part of the address before the name of each place. Changing it changes the URL of every place page.', 'geofolio'));
            ?>
        </table>

        <h2><?php esc_html_e('Defaults of new maps', 'geofolio'); ?></h2>
        <p class="description"><?php esc_html_e('Used by maps that do not set their own value (block, shortcode or Elementor widget).', 'geofolio'); ?></p>
        <table class="form-table" role="presentation">
            <?php
            $geofolio_text('sidebar_title', __('Sidebar title', 'geofolio'), $geofolio_def['sidebar_title']);
            $geofolio_text('sidebar_subtitle', __('Sidebar subtitle', 'geofolio'));
            $geofolio_text('center_lat', __('Centre latitude', 'geofolio'), $geofolio_def['center_lat']);
            $geofolio_text('center_lng', __('Centre longitude', 'geofolio'), $geofolio_def['center_lng']);
            $geofolio_text('zoom', __('Initial zoom', 'geofolio'), $geofolio_def['zoom']);
            ?>
            <tr>
                <th scope="row"><label for="geofolio_fit_bounds"><?php esc_html_e('Fit the view to the places', 'geofolio'); ?></label></th>
                <td>
                    <select id="geofolio_fit_bounds" name="<?php echo esc_attr($geofolio_name); ?>[fit_bounds]">
                        <option value=""<?php selected($geofolio_set['fit_bounds'], ''); ?>><?php esc_html_e('Default', 'geofolio'); ?></option>
                        <option value="true"<?php selected($geofolio_set['fit_bounds'], 'true'); ?>><?php esc_html_e('Yes', 'geofolio'); ?></option>
                        <option value="false"<?php selected($geofolio_set['fit_bounds'], 'false'); ?>><?php esc_html_e('No', 'geofolio'); ?></option>
                    </select>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
