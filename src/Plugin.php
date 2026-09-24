<?php
/**
 * Classe principale : charge les composants et les assets de la carte.
 */

namespace Geofolio;

use Geofolio\Admin\Duplicate;
use Geofolio\Blocks\MapBlock;
use Geofolio\Admin\MetaBoxes;
use Geofolio\Domain\FieldRegistry;
use Geofolio\Domain\Schema;
use Geofolio\Admin\SettingsPage;
use Geofolio\Domain\PlacePostType;
use Geofolio\Domain\Taxonomies;
use Geofolio\Elementor\Integration;
use Geofolio\Import\Importer;
use Geofolio\Map\Defaults;
use Geofolio\Map\Shortcode;
use Geofolio\Migration\Runner;
use Geofolio\Rest\ResponseCache;
use Geofolio\Rest\PlacesController;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe principale du plugin
 */
final class Plugin {

    /**
     * Instance unique (Singleton)
     */
    private static $instance = null;

    /**
     * Récupérer l'instance unique
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructeur
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialiser les hooks
     */
    private function init_hooks() {
        // Activation/Désactivation
        register_activation_hook(GEOFOLIO_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(GEOFOLIO_PLUGIN_FILE, array($this, 'deactivate'));

        // Initialisation
        add_action('init', array($this, 'load_textdomain'));
        add_action('init', array(__CLASS__, 'maybe_flush_rewrite_rules'), 99);
        add_action('init', array($this, 'register_place_meta'), 12);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // Initialiser les composants
        SettingsPage::get_instance();
        PlacePostType::get_instance();
        Taxonomies::get_instance();
        MetaBoxes::get_instance();
        Duplicate::get_instance();
        PlacesController::get_instance();
        ResponseCache::register();
        MapBlock::register();
        Shortcode::get_instance();
        Importer::get_instance();
        Runner::get_instance();

        // Widget Elementor, si Elementor est actif.
        add_action('plugins_loaded', array(Integration::class, 'maybe_boot'));
    }

    /**
     * Expose tous les champs meta de l'établissement via l'API REST.
     *
     * Enregistré ici (plutôt que dans la classe CPT) pour garantir que les
     * mises à jour de plugin invalident bien le cache opcode sur les hôtes
     * où celui-ci n'invalide pas les sous-fichiers (cas observé chez certains hébergeurs).
     */
    public function register_place_meta() {
        // Même sanitisation que les meta boxes : sans elle, l'éditeur de
        // blocs pouvait enregistrer « javascript: » dans le site web.
        foreach (FieldRegistry::sanitizers() as $key => $sanitize) {
            register_post_meta(Schema::POST_TYPE, $key, array(
                'show_in_rest'      => true,
                'single'            => true,
                'type'              => 'string',
                // WordPress passe aussi la clé et le type d'objet : le
                // callback ne reçoit que la valeur (esc_url_raw prendrait
                // la clé pour une liste de protocoles).
                'sanitize_callback' => static function ($value) use ($sanitize) {
                    return call_user_func($sanitize, $value);
                },
                'auth_callback'     => '__return_true',
            ));
        }
    }

    /**
     * Demander la régénération des règles de réécriture à la prochaine
     * requête : utile quand un autre plugin, désactivé pendant la requête
     * courante, a encore enregistré ses propres règles.
     */
    public static function request_rewrite_flush() {
        update_option(self::FLUSH_OPTION, 1, false);
    }

    /**
     * Régénérer les règles de réécriture si une migration l'a demandé.
     */
    public static function maybe_flush_rewrite_rules() {
        if (get_option(self::FLUSH_OPTION)) {
            delete_option(self::FLUSH_OPTION);
            flush_rewrite_rules();
        }
    }

    /**
     * Activation du plugin
     */
    public function activate() {
        // Créer les CPT et taxonomies
        PlacePostType::get_instance()->register_post_type();
        Taxonomies::get_instance()->register_taxonomies();

        // Flush les règles de réécriture
        flush_rewrite_rules();
    }

    /**
     * Désactivation du plugin
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Charger les traductions
     */
    public function load_textdomain() {
        // phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- charge languages/ du plugin tant que le paquet de langue wordpress.org n'existe pas (installation depuis GitHub).
        load_plugin_textdomain(
            'geofolio',
            false,
            dirname(GEOFOLIO_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Feuilles de style embarquées (assets/vendor/, voir VERSIONS.md) :
     * handle => [chemin relatif au plugin, dépendances, version].
     */
    const VENDOR_STYLES = array(
        'leaflet'                       => array('assets/vendor/leaflet-1.9.4/leaflet.css', array(), '1.9.4'),
        'leaflet-markercluster'         => array('assets/vendor/leaflet.markercluster-1.4.1/MarkerCluster.css', array('leaflet'), '1.4.1'),
        'leaflet-markercluster-default' => array('assets/vendor/leaflet.markercluster-1.4.1/MarkerCluster.Default.css', array('leaflet-markercluster'), '1.4.1'),
        // MapLibre GL : fonds vectoriels, ex. Plan IGN « épure » en français.
        'maplibre-gl'                   => array('assets/vendor/maplibre-gl-3.6.2/maplibre-gl.css', array(), '3.6.2'),
    );

    /**
     * Scripts embarqués (assets/vendor/, voir VERSIONS.md) :
     * handle => [chemin relatif au plugin, dépendances, version].
     */
    const VENDOR_SCRIPTS = array(
        'leaflet'               => array('assets/vendor/leaflet-1.9.4/leaflet.js', array(), '1.9.4'),
        'leaflet-markercluster' => array('assets/vendor/leaflet.markercluster-1.4.1/leaflet.markercluster.js', array('leaflet'), '1.4.1'),
        'maplibre-gl'           => array('assets/vendor/maplibre-gl-3.6.2/maplibre-gl.js', array(), '3.6.2'),
        // Pont Leaflet ↔ MapLibre (rendu des tuiles vectorielles IGN).
        'maplibre-gl-leaflet'   => array('assets/vendor/maplibre-gl-leaflet-0.0.22/leaflet-maplibre-gl.js', array('leaflet', 'maplibre-gl'), '0.0.22'),
    );

    /** Feuilles de style à charger sur une page avec carte. */
    const MAP_STYLE_HANDLES = array('leaflet', 'leaflet-markercluster', 'leaflet-markercluster-default', 'maplibre-gl', 'geofolio');

    /** Option signalant des règles de réécriture à régénérer. */
    const FLUSH_OPTION = 'geofolio_flush_rewrite_rules';

    /** Scripts à charger sur une page avec carte. */
    const MAP_SCRIPT_HANDLES = array('geofolio');

    /**
     * Shortcodes qui affichent la carte (un compagnon peut y ajouter un
     * ancien nom, par le filtre geofolio_shortcode_tags).
     *
     * @return string[]
     */
    public static function shortcode_tags() {
        return array_values(array_filter((array) apply_filters('geofolio_shortcode_tags', array(Shortcode::TAG)), 'is_string'));
    }

    /**
     * Noms des widgets Elementor qui affichent la carte (filtre
     * geofolio_elementor_widget_names).
     *
     * @return string[]
     */
    public static function elementor_widget_names() {
        return array_values(array_filter((array) apply_filters('geofolio_elementor_widget_names', array(Integration::WIDGET_NAME)), 'is_string'));
    }

    /**
     * Enregistrer les handles d'un tableau VENDOR_*.
     *
     * @param array    $assets   Tableau handle => [chemin, dépendances, version].
     * @param callable $register wp_register_style ou wp_register_script.
     * @param array    $extra    Arguments supplémentaires (pied de page pour les scripts).
     */
    private static function register_vendor_assets(array $assets, $register, array $extra = array()) {
        foreach ($assets as $handle => $asset) {
            list($path, $deps, $version) = $asset;
            call_user_func_array($register, array_merge(
                array($handle, GEOFOLIO_PLUGIN_URL . $path, $deps, $version),
                $extra
            ));
        }
    }

    /**
     * Enregistrer les assets de la carte, sans les charger : seules les
     * pages qui affichent une carte les chargent (voir enqueue_map_assets).
     */
    public function enqueue_frontend_assets() {
        self::register_map_assets();

        // Filet : le shortcode charge ses assets au rendu, mais un cache ou
        // une mise en page qui rend le contenu après wp_head les ferait
        // arriver trop tard pour les feuilles de style.
        if (self::current_page_needs_map()) {
            self::enqueue_map_assets();
        }
    }

    /**
     * Enregistrer les bibliothèques, la feuille et le script de la carte
     * (sans les charger). Aussi appelé pour l'aperçu du bloc dans l'éditeur.
     */
    public static function register_map_assets() {
        if (wp_script_is('geofolio', 'registered')) {
            return;
        }
        self::register_vendor_assets(self::VENDOR_STYLES, 'wp_register_style');
        self::register_vendor_assets(self::VENDOR_SCRIPTS, 'wp_register_script', array(true));

        wp_register_style(
            'geofolio',
            GEOFOLIO_PLUGIN_URL . 'assets/css/geofolio.css',
            array('leaflet', 'leaflet-markercluster'),
            GEOFOLIO_VERSION
        );

        wp_register_script(
            'geofolio',
            GEOFOLIO_PLUGIN_URL . 'assets/js/geofolio.js',
            array('jquery', 'leaflet', 'leaflet-markercluster', 'maplibre-gl', 'maplibre-gl-leaflet'),
            GEOFOLIO_VERSION,
            true
        );
    }

    /**
     * La page courante affiche-t-elle une carte (shortcode, widget
     * Elementor, ou aperçu de l'éditeur Elementor) ?
     *
     * @return bool
     */
    private static function current_page_needs_map() {
        if (self::is_elementor_preview()) {
            return true;
        }
        if (!is_singular()) {
            return false;
        }
        $post = get_post(get_queried_object_id());
        if (!$post) {
            return false;
        }
        foreach (self::shortcode_tags() as $tag) {
            if (has_shortcode($post->post_content, $tag)) {
                return true;
            }
        }
        if (function_exists('has_block') && has_block(MapBlock::NAME, $post)) {
            return true;
        }
        $elementor_data = get_post_meta($post->ID, '_elementor_data', true);
        if (!is_string($elementor_data)) {
            return false;
        }
        foreach (self::elementor_widget_names() as $name) {
            if (strpos($elementor_data, '"widgetType":"' . $name . '"') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Aperçu de l'éditeur Elementor : le widget peut y être ajouté avant
     * tout enregistrement, la meta de la page ne le contient pas encore.
     *
     * @return bool
     */
    private static function is_elementor_preview() {
        return class_exists('\Elementor\Plugin')
            && isset(\Elementor\Plugin::$instance->preview)
            && \Elementor\Plugin::$instance->preview->is_preview_mode();
    }

    /**
     * Charger les assets de la carte et sa configuration JS. Appelé par le
     * shortcode au rendu et par le filet de enqueue_frontend_assets ;
     * les appels suivants sont sans effet.
     */
    public static function enqueue_map_assets() {
        static $done = false;
        if ($done || !wp_script_is('geofolio', 'registered')) {
            return;
        }
        $done = true;

        array_map('wp_enqueue_style', self::MAP_STYLE_HANDLES);
        array_map('wp_enqueue_script', self::MAP_SCRIPT_HANDLES);

        // Point d'accroche des surcouches (charte d'un préréglage…).
        do_action('geofolio_assets_enqueued');

        // Variables JS
        wp_localize_script('geofolio', 'geofolioConfig', array(
            'restUrl' => rest_url('geofolio/v1/'),
            'pluginUrl' => GEOFOLIO_PLUGIN_URL,
            'tiles' => SettingsPage::js_tiles_config(),
            'defaultColor' => Defaults::color(),
            'elementorWidgets' => self::elementor_widget_names(),
            'i18n' => array(
                'noResults'         => __('No place found', 'geofolio'),
                'filterAll'         => __('All types', 'geofolio'),
                'manager'           => __('Manager: ', 'geofolio'),
                'managers'          => __('Managers: ', 'geofolio'),
                'entityHint'        => __('Click an entity to show only that one.', 'geofolio'),
                'entityReset'       => __('Show all', 'geofolio'),
                'geolocError'       => __('Unable to find your location', 'geofolio'),
                'geolocUnavailable' => __('Geolocation is not available', 'geofolio'),
                'loadError'         => __('The places could not be loaded. Please reload the page.', 'geofolio'),
                'enterFullscreen'   => __('Full screen', 'geofolio'),
                'exitFullscreen'    => __('Exit full screen', 'geofolio'),
                'filters'           => __('Filters', 'geofolio'),
                'openFilters'       => __('Open filters', 'geofolio'),
                'close'             => _x('Close', 'close the filters drawer', 'geofolio'),
                'closeFilters'      => __('Close filters', 'geofolio'),
                /* translators: %d: number of places in a map cluster */
                'clusterLabel'      => __('%d places', 'geofolio'),
                'photoPlaceholder'  => __('Photo coming soon', 'geofolio'),
                'audience'          => __('Audience:', 'geofolio'),
                /* translators: 1: photo position, 2: number of photos */
                'slideOf'           => __('%1$d of %2$d', 'geofolio'),
                /* translators: %d: photo position */
                'goToSlide'         => __('Go to photo %d', 'geofolio'),
                'gallery'           => __('Photo gallery', 'geofolio'),
                'carousel'          => _x('carousel', 'ARIA role description', 'geofolio'),
                'previousPhoto'     => __('Previous photo', 'geofolio'),
                'nextPhoto'         => __('Next photo', 'geofolio'),
            ),
        ));
    }

    /**
     * Charger les assets admin
     */
    public function enqueue_admin_assets($hook) {
        global $post_type;

        if ($post_type !== Schema::POST_TYPE) {
            return;
        }

        // Leaflet pour l'admin (sélection coordonnées), embarqué comme en front.
        list($css_path, $css_deps, $css_version) = self::VENDOR_STYLES['leaflet'];
        list($js_path, $js_deps, $js_version)    = self::VENDOR_SCRIPTS['leaflet'];
        wp_enqueue_style('leaflet', GEOFOLIO_PLUGIN_URL . $css_path, $css_deps, $css_version);
        wp_enqueue_script('leaflet', GEOFOLIO_PLUGIN_URL . $js_path, $js_deps, $js_version, true);

        // Médiathèque WordPress (meta box Galerie photos). wp_enqueue_media()
        // est idempotent : l'appeler ici en plus de la classe CPT est sans
        // effet de bord et couvre le cas où ce hook-là ne serait pas passé.
        wp_enqueue_media();

        // Admin CSS/JS
        wp_enqueue_style('geofolio-admin', GEOFOLIO_PLUGIN_URL . 'assets/css/geofolio-admin.css', array(), GEOFOLIO_VERSION);
        wp_enqueue_script('geofolio-admin', GEOFOLIO_PLUGIN_URL . 'assets/js/geofolio-admin.js', array('jquery', 'jquery-ui-sortable', 'leaflet'), GEOFOLIO_VERSION, true);

        $defaults = Defaults::all();
        wp_localize_script('geofolio-admin', 'geofolioAdmin', array(
            'apiGouv' => Importer::DEFAULT_GEOCODER_URL,
            'center'  => array((float) $defaults['center_lat'], (float) $defaults['center_lng']),
            'i18n'    => array(
                'mapUnavailable'  => __('The location map could not be loaded. Coordinates can still be entered by hand.', 'geofolio'),
                'enterAddress'    => __('Please enter an address', 'geofolio'),
                'searching'       => __('Searching...', 'geofolio'),
                /* translators: %s: address found by the geocoder */
                'addressFound'    => __('Address found: %s', 'geofolio'),
                'addressNotFound' => __('Address not found', 'geofolio'),
                'searchError'     => __('Error during the search', 'geofolio'),
            ),
        ));

        // Galerie photos : script SEPARE, sans dependance a Leaflet. Une
        // erreur de la carte ne doit pas desactiver l'ajout de photos.
        wp_enqueue_script(
            'geofolio-gallery',
            GEOFOLIO_PLUGIN_URL . 'assets/js/geofolio-gallery.js',
            array('jquery', 'jquery-ui-sortable'),
            GEOFOLIO_VERSION,
            true
        );

        wp_localize_script('geofolio-gallery', 'geofolioGallery', array(
            'i18n' => array(
                'frameTitle'   => __('Photo gallery', 'geofolio'),
                'frameButton'  => __('Use these photos', 'geofolio'),
                'removeItem'   => __('Remove this photo', 'geofolio'),
                'mediaMissing' => __('The WordPress media library could not be loaded on this page. Reload the page; if the problem persists, temporarily deactivate other plugins to find the conflict.', 'geofolio'),
                'parseError'   => __('The saved photo list was unreadable and has been reset. Select your photos again before saving.', 'geofolio'),
            ),
        ));
    }
}
