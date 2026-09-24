<?php
/**
 * Substitut minimal d'Elementor : capture les contrôles déclarés par un
 * widget pour vérifier leurs sélecteurs sans charger Elementor.
 */

namespace Elementor;

class Controls_Manager {
    const TAB_CONTENT = 'content';
    const TAB_STYLE   = 'style';
    const SLIDER      = 'slider';
    const NUMBER      = 'number';
    const SWITCHER    = 'switcher';
    const SELECT      = 'select';
    const TEXT        = 'text';
    const TEXTAREA    = 'textarea';
    const COLOR       = 'color';
    const DIMENSIONS  = 'dimensions';
    const HEADING     = 'heading';
}

abstract class Group_Control_Base {
    public static function get_type() {
        return static::TYPE;
    }
}

class Group_Control_Typography extends Group_Control_Base { const TYPE = 'typography'; }
class Group_Control_Border extends Group_Control_Base { const TYPE = 'border'; }
class Group_Control_Box_Shadow extends Group_Control_Base { const TYPE = 'box-shadow'; }
class Group_Control_Background extends Group_Control_Base { const TYPE = 'background'; }

abstract class Widget_Base {

    /** Contrôles déclarés : id => arguments. */
    public $captured_controls = array();

    /** Réglages renvoyés par get_settings_for_display(). */
    public $test_settings = array();

    public function __construct() {
        $this->register_controls();
    }

    abstract protected function register_controls();

    public function start_controls_section($id, array $args = array()) {}
    public function end_controls_section() {}
    public function start_controls_tabs($id, array $args = array()) {}
    public function end_controls_tabs() {}
    public function start_controls_tab($id, array $args = array()) {}
    public function end_controls_tab() {}

    public function add_control($id, array $args = array()) {
        $this->captured_controls[$id] = $args;
    }

    public function add_responsive_control($id, array $args = array()) {
        $this->captured_controls[$id] = $args + array('responsive' => true);
    }

    public function add_group_control($type, array $args = array()) {
        $this->captured_controls[$args['name']] = $args + array('group' => $type);
    }

    public function get_settings_for_display() {
        return $this->test_settings;
    }

    /** Appel public au rendu protégé du widget. */
    public function test_render() {
        ob_start();
        $this->render();
        return ob_get_clean();
    }
}
