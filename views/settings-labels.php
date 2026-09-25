<?php
/**
 * Onglet « Libellés et défauts » de la page de réglages.
 *
 * @var array $view
 */

if (!defined('ABSPATH')) {
    exit;
}

$mapped_places_name = \MappedPlaces\Admin\LabelsSettings::OPTION_NAME;
$mapped_places_set  = $view['labels'];
$mapped_places_def  = $view['defaults'];
$mapped_places_text = static function ($key, $label, $placeholder = '', $help = '') use ($mapped_places_name, $mapped_places_set) {
    ?>
    <tr>
        <th scope="row"><label for="mapped_places_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
        <td>
            <input type="text" id="mapped_places_<?php echo esc_attr($key); ?>" class="regular-text"
                   name="<?php echo esc_attr($mapped_places_name); ?>[<?php echo esc_attr($key); ?>]"
                   value="<?php echo esc_attr($mapped_places_set[$key]); ?>"
                   placeholder="<?php echo esc_attr($placeholder); ?>" />
            <?php if ($help !== '') : ?><p class="description"><?php echo esc_html($help); ?></p><?php endif; ?>
        </td>
    </tr>
    <?php
};
?>
<div class="wrap">
    <h1><?php esc_html_e('Map configuration', 'mapped-places'); ?></h1>
    <?php include __DIR__ . '/settings-tabs.php'; ?>

    <?php settings_errors($mapped_places_name); ?>

    <form method="post" action="options.php">
        <?php settings_fields(\MappedPlaces\Admin\LabelsSettings::OPTION_GROUP); ?>

        <h2><?php esc_html_e('Names', 'mapped-places'); ?></h2>
        <p class="description"><?php esc_html_e('How places and entities are called in the admin menus. Leave empty to keep the default names.', 'mapped-places'); ?></p>
        <table class="form-table" role="presentation">
            <?php
            $mapped_places_text('place_singular', __('Place, singular', 'mapped-places'), __('Place', 'mapped-places'));
            $mapped_places_text('place_plural', __('Place, plural', 'mapped-places'), __('Places', 'mapped-places'));
            $mapped_places_text('entity_singular', __('Entity, singular', 'mapped-places'), __('Entity', 'mapped-places'));
            $mapped_places_text('entity_plural', __('Entity, plural', 'mapped-places'), __('Entities', 'mapped-places'));
            $mapped_places_text('place_slug', __('URL slug of places', 'mapped-places'), $mapped_places_def['place_slug'], __('The part of the address before the name of each place. Changing it changes the URL of every place page.', 'mapped-places'));
            ?>
        </table>

        <h2><?php esc_html_e('Defaults of new maps', 'mapped-places'); ?></h2>
        <p class="description"><?php esc_html_e('Used by maps that do not set their own value (block, shortcode or Elementor widget).', 'mapped-places'); ?></p>
        <table class="form-table" role="presentation">
            <?php
            $mapped_places_text('sidebar_title', __('Sidebar title', 'mapped-places'), $mapped_places_def['sidebar_title']);
            $mapped_places_text('sidebar_subtitle', __('Sidebar subtitle', 'mapped-places'));
            $mapped_places_text('center_lat', __('Centre latitude', 'mapped-places'), $mapped_places_def['center_lat']);
            $mapped_places_text('center_lng', __('Centre longitude', 'mapped-places'), $mapped_places_def['center_lng']);
            $mapped_places_text('zoom', __('Initial zoom', 'mapped-places'), $mapped_places_def['zoom']);
            ?>
            <tr>
                <th scope="row"><label for="mapped_places_fit_bounds"><?php esc_html_e('Fit the view to the places', 'mapped-places'); ?></label></th>
                <td>
                    <select id="mapped_places_fit_bounds" name="<?php echo esc_attr($mapped_places_name); ?>[fit_bounds]">
                        <option value=""<?php selected($mapped_places_set['fit_bounds'], ''); ?>><?php esc_html_e('Default', 'mapped-places'); ?></option>
                        <option value="true"<?php selected($mapped_places_set['fit_bounds'], 'true'); ?>><?php esc_html_e('Yes', 'mapped-places'); ?></option>
                        <option value="false"<?php selected($mapped_places_set['fit_bounds'], 'false'); ?>><?php esc_html_e('No', 'mapped-places'); ?></option>
                    </select>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
