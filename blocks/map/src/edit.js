/**
 * Édition du bloc « Geofolio Map » : réglages dans l'inspecteur, aperçu réel
 * rendu par le serveur puis initialisé avec le script de la carte, chargé
 * dans l'iframe de l'éditeur (voir Geofolio\Blocks\MapBlock).
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

/** Hauteur par défaut, alignée sur Geofolio\Map\Defaults::HEIGHT. */
const DEFAULT_HEIGHT = '600px';

/**
 * Options du fond de carte : « réglage du site », puis chaque fournisseur.
 *
 * @return {Array<{label: string, value: string}>} Options du sélecteur.
 */
function tileOptions() {
	const tiles = ( window.geofolioBlock && window.geofolioBlock.tiles ) || {};
	return [
		{ label: __( 'Site setting', 'geofolio' ), value: '' },
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
			const container = root.querySelector( '.gfo-map-container' );
			if ( container && ! container.dataset.gfoPreview && view.Geofolio ) {
				container.dataset.gfoPreview = '1';
				view.Geofolio.init( container );
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
				<PanelBody title={ __( 'Map parameters', 'geofolio' ) }>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Map height', 'geofolio' ) }
						help={ __( 'A CSS length: 600px, 80vh, 40rem…', 'geofolio' ) }
						value={ attributes.height || '' }
						placeholder={ DEFAULT_HEIGHT }
						onChange={ ( value ) => setAttributes( { height: value || undefined } ) }
					/>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Tile style', 'geofolio' ) }
						value={ attributes.tile_style }
						options={ tileOptions() }
						onChange={ ( value ) => setAttributes( { tile_style: value } ) }
					/>
					{ toggle( 'fit_bounds', __( 'Fit the view to the places', 'geofolio' ) ) }
					{ ! attributes.fit_bounds && (
						<>
							<TextControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								type="number"
								step="0.0001"
								label={ __( 'Centre latitude', 'geofolio' ) }
								value={ attributes.center_lat ?? '' }
								onChange={ ( value ) => setAttributes( { center_lat: value === '' ? undefined : parseFloat( value ) } ) }
							/>
							<TextControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								type="number"
								step="0.0001"
								label={ __( 'Centre longitude', 'geofolio' ) }
								value={ attributes.center_lng ?? '' }
								onChange={ ( value ) => setAttributes( { center_lng: value === '' ? undefined : parseFloat( value ) } ) }
							/>
							<RangeControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								label={ __( 'Initial zoom', 'geofolio' ) }
								value={ attributes.zoom }
								min={ 1 }
								max={ 18 }
								allowReset
								onChange={ ( value ) => setAttributes( { zoom: value } ) }
							/>
						</>
					) }
				</PanelBody>
				<PanelBody title={ __( 'Display options', 'geofolio' ) } initialOpen={ false }>
					{ toggle( 'show_search', __( 'Show the search', 'geofolio' ) ) }
					{ toggle( 'show_filter', __( 'Show the type filter', 'geofolio' ) ) }
					{ toggle( 'show_list', __( 'Show the results list', 'geofolio' ) ) }
					{ toggle( 'show_fullscreen', __( 'Full screen button', 'geofolio' ) ) }
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Sidebar position', 'geofolio' ) }
						value={ attributes.sidebar_position }
						options={ [
							{ label: __( 'Left', 'geofolio' ), value: 'left' },
							{ label: __( 'Right', 'geofolio' ), value: 'right' },
						] }
						onChange={ ( value ) => setAttributes( { sidebar_position: value } ) }
					/>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Sidebar title', 'geofolio' ) }
						value={ attributes.sidebar_title ?? '' }
						onChange={ ( value ) => setAttributes( { sidebar_title: value } ) }
					/>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Sidebar subtitle', 'geofolio' ) }
						value={ attributes.sidebar_subtitle ?? '' }
						onChange={ ( value ) => setAttributes( { sidebar_subtitle: value } ) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div ref={ ref }>
					<ServerSideRender block="geofolio/map" attributes={ attributes } />
				</div>
			</div>
		</>
	);
}
