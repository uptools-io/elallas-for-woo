/* Elallas for WooCommerce — front-end progressive enhancement.
 * Core flow works without JS; this only adds quantity convenience controls.
 */
( function () {
	'use strict';

	function onReady( fn ) {
		if ( document.readyState !== 'loading' ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	onReady( function () {
		var form = document.querySelector( '.elallas-step-select .elallas-step-form' );
		if ( ! form ) {
			return;
		}

		// Disable the submit briefly on confirm to prevent double submissions.
		var confirmForm = document.querySelector( '.elallas-step-confirm .elallas-step-form' );
		if ( confirmForm ) {
			confirmForm.addEventListener( 'submit', function () {
				var btn = confirmForm.querySelector( 'button[type="submit"]' );
				if ( btn ) {
					window.setTimeout( function () {
						btn.setAttribute( 'disabled', 'disabled' );
					}, 0 );
				}
			} );
		}
	} );
}() );
