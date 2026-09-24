<?php
/**
 * Import des établissements depuis CSV
 */

namespace Geofolio\Import;

use Geofolio\Domain\Schema;

use Geofolio\Domain\FieldRegistry;
use Geofolio\Domain\Icons;
use Geofolio\Domain\Taxonomies;
use Geofolio\Migration\TermTools;

if (!defined('ABSPATH')) {
    exit;
}

class Importer {

    /** Taille maximale du fichier CSV téléversé, en octets (2 Mo). */
    const MAX_FILE_SIZE = 2097152;

    /** Seule extension acceptée pour le fichier téléversé. */
    const ALLOWED_EXTENSION = 'csv';

    /** Caractère d'échappement de fgetcsv() : aucun, conforme à la RFC 4180. */
    const CSV_ESCAPE = '';

    /** Page d'import, cible des redirections. */
    const IMPORT_PAGE = 'edit.php?post_type=etablissement&page=geofolio-import';

    /** Géocodeur par défaut : Base Adresse Nationale (France). */
    const DEFAULT_GEOCODER_URL = 'https://api-adresse.data.gouv.fr/search/';

    /** Jeu de données livré avec le cœur, relatif au dossier du plugin. */
    const EXAMPLE_DATASET = 'data/sample/places.csv';

    /** Dossier des photos d'un jeu de données, à côté de son CSV. */
    const PHOTOS_DIR = 'photos';

    /** Champ à valeurs multiples (« a; b | c ») => taxonomie. */
    const LIST_TAXONOMIES = array(
        'type'          => Schema::TAX_TYPE,
        'service'       => Schema::TAX_SERVICE,
        'accessibility' => Schema::TAX_ACCESSIBILITY,
    );

    /** Séparateurs d'une liste de valeurs dans une cellule. */
    const LIST_SEPARATORS = '/[;|]/';

