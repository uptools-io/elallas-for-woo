/**
 * Live gross total on the case refund form. Display only; the server recalculates.
 */
( function () {
	'use strict';

	document.querySelectorAll( '.elallas-refund-form' ).forEach( function ( form ) {
		var decimals = parseInt( form.getAttribute( 'data-decimals' ), 10 ) || 0;
		var output = form.querySelector( '.elallas-refund-total' );

		function clamp( input ) {
			var value = parseFloat( input.value ) || 0;
			var max = parseFloat( input.getAttribute( 'max' ) );

			return Math.max( 0, isNaN( max ) ? value : Math.min( value, max ) );
		}

		function update() {
			var total = 0;

			form.querySelectorAll( '.elallas-refund-qty' ).forEach( function ( input ) {
				total += clamp( input ) * ( parseFloat( input.getAttribute( 'data-unit' ) ) || 0 );
			} );
			form.querySelectorAll( '.elallas-refund-amount' ).forEach( function ( input ) {
				total += clamp( input );
			} );

			output.textContent = total.toLocaleString( document.documentElement.lang || undefined, {
				minimumFractionDigits: decimals,
				maximumFractionDigits: decimals,
			} );
		}

		form.addEventListener( 'input', update );
		update();
	} );
} )();
