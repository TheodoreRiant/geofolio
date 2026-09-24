<?php
/**
 * Widget Elementor pour Geofolio
 */

namespace Geofolio\Elementor;

if (!defined('ABSPATH')) {
    exit;
}

class Integration {

    /**
     * Nom du widget carte. Ici plutôt que dans MapWidget : lire une constante
     * de MapWidget charge la classe, qui hérite de \Elementor\Widget_Base et
     * provoque une erreur fatale sur un site sans Elementor.
     */
    const WIDGET_NAME = 'geofolio_map';

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('elementor/widgets/register', array($this, 'register_widget'));
        add_action('elementor/elements/categories_registered', array($this, 'add_category'));
    }

    /**
     * Ajouter la catégorie Geofolio
     */
    public function add_category($elements_manager) {
        $elements_manager->add_category(
            'geofolio',
            array(
                'title' => __('Geofolio', 'geofolio'),
                'icon'  => 'fa fa-map',
            )
        );
    }

    /**
     * Enregistrer le widget
     */
    public function register_widget($widgets_manager) {
        $widgets_manager->register(new MapWidget());
    }

    /**
     * Démarrer l'intégration si Elementor est chargé.
     */
    public static function maybe_boot() {
        if (did_action('elementor/loaded')) {
            self::get_instance();
        }
    }
}
