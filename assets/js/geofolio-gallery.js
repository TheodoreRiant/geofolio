/**
 * Geofolio — Galerie photos des établissements.
 *
 * Script VOLONTAIREMENT INDEPENDANT de gfo-map-admin.js : ce dernier
 * initialise une carte Leaflet chargée depuis un CDN. Si ce CDN est bloqué
 * ou lent, l'exception ne doit pas empêcher d'ajouter des photos.
 *
 * Les gestionnaires d'événements sont DELEGUES au document : dans l'éditeur
 * de blocs, WordPress déplace les meta boxes dans l'arbre React après le
 * chargement de la page, et un binding posé directement sur les boutons au
 * moment du `ready` peut ne rien trouver à attacher.
 */
(function ($) {
    'use strict';

    const INPUT_NAME  = 'geofolio_gallery';
    const SELECTORS = {
        // Cible par NAME et non par id : WordPress donne au conteneur de meta
        // box l'id passe a add_meta_box(). Un champ portant le meme id serait
        // masque par ce conteneur (getElementById renvoie le premier du DOM).
        input:   'input[name="geofolio_gallery"]',
        preview: '#geofolio_gallery_preview',
        select:  '#geofolio_gallery_select',
        clear:   '#geofolio_gallery_clear',
        remove:  '.gfo-gallery-remove',
        item:    '.gfo-gallery-item',
        error:   '.gfo-gallery-error',
    };

    /** Délai maximal d'attente de la meta box (éditeur de blocs). */
    const OBSERVE_TIMEOUT_MS = 15000;

    /** Cadre médiathèque, créé à la première ouverture. */
    let frame = null;

    /**
     * Libellés traduits, fournis par PHP (wp_localize_script).
     */
    function t(key) {
        const i18n = (window.geofolioGallery && geofolioGallery.i18n) || {};
        return i18n[key] || '';
    }

    /* ================================================================ */
    /*  ETAT : le champ caché est la seule source de vérité              */
    /* ================================================================ */

    /**
     * Retrouver le champ caché, par son name (cf. SELECTORS.input).
     *
     * @return {HTMLInputElement|null}
     */
    function findInput() {
        return document.querySelector('input[name="' + INPUT_NAME + '"]');
    }

    /**
     * Lire les IDs de pièces jointes depuis le champ caché.
     *
     * @return {number[]} Nouvelle liste, dédoublonnée, sans valeur invalide.
     */
    function readIds() {
        const raw = $(SELECTORS.input).val();
        if (!raw) {
            return [];
        }

        let parsed;
        try {
            parsed = JSON.parse(raw);
        } catch (error) {
            window.console && console.error('[geofolio] Galerie illisible :', error);
            showError(t('parseError'));
            return [];
        }

        if (!Array.isArray(parsed)) {
            return [];
        }

        return parsed
            .map(function (id) { return parseInt(id, 10); })
            .filter(function (id, index, ids) {
                return id > 0 && ids.indexOf(id) === index;
            });
    }

    /**
     * Enregistrer une NOUVELLE liste d'IDs et rafraîchir l'aperçu.
     * Aucune mutation en place : l'appelant construit toujours un nouveau tableau.
     *
     * @param {number[]} ids Liste à écrire.
     */
    function writeIds(ids) {
        const $input = $(SELECTORS.input);
        if (!$input.length) {
            return;
        }

        $input.val(JSON.stringify(ids));
        $(SELECTORS.clear).prop('hidden', ids.length === 0);
        renderPreview(ids);
    }

    /* ================================================================ */
    /*  MESSAGES D'ERREUR VISIBLES                                       */
    /* ================================================================ */

    /**
     * Afficher un message dans la meta box plutôt que d'échouer en silence.
     *
     * @param {string} message Texte à afficher.
     */
    function showError(message) {
        const $preview = $(SELECTORS.preview);
        if (!$preview.length || !message) {
            return;
        }

        let $error = $preview.siblings(SELECTORS.error);
        if (!$error.length) {
            $error = $('<p>', { 'class': 'gfo-gallery-error notice notice-error' });
            $preview.before($error);
        }
        $error.text(message);
    }

    /** Effacer le message d'erreur éventuel. */
    function clearError() {
        $(SELECTORS.preview).siblings(SELECTORS.error).remove();
    }

    /* ================================================================ */
    /*  MEDIATHEQUE                                                      */
    /* ================================================================ */

    /**
     * Ouvrir la médiathèque pour choisir les photos.
     */
    function openFrame() {
        if (typeof wp === 'undefined' || !wp.media) {
            window.console && console.error('[geofolio] wp.media est indisponible : wp_enqueue_media() n\'a pas été exécuté sur cet écran.');
            showError(t('mediaMissing'));
            return;
        }

        clearError();

        if (!frame) {
            frame = wp.media({
                title:    t('frameTitle'),
                button:   { text: t('frameButton') },
                library:  { type: 'image' },
                multiple: true,
            });

            // La pre-selection DOIT se faire ici : wp.media() ne construit ses
            // etats qu'a l'ouverture, donc frame.state() vaut `undefined` tant
            // que open() n'a pas ete appele. L'appeler avant levait une
            // TypeError qui empechait la mediatheque de s'ouvrir.
            frame.on('open', function () {
                preselect(readIds());
            });

            frame.on('select', function () {
                const current   = readIds();
                const selection = frame.state().get('selection');
                const added     = [];

                selection.each(function (attachment) {
                    const id = parseInt(attachment.id, 10);
                    if (id > 0 && current.indexOf(id) === -1 && added.indexOf(id) === -1) {
                        added.push(id);
                    }
                });

                writeIds(current.concat(added));
            });
        }

        frame.open();
    }

    /**
     * Pré-cocher les photos déjà retenues dans la médiathèque.
     *
     * @param {number[]} ids Photos actuellement dans la galerie.
     */
    function preselect(ids) {
        const selection = frame.state().get('selection');
        selection.reset();

        ids.forEach(function (id) {
            const attachment = wp.media.attachment(id);
            attachment.fetch();
            selection.add(attachment);
        });
    }

    /* ================================================================ */
    /*  APERÇU                                                           */
    /* ================================================================ */

    /**
     * Redessiner la grille d'aperçu.
     *
     * @param {number[]} ids Photos à afficher, dans l'ordre.
     */
    function renderPreview(ids) {
        const $preview = $(SELECTORS.preview);
        if (!$preview.length) {
            return;
        }

        if ($preview.data('ui-sortable')) {
            $preview.sortable('destroy');
        }
        $preview.empty();

        ids.forEach(function (id) {
            $preview.append(buildItem(id));
        });

        enableSorting($preview, ids);
    }

    /**
     * Construire une vignette et déclencher le chargement de son image.
     *
     * @param {number} id ID de la pièce jointe.
     * @return {jQuery} Elément prêt à insérer.
     */
    function buildItem(id) {
        const $item = $('<div>', {
            'class':  'gfo-gallery-item',
            'data-id': id,
        });

        $item.append($('<button>', {
            type:    'button',
            'class': 'gfo-gallery-remove',
            title:   t('removeItem'),
            text:    '×',
        }));

        if (typeof wp !== 'undefined' && wp.media) {
            const attachment = wp.media.attachment(id);
            attachment.fetch().done(function () {
                const url = attachment.get('sizes') && attachment.get('sizes').thumbnail
                    ? attachment.get('sizes').thumbnail.url
                    : attachment.get('url');

                if (url) {
                    $item.prepend($('<img>', {
                        src: url,
                        alt: attachment.get('alt') || '',
                    }));
                }
            });
        }

        return $item;
    }

    /**
     * Activer le tri par glisser-déposer quand c'est pertinent.
     *
     * @param {jQuery}   $preview Conteneur de l'aperçu.
     * @param {number[]} ids      Photos affichées.
     */
    function enableSorting($preview, ids) {
        if (!$.fn.sortable || ids.length < 2) {
            return;
        }

        $preview.sortable({
            items:  SELECTORS.item,
            update: function () {
                const reordered = $preview.find(SELECTORS.item).map(function () {
                    return parseInt($(this).data('id'), 10);
                }).get().filter(function (id) {
                    return id > 0;
                });

                writeIds(reordered);
            },
        });
    }

    /* ================================================================ */
    /*  EVENEMENTS (délégués)                                            */
    /* ================================================================ */

    $(document)
        .on('click', SELECTORS.select, function (event) {
            event.preventDefault();
            openFrame();
        })
        .on('click', SELECTORS.clear, function (event) {
            event.preventDefault();
            writeIds([]);
        })
        .on('click', SELECTORS.remove, function (event) {
            event.preventDefault();
            const removed = parseInt($(this).closest(SELECTORS.item).data('id'), 10);
            writeIds(readIds().filter(function (id) { return id !== removed; }));
        });

    /* ================================================================ */
    /*  DEMARRAGE                                                        */
    /* ================================================================ */

    /** Dessiner l'aperçu initial dès que la meta box est dans le DOM. */
    function start() {
        if (findInput()) {
            renderPreview(readIds());
            return;
        }
        waitForMetaBox();
    }

    /**
     * Attendre l'insertion de la meta box (éditeur de blocs : WordPress la
     * déplace dans l'arbre React après le chargement).
     */
    function waitForMetaBox() {
        if (typeof window.MutationObserver !== 'function') {
            return;
        }

        const observer = new MutationObserver(function () {
            if (findInput()) {
                observer.disconnect();
                renderPreview(readIds());
            }
        });

        observer.observe(document.body, { childList: true, subtree: true });
        window.setTimeout(function () { observer.disconnect(); }, OBSERVE_TIMEOUT_MS);
    }

    $(document).ready(start);

})(jQuery);
