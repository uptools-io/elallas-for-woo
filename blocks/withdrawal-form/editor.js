( function () {
	'use strict';

	var el            = wp.element.createElement;
	var registerBlock = wp.blocks.registerBlockType;
	var useBlockProps  = wp.blockEditor.useBlockProps;
	var __             = wp.i18n.__;

	registerBlock( 'elallas/withdrawal-form', {
		edit: function () {
			var blockProps = useBlockProps( {
				style: {
					border: '2px dashed #2271b1',
					borderRadius: '4px',
					padding: '16px',
					background: 'rgba(34, 113, 177, 0.04)',
					textAlign: 'center'
				}
			} );

			return el( 'div', blockProps,
				el( 'div', {
					style: {
						fontSize: '11px',
						fontWeight: 600,
						textTransform: 'uppercase',
						color: '#2271b1',
						marginBottom: '8px',
						letterSpacing: '0.5px'
					}
				}, __( 'Elállási űrlap', 'elallas-for-woo' ) ),
				el( 'p', { style: { margin: 0 } },
					__( 'Az elállási nyilatkozat űrlap a webhely látogatói oldalán jelenik meg.', 'elallas-for-woo' )
				)
			);
		},

		save: function () {
			return null;
		}
	} );
} )();
