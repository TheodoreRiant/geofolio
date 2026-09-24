/**
 * Bloc « Geofolio Map » : enregistrement côté éditeur. Le rendu est fait
 * par le serveur (render.php) ; le bloc ne sauvegarde aucun HTML.
 */
import { registerBlockType } from '@wordpress/blocks';

import metadata from './block.json';
import Edit from './edit';

registerBlockType( metadata.name, {
	edit: Edit,
	save: () => null,
} );
