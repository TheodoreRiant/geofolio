<?php
/**
 * Barre d'onglets de la page de réglages (fragment inclus par chaque onglet).
 *
 * @var array $view
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e('Map configuration', 'geofolio'); ?>">
    <?php foreach ($view['tabs'] as $geofolio_tab => $geofolio_label) : ?>
        <a href="<?php echo esc_url(add_query_arg('tab', $geofolio_tab, $view['page_url'])); ?>"
           class="nav-tab<?php echo $geofolio_tab === $view['tab'] ? ' nav-tab-active' : ''; ?>"
           <?php echo $geofolio_tab === $view['tab'] ? 'aria-current="page"' : ''; ?>><?php echo esc_html($geofolio_label); ?></a>
    <?php endforeach; ?>
</nav>
