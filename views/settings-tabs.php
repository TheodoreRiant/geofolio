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
<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e('Map configuration', 'mapped-places'); ?>">
    <?php foreach ($view['tabs'] as $mapped_places_tab => $mapped_places_label) : ?>
        <a href="<?php echo esc_url(add_query_arg('tab', $mapped_places_tab, $view['page_url'])); ?>"
           class="nav-tab<?php echo $mapped_places_tab === $view['tab'] ? ' nav-tab-active' : ''; ?>"
           <?php echo $mapped_places_tab === $view['tab'] ? 'aria-current="page"' : ''; ?>><?php echo esc_html($mapped_places_label); ?></a>
    <?php endforeach; ?>
</nav>
