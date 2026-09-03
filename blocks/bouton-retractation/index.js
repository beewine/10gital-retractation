/**
 * Bloc « Bouton de rétractation ».
 *
 * Écrit sans JSX pour rester lisible et ne nécessiter aucune compilation.
 */
( function ( blocks, element, blockEditor, components, i18n ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;
	var settings = window.ret10gBlock || { defaultLabel: '', pageUrl: '#' };

	blocks.registerBlockType( 'dixgital/bouton-retractation', {
		edit: function ( props ) {
			var label = props.attributes.label || settings.defaultLabel;
			var blockProps = blockEditor.useBlockProps( { className: 'ret10g-button-wrap' } );

			return el(
				element.Fragment,
				null,
				el(
					blockEditor.InspectorControls,
					null,
					el(
						components.PanelBody,
						{ title: __( 'Bouton', '10gital-retractation' ) },
						el( components.TextControl, {
							label: __( 'Libellé', '10gital-retractation' ),
							help: __( 'Laisser vide pour utiliser le libellé défini dans les réglages du plugin.', '10gital-retractation' ),
							value: props.attributes.label,
							onChange: function ( value ) {
								props.setAttributes( { label: value } );
							},
						} )
					)
				),
				el( 'div', blockProps, el( 'span', { className: 'ret10g__button' }, label ) )
			);
		},
		save: function () {
			return null;
		},
	} );
}(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.i18n
) );
