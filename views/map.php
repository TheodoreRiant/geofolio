<?php
/**
 * Gabarit de la carte : structure attendue par assets/js/geofolio.js.
 *
 *   .gfo-map-container > .gfo-map-sidebar + .gfo-map-wrapper
 *
 * Reçoit $view, préparé par Renderer::prepare() : toutes les
 * valeurs y sont déjà typées, le gabarit ne fait qu'échapper.
 *
 * @var array $view
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div<?php foreach ($view['container'] as $geofolio_attr => $geofolio_value) : ?> <?php echo esc_attr($geofolio_attr); ?>="<?php echo esc_attr($geofolio_value); ?>"<?php endforeach; ?>>

    <?php if ($view['has_sidebar']) : ?>
    <div class="gfo-map-sidebar">
        <div class="gfo-sidebar-inner">

            <div class="gfo-sidebar-header">
                <h3 class="gfo-sidebar-title"><?php echo esc_html($view['sidebar_title']); ?></h3>
                <p class="gfo-sidebar-subtitle"><?php echo esc_html($view['sidebar_subtitle']); ?></p>

                <?php if ($view['show_search']) : ?>
                <div class="gfo-search-row">
                    <input type="text"
                           class="gfo-search-input"
                           placeholder="<?php esc_attr_e('Search for a place...', 'geofolio'); ?>"
                           autocomplete="off"
                           role="combobox"
                           aria-autocomplete="list"
                           aria-expanded="false"
                           aria-haspopup="listbox"
                           aria-controls="gfo-ac-desktop-<?php echo esc_attr($view['map_id']); ?>" />
                    <button type="button"
                            class="gfo-search-btn"
                            aria-label="<?php esc_attr_e('Search', 'geofolio'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2.5"
                             stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </button>
                    <div id="gfo-ac-desktop-<?php echo esc_attr($view['map_id']); ?>"
                         class="gfo-autocomplete-results"
                         role="listbox"
                         hidden></div>
                </div>
                <?php endif; ?>

                <?php if ($view['show_filter']) : ?>
                <select class="gfo-filter-select"
                        aria-label="<?php esc_attr_e('Filter by type', 'geofolio'); ?>">
                    <option value=""><?php esc_html_e('All types', 'geofolio'); ?></option>
                </select>
                <?php endif; ?>
            </div><!-- .gfo-sidebar-header -->

            <?php if ($view['show_list']) : ?>
            <div class="gfo-results-header">
                <span class="gfo-results-count" aria-live="polite">0</span>
                <?php esc_html_e('place(s)', 'geofolio'); ?>
            </div>

            <div class="gfo-place-list" role="list">
                <!-- Populated by JS -->
            </div>
            <?php endif; ?>

        </div><!-- .gfo-sidebar-inner -->
    </div><!-- .gfo-map-sidebar -->
    <?php endif; ?>

    <div class="gfo-map-wrapper" style="height: <?php echo esc_attr($view['height']); ?>;">

        <?php if ($view['has_sidebar']) : ?>
        <div class="gfo-mobile-toolbar">
            <?php if ($view['show_search']) : ?>
            <div class="gfo-search-row">
                <input type="text"
                       class="gfo-search-input"
                       placeholder="<?php esc_attr_e('Search...', 'geofolio'); ?>"
                       autocomplete="off"
                       role="combobox"
                       aria-autocomplete="list"
                       aria-expanded="false"
                       aria-haspopup="listbox"
                       aria-controls="gfo-ac-mobile-<?php echo esc_attr($view['map_id']); ?>" />
                <button type="button"
                        class="gfo-search-btn"
                        aria-label="<?php esc_attr_e('Search', 'geofolio'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </button>
                <div id="gfo-ac-mobile-<?php echo esc_attr($view['map_id']); ?>"
                     class="gfo-autocomplete-results"
                     role="listbox"
                     hidden></div>
            </div>
            <?php endif; ?>

            <?php if ($view['show_filter']) : ?>
            <select class="gfo-filter-select"
                    aria-label="<?php esc_attr_e('Filter by type', 'geofolio'); ?>">
                <option value=""><?php esc_html_e('All types', 'geofolio'); ?></option>
            </select>
            <?php endif; ?>
        </div><!-- .gfo-mobile-toolbar -->
        <?php endif; ?>

        <div class="gfo-map-canvas"></div>

        <div class="gfo-map-loading" aria-live="polite">
            <div class="gfo-spinner"></div>
            <span><?php esc_html_e('Loading map...', 'geofolio'); ?></span>
        </div>

        <?php if ($view['show_fullscreen']) : ?>
        <button type="button"
                class="gfo-fullscreen-btn"
                title="<?php esc_attr_e('Full screen', 'geofolio'); ?>"
                aria-label="<?php esc_attr_e('Enter full screen', 'geofolio'); ?>">
            <svg class="icon-enter" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 3 21 3 21 9"></polyline>
                <polyline points="9 21 3 21 3 15"></polyline>
                <line x1="21" y1="3" x2="14" y2="10"></line>
                <line x1="3" y1="21" x2="10" y2="14"></line>
            </svg>
            <svg class="icon-exit" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
                <polyline points="4 14 10 14 10 20"></polyline>
                <polyline points="20 10 14 10 14 4"></polyline>
                <line x1="10" y1="14" x2="3" y2="21"></line>
                <line x1="21" y1="3" x2="14" y2="10"></line>
            </svg>
        </button>
        <?php endif; ?>

    </div><!-- .gfo-map-wrapper -->

</div><!-- .gfo-map-container -->
