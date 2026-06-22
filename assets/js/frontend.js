/* Elállás for WooCommerce — front-end progressive enhancement.
 * Core flow works without JS; this adds the order quick-pick and a
 * double-submit guard on the confirm step.
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
		// Identify step: the order quick-pick fills the manual order-number field.
		var orderPick  = document.querySelector( '.elallas-order-pick' );
		var orderInput = document.getElementById( 'elallas-order-number' );
		if ( orderPick && orderInput ) {
			orderPick.addEventListener( 'change', function () {
				if ( orderPick.value ) {
					orderInput.value = orderPick.value;
				}
			} );
		}

		// Confirm step: disable the submit briefly to prevent double submissions.
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
