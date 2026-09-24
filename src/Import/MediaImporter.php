<?php
/**
 * Photos d'un jeu de données : image à la une et galerie, lues dans le
 * dossier du jeu et versées dans la médiathèque.
 *
 * Réservé aux jeux de confiance (le jeu livré avec l'extension, ou celui
 * d'un compagnon) : un CSV téléversé n'a pas de dossier de médias, ses
 * colonnes image et galerie sont ignorées.
 */

namespace Geofolio\Import;

use Geofolio\Domain\FieldRegistry;

if (!defined('ABSPATH')) {
    exit;
}

final class MediaImporter {

    /** Extensions d'image acceptées. */
    const EXTENSIONS = array('jpg', 'jpeg', 'png', 'webp');

    /** Meta d'une pièce jointe : empreinte du fichier importé (réutilisation). */
    const SOURCE_META = '_geofolio_import_source';

    /** @var array<string, int> Empreinte => ID de pièce jointe, pour l'import en cours. */
    private $attachments = array();

    /**
     * Fichiers d'une liste (« a.jpg; b.png »), cherchés dans le dossier du
     * jeu. Un nom qui sort du dossier, un fichier absent ou qui n'est pas
     * une image est ignoré.
     *
     * @param string      $list
     * @param string|null $dir  Dossier des médias (null : aucun média).
     * @return string[] Chemins absolus.
     */
    public static function resolve_files($list, $dir) {
        if ($dir === null || !is_dir($dir)) {
            return array();
        }
        $files = array();
        foreach (Importer::split_list((string) $list) as $name) {
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (preg_match('#[/\\\\]|\.\.#', $name) || !in_array($extension, self::EXTENSIONS, true)) {
                continue;
            }
            $path = rtrim($dir, '/') . '/' . $name;
            if (is_file($path) && !in_array($path, $files, true)) {
                $files[] = $path;
            }
        }
        return $files;
    }

    /**
     * Poser l'image à la une et la galerie d'un lieu.
     *
     * @param int         $post_id
     * @param array       $fields Champs de la ligne.
     * @param string|null $dir    Dossier des médias.
     */
    public function attach($post_id, array $fields, $dir) {
        $cover = self::resolve_files($fields['image'] ?? '', $dir);
        if ($cover) {
            $id = $this->attachment_id($cover[0], $post_id);
            if ($id) {
                set_post_thumbnail($post_id, $id);
            }
        }

        $gallery = array();
        foreach (self::resolve_files($fields['gallery'] ?? '', $dir) as $path) {
            $id = $this->attachment_id($path, $post_id);
            if ($id) {
                $gallery[] = $id;
            }
        }
        if ($gallery) {
            update_post_meta($post_id, FieldRegistry::meta_key('gallery'), wp_json_encode($gallery));
        }
    }

    /**
     * Pièce jointe d'un fichier : réutilisée si le même fichier a déjà été
     * importé (dans cet import ou un précédent), créée sinon.
     *
     * @param string $path
     * @param int    $post_id Lieu parent.
     * @return int 0 en cas d'échec.
     */
    private function attachment_id($path, $post_id) {
        $source = basename($path) . ':' . sha1_file($path);
        if (isset($this->attachments[$source])) {
            return $this->attachments[$source];
        }

        $existing = get_posts(array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_key'       => self::SOURCE_META,
            'meta_value'     => $source,
        ));
        $id = $existing ? (int) $existing[0] : self::sideload($path, $post_id);
        if ($id && !$existing) {
            update_post_meta($id, self::SOURCE_META, $source);
        }

        $this->attachments[$source] = $id;
        return $id;
    }

    /**
     * Copier le fichier dans la médiathèque (tailles intermédiaires comprises).
     *
     * @param string $path
     * @param int    $post_id
     * @return int
     */
    private static function sideload($path, $post_id) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // media_handle_sideload() déplace le fichier : on lui donne une copie.
        $tmp = wp_tempnam(basename($path));
        if (!$tmp || !copy($path, $tmp)) {
            return 0;
        }
        $id = media_handle_sideload(array('name' => basename($path), 'tmp_name' => $tmp), $post_id);
        if (is_wp_error($id)) {
            @unlink($tmp);
            return 0;
        }
        return (int) $id;
    }
}
