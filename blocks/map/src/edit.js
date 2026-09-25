/**
 * Édition du bloc « Mapped Places Map » : réglages dans l'inspecteur, aperçu réel
 * rendu par le serveur puis initialisé avec le script de la carte, chargé
 * dans l'iframe de l'éditeur (voir MappedPlaces\Blocks\MapBlock).
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { useEffect, useRef } from '@wordpress/element';

/** Hauteur par défaut, alignée sur MappedPlaces\Map\Defaults::HEIGHT. */
const DEFAULT_HEIGHT = '600px';

/**
 * Options du fond de carte : « réglage du site », puis chaque fournisseur.
 *
 * @return {Array<{label: string, value: string}>} Options du sélecteur.
 */
function tileOptions() {
	const tiles = ( window.mappedPlacesBlock && window.mappedPlacesBlock.tiles ) || {};
	return [
		{ label: __( 'Site setting', 'mapped-places' ), value: '' },
		...Object.keys( tiles ).map( ( id ) => ( { label: tiles[ id ], value: id } ) ),
	];
}

/**
 * Initialiser la carte rendue par le serveur dès qu'elle apparaît dans
 * l'aperçu, et à chaque nouveau rendu (réglage modifié).
 *
 * @param {Object} ref Référence React du conteneur de l'aperçu.
 */
function useMapPreview( ref ) {
	useEffect( () => {
		const root = ref.current;
		if ( ! root ) {
			return undefined;
		}
		const start = () => {
			const view = root.ownerDocument.defaultView;
			const container = root.querySelector( '.mapl-map-container' );
			if ( container && ! container.dataset.maplPreview && view.MappedPlaces ) {
				container.dataset.maplPreview = '1';
				view.MappedPlaces.init( container );
			}
		};
		const observer = new root.ownerDocument.defaultView.MutationObserver( start );
		observer.observe( root, { childList: true, subtree: true } );
		start();
		return () => observer.disconnect();
	}, [ ref ] );
}

export default function Edit( { attributes, setAttributes } ) {
	const ref = useRef();
	const blockProps = useBlockProps();
	useMapPreview( ref );

	const toggle = ( key, label ) => (
		<ToggleControl
			__nextHasNoMarginBottom
			label={ label }
			checked={ !! attributes[ key ] }
			onChange={ ( value ) => setAttributes( { [ key ]: value } ) }
		/>
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Map parameters', 'mapped-places' ) }>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Map height', 'mapped-places' ) }
						help={ __( 'A CSS length: 600px, 80vh, 40rem…', 'mapped-places' ) }
						value={ attributes.height || '' }
						placeholder={ DEFAULT_HEIGHT }
						onChange={ ( value ) => setAttributes( { height: value || undefined } ) }
					/>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Tile style', 'mapped-places' ) }
						value={ attributes.tile_style }
						options={ tileOptions() }
						onChange={ ( value ) => setAttributes( { tile_style: value } ) }
					/>
					{ toggle( 'fit_bounds', __( 'Fit the view to the places', 'mapped-places' ) ) }
					{ ! attributes.fit_bounds && (
						<>
							<TextControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								type="number"
								step="0.0001"
								label={ __( 'Centre latitude', 'mapped-places' ) }
								value={ attributes.center_lat ?? '' }
								onChange={ ( value ) => setAttributes( { center_lat: value === '' ? undefined : parseFloat( value ) } ) }
							/>
							<TextControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								type="number"
								step="0.0001"
								label={ __( 'Centre longitude', 'mapped-places' ) }
								value={ attributes.center_lng ?? '' }
								onChange={ ( value ) => setAttributes( { center_lng: value === '' ? undefined : parseFloat( value ) } ) }
							/>
							<RangeControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								label={ __( 'Initial zoom', 'mapped-places' ) }
								value={ attributes.zoom }
								min={ 1 }
								max={ 18 }
								allowReset
								onChange={ ( value ) => setAttributes( { zoom: value } ) }
							/>
						</>
					) }
				</PanelBody>
				<PanelBody title={ __( 'Display options', 'mapped-places' ) } initialOpen={ false }>
					{ toggle( 'show_search', __( 'Show the search', 'mapped-places' ) ) }
					{ toggle( 'show_filter', __( 'Show the type filter', 'mapped-places' ) ) }
					{ toggle( 'show_list', __( 'Show the results list', 'mapped-places' ) ) }
					{ toggle( 'show_fullscreen', __( 'Full screen button', 'mapped-places' ) ) }
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Sidebar position', 'mapped-places' ) }
						value={ attributes.sidebar_position }
						options={ [
							{ label: __( 'Left', 'mapped-places' ), value: 'left' },
							{ label: __( 'Right', 'mapped-places' ), value: 'right' },
						] }
						onChange={ ( value ) => setAttributes( { sidebar_position: value } ) }
					/>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Sidebar title', 'mapped-places' ) }
						value={ attributes.sidebar_title ?? '' }
						onChange={ ( value ) => setAttributes( { sidebar_title: value } ) }
					/>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Sidebar subtitle', 'mapped-places' ) }
						value={ attributes.sidebar_subtitle ?? '' }
						onChange={ ( value ) => setAttributes( { sidebar_subtitle: value } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div ref={ ref }>
					<ServerSideRender block="mapped-places/map" attributes={ attributes } />
				</div>
			</div>
		</>
	);
}
