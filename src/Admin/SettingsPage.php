<?php
/**
 * Réglages du plugin (Établissements → Réglages carte).
 *
 * Permet de saisir dans l'admin la clé API du fournisseur de tuiles et,
 * optionnellement, d'imposer un fond de carte à toutes les cartes du site
 * (les pages Elementor enregistrent leur propre `tile_style`, qui ne suit
 * donc pas les changements faits dans le code).
 *
 * La clé peut aussi être définie hors base, dans wp-config.php :
 *     define('GEOFOLIO_TILE_API_KEY', 'xxxxxxxx');
 * Dans ce cas la constante l'emporte et le champ passe en lecture seule.
 */

namespace Geofolio\Admin;

use Geofolio\Map\TileProviders;

if (!defined('ABSPATH')) {
    exit;
}

final class SettingsPage {

    const OPTION_NAME  = 'geofolio_settings';
    const OPTION_GROUP = 'geofolio_settings_group';
    const PAGE_SLUG    = 'geofolio-settings';
    const KEY_CONSTANT = 'GEOFOLIO_TILE_API_KEY';

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'render_config_notice'));
    }

    /* ================================================================ */
    /*  LECTURE DES RÉGLAGES                                             */
    /* ================================================================ */

    /**
     * Valeurs par défaut.
     *
     * @return array<string, string>
     */
    public static function defaults() {
        return array(
            'tile_style'              => '',   // '' = chaque page décide
            'api_key'                 => '',
            'custom_tile_url'         => '',
            'custom_tile_attribution' => '',
        );
    }

    /**
     * Réglages enregistrés, fusionnés avec les valeurs par défaut.
     *
     * @return array<string, string>
     */
    public static function get_all() {
        $stored = get_option(self::OPTION_NAME, array());
        if (!is_array($stored)) {
            $stored = array();
        }
        return array_merge(self::defaults(), $stored);
    }

    /**
     * Clé API effective : la constante wp-config.php l'emporte sur la base.
     *
     * @return string
     */
    public static function get_api_key() {
        if (defined(self::KEY_CONSTANT)) {
            $constant = constant(self::KEY_CONSTANT);
            if (is_string($constant) && trim($constant) !== '') {
                return trim($constant);
            }
        }
        $settings = self::get_all();
        return trim((string) $settings['api_key']);
    }

    /**
     * La clé est-elle imposée par une constante (champ en lecture seule) ?
     *
     * @return bool
     */
    public static function is_key_locked_by_constant() {
        return defined(self::KEY_CONSTANT)
            && is_string(constant(self::KEY_CONSTANT))
            && trim(constant(self::KEY_CONSTANT)) !== '';
    }

    /**
     * Fond imposé à tout le site, ou '' si chaque page décide.
     *
     * @return string
     */
    public static function get_forced_tile_style() {
        $settings = self::get_all();
        $style    = (string) $settings['tile_style'];
        return TileProviders::exists($style) ? $style : '';
    }

    /**
     * Réglages du fond « URL personnalisée ».
     *
     * @return array{url:string,attribution:string}
     */
    public static function get_custom_tile() {
        $settings = self::get_all();
        return array(
            'url'         => (string) $settings['custom_tile_url'],
            'attribution' => (string) $settings['custom_tile_attribution'],
        );
    }

    /**
     * Résoudre le fond réellement affiché pour un `tile_style` demandé.
     *
     * @param string $requested Fond demandé par la page (shortcode / Elementor).
     * @return array Définition normalisée (cf. TileProviders::resolve).
     */
    public static function resolve_tile($requested) {
        $forced = self::get_forced_tile_style();
        $id     = ($forced !== '') ? $forced : $requested;

        return TileProviders::resolve(
            $id,
            self::get_api_key(),
            self::get_custom_tile()
        );
    }

    /**
     * Table des fonds de carte destinée au JS du frontend.
     *
     * Les clés API sont déjà injectées côté serveur ; un fond dont la clé
     * manque est marqué `available = false` et le JS bascule sur le fond de
     * repli au lieu d'appeler le fournisseur et d'afficher son erreur.
     *
     * @return array<string, mixed>
     */
    public static function js_tiles_config() {
        $api_key   = self::get_api_key();
        $custom    = self::get_custom_tile();
        $providers = array();

        foreach (TileProviders::all() as $id => $provider) {
            $resolved  = TileProviders::resolve($id, $api_key, $custom);
            $available = ($resolved['fallbackReason'] === '');

            $providers[$id] = array(
                'type'        => $provider['type'],
                'url'         => $available ? $resolved['url'] : '',
                'attribution' => $available ? $resolved['attribution'] : '',
                'subdomains'  => $available ? $resolved['subdomains'] : '',
                'maxZoom'     => TileProviders::MAX_ZOOM,
                'available'   => $available,
            );
        }

        return array(
            'providers' => $providers,
            'forced'    => self::get_forced_tile_style(),
            'fallback'  => TileProviders::FALLBACK_ID,
        );
    }

    /* ================================================================ */
    /*  ENREGISTREMENT                                                   */
    /* ================================================================ */

    /**
     * Ajouter la page de réglages sous le menu Établissements.
     */
    public function register_menu() {
        add_submenu_page(
            'edit.php?post_type=etablissement',
            __('Map configuration', 'geofolio'),
            __('Map settings', 'geofolio'),
            'manage_options',
            self::PAGE_SLUG,
            array($this, 'render_page')
        );
    }

    /**
     * Déclarer l'option et ses champs.
     */
    public function register_settings() {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_NAME,
            array(
                'type'              => 'array',
                'sanitize_callback' => array($this, 'sanitize'),
                'default'           => self::defaults(),
            )
        );
    }

    /**
     * Signaler un probleme et retenir s'il est bloquant.
     *
     * @param array  $blocking Compteur d'erreurs bloquantes, modifie par reference.
     * @param string $code     Code du message.
     * @param string $message  Texte affiche.
     * @param string $type     'error' (bloquant) ou 'warning' (informatif).
     */
    private function flag(array &$blocking, $code, $message, $type = 'error') {
        if ($type === 'error') {
            $blocking[] = $code;
        }
        add_settings_error(self::OPTION_NAME, $code, $message, $type);
    }

    /**
     * Valider et nettoyer les réglages soumis.
     *
     * Retourne un NOUVEAU tableau : l'entrée n'est jamais modifiée en place.
     * Toute valeur invalide est remplacée par la valeur par défaut et signalée
     * à l'utilisateur via add_settings_error().
     *
     * @param mixed $input Données brutes du formulaire.
     * @return array<string, string>
     */
    public function sanitize($input) {
        $clean    = self::defaults();
        $blocking = array();

        if (!is_array($input)) {
            $this->flag(
                $blocking,
                'geofolio_bad_payload',
                __('Unrecognised settings: the default values have been restored.', 'geofolio')
            );
            return $clean;
        }

        // Fond de carte imposé au site ('' = chaque page décide).
        $style = isset($input['tile_style']) ? sanitize_key($input['tile_style']) : '';
        if ($style !== '' && !TileProviders::exists($style)) {
            $this->flag(
                $blocking,
                'geofolio_bad_style',
                __('Unknown basemap: the “each page decides” setting was kept.', 'geofolio')
            );
            $style = '';
        }
        $clean['tile_style'] = $style;

        // Clé API. Quand la constante wp-config.php la définit, le champ est
        // désactivé et n'est donc PAS soumis : on conserve alors la valeur en
        // base au lieu de l'effacer silencieusement.
        if (!isset($input['api_key'])) {
            $stored = self::get_all();
            $clean['api_key'] = (string) $stored['api_key'];
        } else {
            $clean['api_key'] = self::sanitize_api_key($input['api_key']);
        }

        // URL personnalisée.
        $url = isset($input['custom_tile_url']) ? trim((string) $input['custom_tile_url']) : '';
        if ($url !== '' && !TileProviders::is_valid_url_template($url)) {
            $this->flag(
                $blocking,
                'geofolio_bad_custom_url',
                __('Invalid tile URL: it must start with https:// and contain {z}, {x} and {y}. It was not saved.', 'geofolio')
            );
            $url = '';
        }
        $clean['custom_tile_url'] = $url;

        $attribution = isset($input['custom_tile_attribution']) ? (string) $input['custom_tile_attribution'] : '';
        $clean['custom_tile_attribution'] = TileProviders::add_noopener(wp_kses(
            $attribution,
            array(
                'a'    => array('href' => array(), 'target' => array(), 'rel' => array()),
                'span' => array(),
            )
        ));

        // Cohérence : fond personnalisé choisi sans URL fournie.
        if ($clean['tile_style'] === TileProviders::CUSTOM_ID && $clean['custom_tile_url'] === '') {
            $this->flag(
                $blocking,
                'geofolio_custom_without_url',
                __('The “Custom URL” basemap needs a tile URL: the map will use the keyless Positron basemap in the meantime.', 'geofolio'),
                'warning'
            );
        }

        // Cohérence : fond à clé choisi sans clé disponible.
        if (TileProviders::requires_key($clean['tile_style'])
            && $clean['api_key'] === ''
            && !self::is_key_locked_by_constant()) {
            $this->flag(
                $blocking,
                'geofolio_style_without_key',
                __('This basemap requires an API key: the map will use the keyless Positron basemap until the key is set.', 'geofolio'),
                'warning'
            );
        }

        // Confirmation explicite. WordPress ne pose son « Réglages enregistrés »
        // que pour les pages rangees sous le menu Reglages ; sur un sous-menu
        // personnalise il n'apparait pas, et la redirection observee en
        // production ne porte meme pas `settings-updated`. On emprunte donc le
        // canal des messages de reglages, qui lui fonctionne.
        if (empty($blocking)) {
            add_settings_error(
                self::OPTION_NAME,
                'geofolio_saved',
                __('Settings saved.', 'geofolio'),
                'success'
            );
        }

        return $clean;
    }

    /**
     * Nettoyer une clé API sans la mutiler.
     *
     * On n'utilise volontairement PAS sanitize_text_field() : celle-ci
     * supprime les séquences %XX, présentes dans certains jetons. On retire
     * ici le HTML, les caractères de contrôle et les espaces.
     *
     * @param mixed $key Valeur brute du formulaire.
     * @return string
     */
    private static function sanitize_api_key($key) {
        $key = wp_strip_all_tags((string) $key);
        $key = preg_replace('/[\s\x00-\x1F\x7F]+/u', '', $key);
        return is_string($key) ? $key : '';
    }

    /* ================================================================ */
    /*  AFFICHAGE                                                        */
    /* ================================================================ */

    /**
     * Avertir dans l'admin quand le fond configuré n'est pas celui affiché.
     */
    public function render_config_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $forced = self::get_forced_tile_style();
        if ($forced === '') {
            return;
        }

        $resolved = self::resolve_tile($forced);
        if ($resolved['fallbackReason'] === '') {
            return;
        }

        printf(
            '<div class="notice notice-warning"><p><strong>%s</strong> %s <a href="%s">%s</a></p></div>',
            esc_html__('Map:', 'geofolio'),
            esc_html(self::describe_fallback($resolved['fallbackReason'])),
            esc_url(admin_url('edit.php?post_type=etablissement&page=' . self::PAGE_SLUG)),
            esc_html__('Open the map settings', 'geofolio')
        );
    }

    /**
     * Message lisible pour un motif de repli.
     *
     * @param string $reason Motif renvoyé par le résolveur.
     * @return string
     */
    public static function describe_fallback($reason) {
        switch ($reason) {
            case 'missing_key':
                return __('the selected basemap requires an API key that is not set; the keyless Positron basemap is shown instead.', 'geofolio');
            case 'invalid_custom_url':
                return __('the custom tile URL is missing or invalid; the keyless Positron basemap is shown instead.', 'geofolio');
            case 'unknown':
                return __('the requested basemap does not exist; the keyless Positron basemap is shown instead.', 'geofolio');
            default:
                return '';
        }
    }

    /**
     * Rendu de la page de réglages.
     */
    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have the required permissions.', 'geofolio'));
        }

        $settings   = self::get_all();
        $locked     = self::is_key_locked_by_constant();
        $api_key    = self::get_api_key();
        $providers  = TileProviders::all();
        $forced     = self::get_forced_tile_style();
        $resolved   = self::resolve_tile($forced !== '' ? $forced : TileProviders::DEFAULT_ID);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Map configuration', 'geofolio'); ?></h1>

            <?php settings_errors(self::OPTION_NAME); ?>

            <form method="post" action="options.php">
                <?php settings_fields(self::OPTION_GROUP); ?>

                <h2><?php esc_html_e('Basemap', 'geofolio'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="geofolio_tile_style"><?php esc_html_e('Basemap applied to the site', 'geofolio'); ?></label>
                        </th>
                        <td>
                            <select id="geofolio_tile_style"
                                    name="<?php echo esc_attr(self::OPTION_NAME); ?>[tile_style]">
                                <option value=""<?php selected($settings['tile_style'], ''); ?>>
                                    <?php esc_html_e('— Let each page decide (Elementor / shortcode setting) —', 'geofolio'); ?>
                                </option>
                                <?php foreach ($providers as $id => $provider) : ?>
                                    <option value="<?php echo esc_attr($id); ?>"<?php selected($settings['tile_style'], $id); ?>>
                                        <?php echo esc_html($provider['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Each Elementor page stores its own basemap. Choosing a value here forces it on every map of the site, without reopening each page.', 'geofolio'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="geofolio_api_key"><?php esc_html_e('Provider API key', 'geofolio'); ?></label>
                        </th>
                        <td>
                            <input type="text"
                                   id="geofolio_api_key"
                                   class="regular-text code"
                                   name="<?php echo esc_attr(self::OPTION_NAME); ?>[api_key]"
                                   value="<?php echo esc_attr($locked ? '' : $settings['api_key']); ?>"
                                   autocomplete="off"
                                   spellcheck="false"
                                   <?php disabled($locked); ?> />
                            <?php if ($locked) : ?>
                                <p class="description">
                                    <?php
                                    printf(
                                        /* translators: %s: nom de la constante PHP */
                                        esc_html__('The key is set by the %s constant in wp-config.php: it takes precedence and cannot be changed here.', 'geofolio'),
                                        '<code>' . esc_html(self::KEY_CONSTANT) . '</code>'
                                    );
                                    ?>
                                </p>
                            <?php else : ?>
                                <p class="description">
                                    <?php esc_html_e('Only needed for basemaps marked “key required”. The IGN and OpenStreetMap basemaps work without a key. Since 2026, CARTO basemaps (Positron, Voyager, Dark Matter) require a key: without it, their tiles are stamped “API KEY REQUIRED”.', 'geofolio'); ?>
                                </p>
                                <p class="description">
                                    <?php
                                    printf(
                                        /* translators: %s: nom de la constante PHP */
                                        esc_html__('To avoid storing the key in the database, you can also define it in wp-config.php: %s', 'geofolio'),
                                        '<code>define(\'' . esc_html(self::KEY_CONSTANT) . '\', \'votre-cle\');</code>'
                                    );
                                    ?>
                                </p>
                            <?php endif; ?>
                            <p class="description">
                                <strong><?php esc_html_e('Good to know:', 'geofolio'); ?></strong>
                                <?php esc_html_e('a tile key is always visible in the page source, since it is the visitor\'s browser that sends it to the provider. Restrict it to your site\'s domain in your provider account.', 'geofolio'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Custom provider', 'geofolio'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Only fill this in if you chose the “Custom URL” basemap above.', 'geofolio'); ?>
                </p>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="geofolio_custom_tile_url"><?php esc_html_e('Tile URL', 'geofolio'); ?></label>
                        </th>
                        <td>
                            <input type="text"
                                   id="geofolio_custom_tile_url"
                                   class="large-text code"
                                   name="<?php echo esc_attr(self::OPTION_NAME); ?>[custom_tile_url]"
                                   value="<?php echo esc_attr($settings['custom_tile_url']); ?>"
                                   spellcheck="false"
                                   placeholder="https://exemple.fr/tiles/{z}/{x}/{y}.png?key={key}" />
                            <p class="description">
                                <?php esc_html_e('Accepted tokens: {z}, {x}, {y} (required), {s} for subdomains, {r} for @2x tiles, and {key}, replaced by the API key above.', 'geofolio'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="geofolio_custom_tile_attribution"><?php esc_html_e('Attribution', 'geofolio'); ?></label>
                        </th>
                        <td>
                            <input type="text"
                                   id="geofolio_custom_tile_attribution"
                                   class="large-text"
                                   name="<?php echo esc_attr(self::OPTION_NAME); ?>[custom_tile_attribution]"
                                   value="<?php echo esc_attr($settings['custom_tile_attribution']); ?>" />
                            <p class="description">
                                <?php esc_html_e('Legal notice shown at the bottom of the map. <a> links are allowed.', 'geofolio'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <h2><?php esc_html_e('Current state', 'geofolio'); ?></h2>
            <table class="widefat striped" style="max-width:840px">
                <tbody>
                    <tr>
                        <th scope="row" style="width:220px"><?php esc_html_e('API key', 'geofolio'); ?></th>
                        <td>
                            <?php if ($api_key === '') : ?>
                                <?php esc_html_e('no key saved', 'geofolio'); ?>
                            <?php else : ?>
                                <code><?php echo esc_html(self::mask_key($api_key)); ?></code>
                                <?php if ($locked) : ?>
                                    <?php esc_html_e('(set in wp-config.php)', 'geofolio'); ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php
                            echo esc_html(
                                $forced !== ''
                                    ? __('Forced basemap, actually displayed', 'geofolio')
                                    : __('Default basemap for pages that do not set one', 'geofolio')
                            );
                            ?>
                        </th>
                        <td>
                            <code><?php echo esc_html($resolved['id']); ?></code>
                            <?php if ($resolved['fallbackReason'] !== '') : ?>
                                <br /><em><?php echo esc_html(self::describe_fallback($resolved['fallbackReason'])); ?></em>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Masquer une clé pour l'affichage (4 premiers et 4 derniers caractères).
     *
     * @param string $key Clé en clair.
     * @return string
     */
    public static function mask_key($key) {
        $length = strlen($key);
        if ($length <= 8) {
            return str_repeat('•', $length);
        }
        return substr($key, 0, 4) . str_repeat('•', max(4, $length - 8)) . substr($key, -4);
    }
}
