<?php
/**
 * Renommer les blocs d'un ancien plugin dans le contenu des posts : seuls
 * les délimiteurs de bloc changent, leurs attributs restent.
 *
 * @package Mapped Places
 */

namespace MappedPlaces\Migration\Legacy;

if (!defined('ABSPATH')) {
    exit;
}

class BlockRewriter {

    /**
     * @param string   $content
     * @param string[] $old Anciens noms de bloc (espace/nom).
     * @param string   $new Nouveau nom.
     * @return string
     */
    public static function rewrite($content, array $old, $new) {
        if ($old === array()) {
            return (string) $content;
        }
        $names = implode('|', array_map(static function ($name) {
            return preg_quote($name, '/');
        }, $old));
        // Délimiteur ouvrant, auto-fermant ou fermant ; le nom doit être
        // suivi d'un espace, de / ou de - (fin « --> ») : old/mapx reste.
        return (string) preg_replace('/<!--\s+(\/?)wp:(?:' . $names . ')(?=[\s\/-])/', '<!-- $1wp:' . $new, (string) $content);
    }

    /**
     * Appliquer aux posts publiés ou brouillons (pas aux révisions).
     *
     * @param string[] $old
     * @param string   $new
     * @return int Nombre de posts réécrits.
     */
    public static function apply(array $old, $new) {
        global $wpdb;
        $updated = 0;
        foreach ($old as $name) {
            $rows = $wpdb->get_results($wpdb->prepare(
                "SELECT ID, post_content FROM {$wpdb->posts} WHERE post_type <> 'revision' AND post_content LIKE %s",
                '%' . $wpdb->esc_like('wp:' . $name) . '%'
            ));
            foreach ((array) $rows as $row) {
                $content = self::rewrite($row->post_content, $old, $new);
                if ($content !== $row->post_content) {
                    $wpdb->update($wpdb->posts, array('post_content' => $content), array('ID' => (int) $row->ID));
                    clean_post_cache((int) $row->ID);
                    $updated++;
                }
            }
        }
        return $updated;
    }
}