    /** Champs d'une ligne CSV enregistrés en metas du lieu. */
    const META_FIELDS = array(
        'address', 'postal_code', 'city', 'latitude', 'longitude',
        'phone', 'email', 'website', 'manager', 'opening_hours',
    );

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_import_page'));
        add_action('admin_init', array($this, 'handle_import'));
    }

    /**
     * Ajouter la page d'import
     */
    public function add_import_page() {
        add_submenu_page(
            'edit.php?post_type=etablissement',
            __('Import places', 'geofolio'),
            __('Import CSV', 'geofolio'),
            'manage_options',
            'geofolio-import',
            array($this, 'render_import_page')
        );
    }

    /**
     * Afficher la page d'import
     */
    public function render_import_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Import places', 'geofolio'); ?></h1>

            <?php if (isset($_GET['imported'])) : ?>
                <div class="notice notice-success">
                    <p><?php printf(/* translators: %d: number of imported places */ __('%d place(s) imported successfully!', 'geofolio'), intval($_GET['imported'])); ?></p>
                    <?php if (!empty($_GET['skipped'])) : ?>
                        <p><?php printf(/* translators: %d: number of skipped lines */ __('%d malformed line(s) skipped: column count differs from the header.', 'geofolio'), intval($_GET['skipped'])); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])) : ?>
                <div class="notice notice-error">
                    <p><?php echo esc_html(wp_unslash($_GET['error'])); ?></p>
                </div>
            <?php endif; ?>

            <div class="card" style="max-width: 600px; padding: 20px;">
                <h2><?php _e('Import from a CSV file', 'geofolio'); ?></h2>
                <p><?php _e('The first line of the file names the columns. Recognised columns (in English or French, accents and case ignored):', 'geofolio'); ?></p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><strong>Nom</strong> / <strong>Name</strong> - <?php _e('name of the place (required)', 'geofolio'); ?></li>
                    <li><strong>Type</strong>, <strong>Service</strong>, <strong>Entité</strong> / <strong>Entity</strong></li>
                    <li><strong>Description</strong>, <strong>Capacité</strong> / <strong>Capacity</strong></li>
                    <li><strong>Adresse</strong> / <strong>Address</strong>, <strong>Code postal</strong>, <strong>Ville</strong> / <strong>City</strong>, <strong>Dép</strong> / <strong>Department</strong></li>
                    <li><strong>Latitude</strong>, <strong>Longitude</strong> - <?php _e('without them, the address is geocoded', 'geofolio'); ?></li>
                    <li><strong>Téléphone</strong> / <strong>Phone</strong>, <strong>Email</strong>, <strong>Site web</strong> / <strong>Website</strong>, <strong>Horaires</strong></li>
                </ul>

                <form method="post" enctype="multipart/form-data" style="margin-top: 20px;">
                    <?php wp_nonce_field('geofolio_import_csv', 'geofolio_import_nonce'); ?>

                    <p>
                        <label for="csv_file"><strong><?php _e('CSV file:', 'geofolio'); ?></strong></label><br>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" required />
                    </p>

                    <p>
                        <label>
                            <input type="checkbox" name="skip_existing" value="1" checked />
                            <?php _e('Skip places that already exist (same name)', 'geofolio'); ?>
                        </label>
                    </p>

                    <p>
                        <label>
                            <input type="checkbox" name="geocode" value="1" checked />
                            <?php _e('Geocode addresses automatically', 'geofolio'); ?>
                        </label>
                    </p>

                    <p>
                        <input type="submit" name="geofolio_import" class="button button-primary" value="<?php esc_attr_e('Import', 'geofolio'); ?>" />
                    </p>
                </form>
            </div>

            <div class="card" style="max-width: 600px; padding: 20px; margin-top: 20px;">
                <h2><?php _e('Default dataset', 'geofolio'); ?></h2>
                <p><?php _e('Import the preset places (a sample dataset, or the one provided by a preset).', 'geofolio'); ?></p>

                <form method="post">
                    <?php wp_nonce_field('geofolio_import_default', 'geofolio_import_default_nonce'); ?>
                    <input type="submit" name="geofolio_import_default" class="button button-secondary" value="<?php esc_attr_e('Import the preset places', 'geofolio'); ?>" />
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Gérer l'import. Accroché à admin_init : toute requête admin passe ici,
     * d'où la vérification de capacité avant celle du nonce.
     */
    public function handle_import() {
        if (isset($_POST['geofolio_import']) && isset($_FILES['csv_file'])) {
            $this->handle_csv_upload();
            return;
        }

        if (isset($_POST['geofolio_import_default'])) {
            $this->handle_default_import();
        }
    }

    /**
     * Vérifier capacité et nonce d'un formulaire d'import.
     *
     * @param string $field  Champ du nonce.
     * @param string $action Action du nonce.
     * @return bool
     */
    private function is_authorized($field, $action) {
        if (!current_user_can('manage_options')) {
            return false;
        }
        return isset($_POST[$field])
            && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[$field])), $action);
    }

    /**
     * Import d'un CSV téléversé.
     */
    private function handle_csv_upload() {
        if (!$this->is_authorized('geofolio_import_nonce', 'geofolio_import_csv')) {
            return;
        }

        $file  = $_FILES['csv_file'];
        $error = self::upload_error($file);
        if ($error !== '') {
            $this->redirect(array('error' => $error));
        }

        $result = $this->import_csv($file['tmp_name'], isset($_POST['skip_existing']), isset($_POST['geocode']));
        $this->redirect($result);
    }

    /**
     * Import du jeu de données livré avec le plugin.
     */
    private function handle_default_import() {
        if (!$this->is_authorized('geofolio_import_default_nonce', 'geofolio_import_default')) {
            return;
        }

        $csv_file = self::default_dataset();
        if (is_readable($csv_file)) {
            $this->redirect($this->import_csv($csv_file, true, true, dirname($csv_file) . '/' . self::PHOTOS_DIR));
        }
        $this->redirect(array('error' => __('Default dataset not found.', 'geofolio')));
    }

    /**
     * Fichier importé par le bouton « établissements pré-configurés ».
     *
     * @return string
     */
    public static function default_dataset() {
        return (string) apply_filters('geofolio_default_dataset', GEOFOLIO_PLUGIN_DIR . self::EXAMPLE_DATASET);
    }

    /**
     * Rediriger vers la page d'import avec des paramètres d'URL, puis sortir.
     *
     * @param array $args Paramètres (imported, skipped, error).
     */
    private function redirect(array $args) {
        wp_safe_redirect(add_query_arg(array_map('rawurlencode', array_map('strval', $args)), admin_url(self::IMPORT_PAGE)));
        exit;
    }

    /**
     * Motif de refus d'un fichier téléversé, ou chaîne vide s'il est acceptable.
     *
     * @param array $file Entrée de $_FILES.
     * @return string
     */
    public static function upload_error($file) {
        if (!is_array($file) || !isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return __('Error while uploading the file.', 'geofolio');
        }
        if (!isset($file['size']) || (int) $file['size'] > self::MAX_FILE_SIZE) {
            return sprintf(
                /* translators: %d: maximum file size in megabytes */ __('File too large: %d MB maximum.', 'geofolio'),
                self::MAX_FILE_SIZE / 1048576
            );
        }
        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($extension !== self::ALLOWED_EXTENSION) {
            return __('Only .csv files are accepted.', 'geofolio');
        }
        return '';
    }

    /**
     * Lire un fichier CSV. L'en-tête est normalisé (minuscules, apostrophes
     * typographiques ramenées à '), et toute ligne dont le nombre de colonnes
     * diffère de l'en-tête est ignorée plutôt que de faire échouer l'import.
     *
     * @param string $file_path Chemin du fichier.
     * @return array{rows: array[], skipped: int}
     */
    public static function parse_csv($file_path) {
        $empty = array('rows' => array(), 'skipped' => 0);

        $handle = is_readable($file_path) ? fopen($file_path, 'r') : false;
        if (!$handle) {
            return $empty;
        }

        $header = fgetcsv($handle, 0, ',', '"', self::CSV_ESCAPE);
        if (!is_array($header) || $header === array(null)) {
            fclose($handle);
            return $empty;
        }
        $header = array_map(array(__CLASS__, 'normalize_header'), $header);

        $rows    = array();
        $skipped = 0;
        while (($row = fgetcsv($handle, 0, ',', '"', self::CSV_ESCAPE)) !== false) {
            if ($row === array(null)) {
                continue; // Ligne vide.
            }
            if (count($row) !== count($header)) {
                $skipped++;
                continue;
            }
            $rows[] = array_combine($header, $row);
        }
        fclose($handle);

        return array('rows' => $rows, 'skipped' => $skipped);
    }

    /**
     * Normaliser un nom de colonne.
     *
     * @param string $col
     * @return string
     */
    private static function normalize_header($col) {
        $col = strtolower(trim((string) $col));
        return str_replace(array("\xe2\x80\x99", "\xe2\x80\x98"), "'", $col);
    }

    /**
     * Importer un fichier CSV.
     *
     * @param string      $file_path
     * @param bool        $skip_existing Ignorer les lieux au nom déjà présent.
     * @param bool        $geocode       Géocoder les lignes sans coordonnées.
     * @param string|null $media_dir     Dossier des photos citées par le CSV
     *                                   (jeux de confiance seulement).
     * @return array{imported: int, skipped: int}
     */
    public function import_csv($file_path, $skip_existing = true, $geocode = true, $media_dir = null) {
        $parsed = self::parse_csv($file_path);
        $count  = 0;
        $media  = new MediaImporter();

        foreach ($parsed['rows'] as $row) {
            $fields = CsvMapping::map_row($row);

            // Nom obligatoire
            if (!isset($fields['name'])) {
                continue;
            }

            if ($skip_existing && self::find_by_title($fields['name'])) {
                continue;
            }

            $geocoded = $geocode && self::needs_geocoding($fields);
            $post_id = $this->create_etablissement($fields, $geocoded);
            if ($post_id) {
                $media->attach($post_id, $fields, $media_dir);
                $count++;
            }

            // Pause pour éviter de surcharger l'API de géocodage
            if ($geocoded) {
                usleep(200000); // 200ms
            }
        }

        return array('imported' => $count, 'skipped' => $parsed['skipped']);
    }

    /**
     * Identifiant d'un établissement portant ce titre, 0 sinon.
     * get_page_by_title() est déprécié depuis WP 6.2 — on passe par WP_Query.
     *
     * @param string $title
     * @return int
     */
    private static function find_by_title($title) {
        $query = new \WP_Query(array(
            'post_type'      => Schema::POST_TYPE,
            'post_status'    => 'any',
            'title'          => $title,
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        ));
        return $query->posts ? (int) $query->posts[0] : 0;
    }

    /**
     * Extrait de la fiche en texte brut : the_excerpt() l'affiche sans
     * l'échapper, aucune balise du CSV ne doit donc y entrer.
     *
     * @param string $mission
     * @param string $capacite
     * @return string
     */
    public static function build_excerpt($mission, $capacite) {
        return wp_strip_all_tags($mission !== '' ? $mission : $capacite);
    }

    /**
     * Libellés placés devant la description et la capacité dans le contenu
     * de la fiche (filtre geofolio_import_content_labels ; '' : pas de
     * libellé).
     *
     * @return array{description: string, capacity: string}
     */
    public static function content_labels() {
        $labels   = array('description' => '', 'capacity' => __('Capacity:', 'geofolio'));
        $filtered = apply_filters('geofolio_import_content_labels', $labels);
        return is_array($filtered) ? array_map('strval', array_merge($labels, array_intersect_key($filtered, $labels))) : $labels;
    }

    /**
     * Contenu HTML de la fiche. Les valeurs du CSV sont du texte : elles
     * sont échappées pour qu'aucune balise n'atteigne post_content.
     *
     * @param string $mission
     * @param string $capacite
     * @return string
     */
    public static function build_content($mission, $capacite) {
        $labels  = self::content_labels();
        $content = '';
        foreach (array('description' => $mission, 'capacity' => $capacite) as $key => $value) {
            if ($value === '') {
                continue;
            }
            $label    = $labels[$key] !== '' ? '<strong>' . esc_html($labels[$key]) . '</strong> ' : '';
            $content .= '<p>' . $label . esc_html($value) . "</p>\n";
        }
        return $content;
    }

    /**
     * Créer un établissement à partir des champs d'une ligne.
     *
     * @param array<string, string> $fields  Champs (CsvMapping::map_row).
     * @param bool                  $geocode Géocoder l'adresse.
     * @return int|false ID créé.
     */
    private function create_etablissement(array $fields, $geocode) {
        $description = $fields['description'] ?? '';
        $capacity    = $fields['capacity'] ?? '';

        $post_id = wp_insert_post(array(
            'post_title'   => $fields['name'],
            'post_content' => self::build_content($description, $capacity),
            // L'audience (« Public : » dans la fiche) prime ; sinon la
            // description, ou à défaut la capacité.
            'post_excerpt' => isset($fields['audience']) ? wp_strip_all_tags($fields['audience']) : self::build_excerpt($description, $capacity),
            'post_status'  => 'publish',
            'post_type'    => Schema::POST_TYPE,
        ));
        if (!$post_id || is_wp_error($post_id)) {
            return false;
        }

        foreach (self::LIST_TAXONOMIES as $field => $taxonomy) {
            $terms = self::split_list($fields[$field] ?? '');
            if ($terms) {
                $term_ids = wp_set_object_terms($post_id, $terms, $taxonomy);
                if ($field === 'type' && is_array($term_ids)) {
                    self::seed_type_icons($term_ids, $fields['type_icon'] ?? '');
                }
            }
        }

        $region = self::resolve_region($fields);
        if ($region !== '') {
            wp_set_object_terms($post_id, $region, Schema::TAX_REGION);
        }

        // Entité : porte la couleur du marqueur sur la carte.
        $entity = self::resolve_entity($fields);
        if ($entity) {
            $this->assign_entite($post_id, $entity['slug'], $entity['name'], self::entity_color($fields));
        }

        $meta = self::meta_from_fields($fields);
        if ($geocode) {
            $meta = self::merge_geocoded($meta, $this->geocode_address($fields['address']));
        }
        foreach ($meta as $field => $value) {
            update_post_meta($post_id, FieldRegistry::meta_key($field), $value);
        }

        return $post_id;
    }

    /**
     * Valeurs d'une cellule à valeurs multiples, séparées par ; ou |.
     *
     * @param string $value
     * @return string[]
     */
    public static function split_list($value) {
        return array_values(array_filter(array_map('trim', preg_split(self::LIST_SEPARATORS, (string) $value)), 'strlen'));
    }

    /**
     * Couleur d'entité de la ligne, si c'est une couleur hexadécimale.
     *
     * @param array<string, string> $fields
     * @return string '' si absente ou invalide.
     */
    public static function entity_color(array $fields) {
        $color = trim((string) ($fields['entity_color'] ?? ''));
        return preg_match('/^#([0-9a-fA-F]{3}){1,2}$/', $color) ? $color : '';
    }

    /**
     * Donner l'icône de la ligne aux types qui n'en ont pas encore.
     *
     * @param int[]  $term_ids
     * @param string $icon
     */
    private static function seed_type_icons(array $term_ids, $icon) {
        if (!Icons::is_valid($icon)) {
            return;
        }
        foreach ($term_ids as $term_id) {
            if (!get_term_meta((int) $term_id, Icons::TERM_META, true)) {
                Taxonomies::store_type_icon((int) $term_id, $icon);
            }
        }
    }

    /**
     * Faut-il géocoder la ligne : une adresse, mais pas de coordonnées ?
     *
     * @param array<string, string> $fields
     * @return bool
     */
    public static function needs_geocoding(array $fields) {
        $meta = self::meta_from_fields($fields);
        return isset($fields['address'])
            && (!isset($meta['latitude']) || !isset($meta['longitude']));
    }

    /**
     * Metas du lieu tirées des champs (champ => valeur), nettoyées par le
     * registre ; les valeurs vides après nettoyage sont omises.
     *
     * @param array<string, string> $fields
     * @return array<string, string>
     */
    public static function meta_from_fields(array $fields) {
        $meta = array();
        foreach (self::META_FIELDS as $field) {
            if (!isset($fields[$field])) {
                continue;
            }
            $value = FieldRegistry::sanitize($field, $fields[$field]);
            if ($value !== '') {
                $meta[$field] = $value;
            }
        }
        return $meta;
    }

    /**
     * Compléter les metas avec un résultat de géocodage : coordonnées, et
     * adresse, ville et code postal normalisés par le géocodeur.
     *
     * @param array<string, string> $meta
     * @param array|null            $coords Retour de geocode_address().
     * @return array<string, string>
     */
    private static function merge_geocoded(array $meta, $coords) {
        if (!$coords) {
            return $meta;
        }
        $geocoded = array_filter(array(
            'latitude'    => (string) $coords['lat'],
            'longitude'   => (string) $coords['lng'],
            'city'        => (string) ($coords['city'] ?? ''),
            'postal_code' => (string) ($coords['postcode'] ?? ''),
            'address'     => (string) ($coords['name'] ?? ''),
        ), 'strlen');
        return array_merge($meta, $geocoded);
    }

    /**
     * Entité d'une ligne : colonne « entité » si présente, sinon ce que
     * fournit le filtre geofolio_import_entity (un préréglage y déduit
     * l'entité du type ou du nom). Sans rien : aucune entité.
     *
     * @param array<string, string> $fields
     * @return array{slug: string, name: string}|null
     */
    public static function resolve_entity(array $fields) {
        $default = isset($fields['entity']) ? array('slug' => '', 'name' => $fields['entity']) : null;
        $entity  = apply_filters('geofolio_import_entity', $default, $fields);

        if (is_string($entity) && trim($entity) !== '') {
            return array('slug' => '', 'name' => trim($entity));
        }
        if (is_array($entity) && isset($entity['name']) && trim((string) $entity['name']) !== '') {
            return array(
                'slug' => isset($entity['slug']) ? (string) $entity['slug'] : '',
                'name' => trim((string) $entity['name']),
            );
        }
        return null;
    }

    /**
     * Région d'une ligne, déduite par le filtre geofolio_import_region
     * (aucune par défaut).
     *
     * @param array<string, string> $fields
     * @return string
     */
    public static function resolve_region(array $fields) {
        $region = apply_filters('geofolio_import_region', $fields['region'] ?? '', $fields['department'] ?? '', $fields);
        return is_string($region) ? trim($region) : '';
    }

    /**
     * Affecte une entité à un établissement, en garantissant l'existence du
     * terme (créé au besoin, sans couleur : elle se choisit dans l'admin).
     *
     * Idempotent : remplace l'entité du post par le terme cible. N'est appelé
     * qu'à la création d'un établissement (les imports ignorent par défaut les
     * établissements existants), donc n'écrase pas une affectation manuelle.
     *
     * @param int    $post_id ID de l'établissement.
     * @param string $slug    Slug voulu ('' : dérivé du nom).
     * @param string $name    Nom de l'entité.
     * @param string $color   Couleur à semer si l'entité n'en a pas ('' : aucune).
     * @return void
     */
    private function assign_entite($post_id, $slug, $name, $color = '') {
        $slug = $slug !== '' ? $slug : sanitize_title($name);
        $term = get_term_by('slug', $slug, Schema::TAX_ENTITY);

        if ($term && !is_wp_error($term)) {
            $term_id = (int) $term->term_id;
        } else {
            $created = wp_insert_term($name, Schema::TAX_ENTITY, array('slug' => $slug));
            if (!is_wp_error($created)) {
                $term_id = (int) $created['term_id'];
            } else {
                // Collision possible (nom déjà pris) : retomber sur le terme existant.
                $fallback = get_term_by('name', $name, Schema::TAX_ENTITY);
                $term_id  = ($fallback && !is_wp_error($fallback)) ? (int) $fallback->term_id : 0;
            }
        }

        if ($term_id) {
            wp_set_object_terms($post_id, array($term_id), Schema::TAX_ENTITY, false);
            if ($color !== '') {
                TermTools::seed_term_color($term_id, $color);
            }
        }
    }

    /**
     * URL de géocodage d'une adresse. Le géocodeur par défaut couvre la
     * France ; le filtre geofolio_geocoder_url en désigne un autre
     * répondant en GeoJSON (ex. Nominatim avec ?format=geojson).
     *
     * @param string $address
     * @return string
     */
    public static function geocoder_url($address) {
        $base = (string) apply_filters('geofolio_geocoder_url', self::DEFAULT_GEOCODER_URL);
        return $base . (strpos($base, '?') === false ? '?' : '&') . http_build_query(array(
            'q'     => $address,
            'limit' => 1,
        ));
    }

    /**
     * Géocoder une adresse (réponse GeoJSON : première entité trouvée).
     *
     * @param string $address
     * @return array|null
     */
    private function geocode_address($address) {
        $response = wp_remote_get(self::geocoder_url($address), array('timeout' => 10));
        if (is_wp_error($response)) {
            return null;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data['features'][0]['geometry']['coordinates'])) {
            return null;
        }

        $feature = $data['features'][0];
        $coords  = $feature['geometry']['coordinates'];
        $props   = isset($feature['properties']) && is_array($feature['properties']) ? $feature['properties'] : array();

        return array(
            'lat'      => $coords[1],
            'lng'      => $coords[0],
            'city'     => $props['city'] ?? '',
            'postcode' => $props['postcode'] ?? '',
            'name'     => $props['name'] ?? '',
        );
    }
}
