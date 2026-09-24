<?php
/**
 * Bloc Gutenberg « Geofolio Map » : enregistrement, attributs, rendu et
 * aperçu dans l'éditeur.
 *
 * Le rendu passe par Renderer, comme le shortcode et le widget Elementor :
 * mêmes valeurs par défaut, même gabarit, même échappement.
 *
 * @package Geofolio
 */

namespace Geofolio\Blocks;

use Geofolio\Map\Defaults;
use Geofolio\Map\Renderer;
use Geofolio\Map\TileProviders;
use Geofolio\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

class MapBlock {

    /** Nom du bloc. */
    const NAME = 'geofolio/map';

    /**
     * Script requis par l'éditeur du bloc (JSX compilé par @wordpress/scripts),
     * fourni par WordPress depuis la 6.6. Sans lui (6.5), le bloc n'est pas
     * proposé ; shortcode et widget Elementor restent disponibles.
     */
    const REQUIRED_SCRIPT = 'react-jsx-runtime';

    /** Dossier compilé du bloc (block.json, index.js, render.php). */
    const BUILD_DIR = __DIR__ . '/../../blocks/map/build';

    /**
     * Brancher le bloc sur WordPress.
     */
    public static function register() {
        add_action('init', array(__CLASS__, 'register_block'));
        add_action('enqueue_block_assets', array(__CLASS__, 'enqueue_editor_preview_assets'));
    }

    /**
     * Enregistrer le bloc et transmettre à son script d'édition la liste
     * des fonds de carte.
     */
    public static function register_block() {
        if (!function_exists('register_block_type') || !file_exists(self::BUILD_DIR . '/block.json')
            || !wp_script_is(self::REQUIRED_SCRIPT, 'registered')) {
            return;
        }
        register_block_type(self::BUILD_DIR);

        $handle = generate_block_asset_handle(self::NAME, 'editorScript');
        wp_localize_script($handle, 'geofolioBlock', array(
            'tiles' => TileProviders::labels(),
        ));
        wp_set_script_translations($handle, 'geofolio', GEOFOLIO_PLUGIN_DIR . 'languages');
    }

    /**
     * Aperçu réel dans l'éditeur : les bibliothèques de la carte et son
     * script sont chargés dans l'iframe de l'éditeur de blocs.
     */
    public static function enqueue_editor_preview_assets() {
        if (!is_admin()) {
            return;
        }
        Plugin::register_map_assets();
        Plugin::enqueue_map_assets();
    }

    /**
     * Attributs du rendu partagé à partir des attributs du bloc ; les
     * absents gardent les valeurs partagées (Defaults).
     *
     * @param array $attributes Attributs du bloc.
     * @return array<string, string>
     */
    public static function attributes_to_atts(array $attributes) {
        $atts = Defaults::all();
        foreach (array_keys($atts) as $key) {
            if (!array_key_exists($key, $attributes)) {
                continue;
            }
            $value = $attributes[$key];
            if (is_bool($value)) {
                $atts[$key] = $value ? 'true' : 'false';
            } elseif (is_int($value) || is_float($value) || is_string($value)) {
                $atts[$key] = (string) $value;
            }
        }
        return $atts;
    }

    /**
     * HTML du bloc : attributs du conteneur de bloc (alignement, classes)
     * autour de la carte.
     *
     * @param array $attributes Attributs du bloc.
     * @return string
     */
    public static function render(array $attributes) {
        $wrapper = function_exists('get_block_wrapper_attributes') ? get_block_wrapper_attributes() : '';
        return '<div ' . $wrapper . '>' . Renderer::render(self::attributes_to_atts($attributes)) . '</div>';
    }
}
