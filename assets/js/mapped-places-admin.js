/**
 * Mapped Places - Script Admin : carte de localisation et geocodage.
 *
 * La galerie photos vit dans un script SEPARE (mapl-map-gallery.js) : elle ne
 * doit pas dependre de Leaflet, charge depuis un CDN externe.
 */
(function($) {
    'use strict';

    /** Libellé traduit, fourni par PHP (mappedPlacesAdmin.i18n). */
    function t(key) {
        const i18n = (window.mappedPlacesAdmin && mappedPlacesAdmin.i18n) || {};
        return i18n[key] || '';
    }

    let map = null;
    let marker = null;

    /**
     * Initialisation.
     *
     * Chaque etape est isolee : l'echec de l'une ne doit pas empecher les
     * autres de s'executer.
     */
    function init() {
        runStep('carte de localisation', initAdminMap);
        runStep('champs de localisation', bindEvents);
        runStep('personnes', initPeople);
    }

    /**
     * Executer une etape d'initialisation en isolant ses erreurs.
     *
     * @param {string}   label Nom de l'etape, pour le journal.
     * @param {Function} step  Etape a executer.
     */
    function runStep(label, step) {
        try {
            step();
        } catch (error) {
            window.console && console.error('[mapped-places] Echec de l\'initialisation (' + label + ') :', error);
        }
    }

    /**
     * Initialiser la carte admin
     */
    function initAdminMap() {
        const mapContainer = document.getElementById('mapped_places_admin_map');
        if (!mapContainer) return;

        // Leaflet vient d'un CDN : s'il n'a pas pu etre charge, on le signale
        // au lieu de laisser une exception interrompre le reste du script.
        if (typeof L === 'undefined') {
            window.console && console.error('[mapped-places] Leaflet indisponible : la carte de localisation est desactivee.');
            mapContainer.textContent = t('mapUnavailable');
            mapContainer.className = 'mapl-admin-map-unavailable';
            return;
        }

        const center = (window.mappedPlacesAdmin && mappedPlacesAdmin.center) || [0, 0];
        const lat = parseFloat($('#mapped_places_latitude').val()) || center[0];
        const lng = parseFloat($('#mapped_places_longitude').val()) || center[1];
        const hasCoords = $('#mapped_places_latitude').val() && $('#mapped_places_longitude').val();

        map = L.map('mapped_places_admin_map').setView([lat, lng], hasCoords ? 15 : 6);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 19,
        }).addTo(map);

        // Les meta boxes de l'éditeur de blocs vivent dans un panneau
        // repliable : la carte naît dans un conteneur trop petit puis est
        // agrandie. Leaflet doit alors recalculer sa taille, sinon seul un
        // coin des tuiles s'affiche.
        if (typeof ResizeObserver === 'function') {
            new ResizeObserver(function() {
                map.invalidateSize();
            }).observe(document.getElementById('mapped_places_admin_map'));
        }

        // Ajouter le marqueur si des coordonnées existent
        if (hasCoords) {
            addMarker(lat, lng);
        }

        // Clic sur la carte pour positionner le marqueur
        map.on('click', function(e) {
            const lat = e.latlng.lat.toFixed(6);
            const lng = e.latlng.lng.toFixed(6);

            $('#mapped_places_latitude').val(lat);
            $('#mapped_places_longitude').val(lng);

            addMarker(e.latlng.lat, e.latlng.lng);

            // Reverse geocoding
            reverseGeocode(lat, lng);
        });
    }

    /**
     * Ajouter/déplacer le marqueur
     */
    function addMarker(lat, lng) {
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], {
                draggable: true,
                icon: L.divIcon({
                    html: '<div class="mapl-admin-marker"></div>',
                    className: '',
                    iconSize: [30, 30],
                    iconAnchor: [15, 30],
                }),
            }).addTo(map);

            // Drag du marqueur
            marker.on('dragend', function(e) {
                const pos = e.target.getLatLng();
                $('#mapped_places_latitude').val(pos.lat.toFixed(6));
                $('#mapped_places_longitude').val(pos.lng.toFixed(6));
                reverseGeocode(pos.lat, pos.lng);
            });
        }
    }

    /**
     * Binding des événements
     */
    function bindEvents() {
        // Bouton géocoder
        $('#mapped_places_geocode_btn').on('click', function(e) {
            e.preventDefault();
            geocodeAddress();
        });

        // Mise à jour de la carte quand les coordonnées changent manuellement
        $('#mapped_places_latitude, #mapped_places_longitude').on('change', function() {
            const lat = parseFloat($('#mapped_places_latitude').val());
            const lng = parseFloat($('#mapped_places_longitude').val());

            if (lat && lng && map) {
                map.setView([lat, lng], 15);
                addMarker(lat, lng);
            }
        });
    }

    /**
     * Géocoder l'adresse
     */
    function geocodeAddress() {
        const adresse = $('#mapped_places_address').val();
        const codePostal = $('#mapped_places_postal_code').val();
        const ville = $('#mapped_places_city').val();

        const fullAddress = [adresse, codePostal, ville].filter(Boolean).join(' ');

        if (!fullAddress) {
            showStatus(t('enterAddress'), 'error');
            return;
        }

        showStatus(t('searching'), '');

        $.ajax({
            url: mappedPlacesAdmin.apiGouv,
            data: {
                q: fullAddress,
                limit: 1,
            },
            success: function(response) {
                if (response.features && response.features.length > 0) {
                    const feature = response.features[0];
                    const coords = feature.geometry.coordinates;
                    const props = feature.properties;

                    // Mettre à jour les champs
                    $('#mapped_places_latitude').val(coords[1].toFixed(6));
                    $('#mapped_places_longitude').val(coords[0].toFixed(6));

                    // Mettre à jour les champs d'adresse si vides
                    if (!$('#mapped_places_postal_code').val() && props.postcode) {
                        $('#mapped_places_postal_code').val(props.postcode);
                    }
                    if (!$('#mapped_places_city').val() && props.city) {
                        $('#mapped_places_city').val(props.city);
                    }

                    // Mettre à jour la carte
                    if (map) {
                        map.setView([coords[1], coords[0]], 16);
                        addMarker(coords[1], coords[0]);
                    }

                    showStatus(t('addressFound').replace('%s', props.label), 'success');
                } else {
                    showStatus(t('addressNotFound'), 'error');
                }
            },
            error: function() {
                showStatus(t('searchError'), 'error');
            },
        });
    }

    /**
     * Reverse geocoding
     */
    function reverseGeocode(lat, lng) {
        $.ajax({
            url: 'https://api-adresse.data.gouv.fr/reverse/',
            data: {
                lat: lat,
                lon: lng,
            },
            success: function(response) {
                if (response.features && response.features.length > 0) {
                    const props = response.features[0].properties;

                    // Remplir les champs si vides
                    if (!$('#mapped_places_address').val() && props.name) {
                        $('#mapped_places_address').val(props.name);
                    }
                    if (!$('#mapped_places_postal_code').val() && props.postcode) {
                        $('#mapped_places_postal_code').val(props.postcode);
                    }
                    if (!$('#mapped_places_city').val() && props.city) {
                        $('#mapped_places_city').val(props.city);
                    }
                }
            },
        });
    }

    /**
     * Afficher un statut
     */
    function showStatus(message, type) {
        const $status = $('#mapped_places_geocode_status');
        $status.text(message).removeClass('success error');
        if (type) {
            $status.addClass(type);
        }
    }

    /**
     * Personnes du lieu : lignes rôle + nom, recopiées en JSON dans le champ
     * caché à chaque modification. Sans script, le champ caché garde la
     * valeur initiale : rien n'est perdu.
     */
    function initPeople() {
        const $box = $('[data-mapl-people]');
        if (!$box.length) {
            return;
        }
        const $input = $box.find('#mapped_places_people');
        const $rows = $box.find('#mapped_places_people_rows');
        const template = document.getElementById('mapped_places_person_template');

        function sync() {
            const people = [];
            $rows.find('.mapl-person-row').each(function () {
                const role = $(this).find('.mapl-person-role').val().trim();
                const name = $(this).find('.mapl-person-name').val().trim();
                if (name) {
                    people.push({ role: role, name: name });
                }
            });
            $input.val(JSON.stringify(people));
        }

        $box.on('input change', '.mapl-person-role, .mapl-person-name', sync);
        $box.on('click', '.mapl-person-remove', function () {
            $(this).closest('.mapl-person-row').remove();
            sync();
        });
        $box.on('click', '#mapped_places_people_add', function () {
            const row = template && template.content ? template.content.querySelector('.mapl-person-row') : null;
            if (!row) {
                return;
            }
            const node = row.cloneNode(true);
            $rows.append(node);
            $(node).find('.mapl-person-role').trigger('focus');
        });
        if ($.fn.sortable) {
            $rows.sortable({ handle: '.mapl-person-handle', axis: 'y', update: sync });
        }
    }

    // Initialiser au chargement
    $(document).ready(init);

})(jQuery);
