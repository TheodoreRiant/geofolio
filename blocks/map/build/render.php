<?php
/**
 * Rendu du bloc « Geofolio Map » (côté serveur, dans l'éditeur comme sur
 * le site) : même gabarit que le shortcode et le widget Elementor.
 *
 * @var array $attributes Attributs du bloc.
 *
 * @package Geofolio
 */

if (!defined('ABSPATH')) {
    exit;
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML produit par views/map.php, où chaque valeur est échappée ; wp_kses_post() retirerait les SVG inline.
echo \Geofolio\Blocks\MapBlock::render($attributes);
