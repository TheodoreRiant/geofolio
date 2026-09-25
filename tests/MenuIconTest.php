<?php
/**
 * Icône du menu des lieux : l'épingle standard de WordPress, lisible à
 * 20 px (le pictogramme de marque, carte pliée et épingle, ne l'était pas).
 */

use PHPUnit\Framework\TestCase;
use MappedPlaces\Domain\PlacePostType;

final class MenuIconTest extends TestCase {

    public function test_le_menu_utilise_l_epingle_de_wordpress() {
        $this->assertSame('dashicons-location', PlacePostType::MENU_ICON);
    }
}
