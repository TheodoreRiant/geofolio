<?php
/**
 * Gabarit de la carte : structure attendue par assets/js/mapped-places.js.
 *
 *   .mapl-map-container > .mapl-map-sidebar + .mapl-map-wrapper
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
<div<?php foreach ($view['container'] as $mapped_places_attr => $mapped_places_value) : ?> <?php echo esc_attr($mapped_places_attr); ?>="<?php echo esc_attr($mapped_places_value); ?>"<?php endforeach; ?>>

    <?php if ($view['has_sidebar']) : ?>
    <div class="mapl-map-sidebar">
        <div class="mapl-sidebar-inner">

            <div class="mapl-sidebar-header">
                <h3 class="mapl-sidebar-title"><?php echo esc_html($view['sidebar_title']); ?></h3>
                <p class="mapl-sidebar-subtitle"><?php echo esc_html($view['sidebar_subtitle']); ?></p>

                <?php if ($view['show_search']) : ?>
                <div class="mapl-search-row">
                    <input type="text"
                           class="mapl-search-input"
                           placeholder="<?php esc_attr_e('Search for a place...', 'mapped-places'); ?>"
                           autocomplete="off"
                           role="combobox"
                           aria-autocomplete="list"
                           aria-expanded="false"
                           aria-haspopup="listbox"
                           aria-controls="mapl-ac-desktop-<?php echo esc_attr($view['map_id']); ?>" />
                    <button type="button"
                            class="mapl-search-btn"
                            aria-label="<?php esc_attr_e('Search', 'mapped-places'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor" stroke-width="2.5"
                             stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </button>
                    <div id="mapl-ac-desktop-<?php echo esc_attr($view['map_id']); ?>"
                         class="mapl-autocomplete-results"
                         role="listbox"
                         hidden></div>
                </div>
                <?php endif; ?>

                <?php if ($view['show_filter']) : ?>
                <select class="mapl-filter-select"
                        aria-label="<?php esc_attr_e('Filter by type', 'mapped-places'); ?>">
                    <option value=""><?php esc_html_e('All types', 'mapped-places'); ?></option>
                </select>
                <?php endif; ?>
            </div><!-- .mapl-sidebar-header -->

            <?php if ($view['show_list']) : ?>
            <div class="mapl-results-header">
                <span class="mapl-results-count" aria-live="polite">0</span>
                <?php esc_html_e('place(s)', 'mapped-places'); ?>
            </div>

            <div class="mapl-place-list" role="list">
                <!-- Populated by JS -->
            </div>
            <?php endif; ?>

        </div><!-- .mapl-sidebar-inner -->
    </div><!-- .mapl-map-sidebar -->
    <?php endif; ?>

    <div class="mapl-map-wrapper"<?php if ($view['height'] !== '') : ?> style="height: <?php echo esc_attr($view['height']); ?>;"<?php endif; ?>>

        <?php if ($view['has_sidebar']) : ?>
        <div class="mapl-mobile-toolbar">
            <?php if ($view['show_search']) : ?>
            <div class="mapl-search-row">
                <input type="text"
                       class="mapl-search-input"
                       placeholder="<?php esc_attr_e('Search...', 'mapped-places'); ?>"
                       autocomplete="off"
                       role="combobox"
                       aria-autocomplete="list"
                       aria-expanded="false"
                       aria-haspopup="listbox"
                       aria-controls="mapl-ac-mobile-<?php echo esc_attr($view['map_id']); ?>" />
                <button type="button"
                        class="mapl-search-btn"
                        aria-label="<?php esc_attr_e('Search', 'mapped-places'); ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5"
                         stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </button>
                <div id="mapl-ac-mobile-<?php echo esc_attr($view['map_id']); ?>"
                     class="mapl-autocomplete-results"
                     role="listbox"
                     hidden></div>
            </div>
            <?php endif; ?>

            <?php if ($view['show_filter']) : ?>
            <select class="mapl-filter-select"
                    aria-label="<?php esc_attr_e('Filter by type', 'mapped-places'); ?>">
                <option value=""><?php esc_html_e('All types', 'mapped-places'); ?></option>
            </select>
            <?php endif; ?>
        </div><!-- .mapl-mobile-toolbar -->
        <?php endif; ?>

        <div class="mapl-map-canvas"></div>

        <div class="mapl-map-loading" aria-live="polite">
            <div class="mapl-spinner"></div>
            <span><?php esc_html_e('Loading map...', 'mapped-places'); ?></span>
        </div>

        <?php if ($view['show_fullscreen']) : ?>
        <button type="button"
                class="mapl-fullscreen-btn"
                title="<?php esc_attr_e('Full screen', 'mapped-places'); ?>"
                aria-label="<?php esc_attr_e('Enter full screen', 'mapped-places'); ?>">
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

    </div><!-- .mapl-map-wrapper -->

</div><!-- .mapl-map-container -->
