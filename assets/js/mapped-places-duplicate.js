/**
 * Mapped Places — duplication d'un établissement dans l'éditeur de blocs.
 *
 * Ajoute le lien « Dupliquer cet établissement » au panneau de la fiche et,
 * juste après une duplication, un avis rappelant de modifier l'adresse.
 * Données fournies par wp_localize_script (mappedPlacesDuplicate).
 */
(function (wp, config) {
    'use strict';

    if (!wp || !config) return;

    var el = wp.element.createElement;
    var PostStatusInfo = (wp.editor && wp.editor.PluginPostStatusInfo)
        || (wp.editPost && wp.editPost.PluginPostStatusInfo);

    if (PostStatusInfo && wp.plugins && config.url) {
        wp.plugins.registerPlugin('mapped-places-duplicate', {
            render: function () {
                return el(PostStatusInfo, { className: 'mapl-duplicate' },
                    el('a', { href: config.url }, config.label));
            },
        });
    }

    if (config.notice && wp.data) {
        wp.data.dispatch('core/notices').createNotice('success', config.notice, {
            id: 'mapl-map-duplicated',
            isDismissible: true,
        });
    }
})(window.wp, window.mappedPlacesDuplicate);
