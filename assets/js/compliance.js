/* Elállás for WooCommerce — compliance disclosures (notice, shared behaviour).
 *
 * Works on every <details data-elallas-disclosure>. The markup is fully usable
 * without JS; this adds hover opening (fine pointers only, no modal, no focus
 * trap), Esc to close with focus return, outside-click closing and an
 * aria-expanded mirror. All handlers are delegated on document because the
 * classic checkout replaces its payment fragment over AJAX.
 */
( function () {
	'use strict';

	var SELECTOR   = 'details[data-elallas-disclosure]';
	var HOVER_FLAG = 'data-elallas-hover';
	var OPEN_DELAY = 150;
	var CLOSE_DELAY = 400;

	var canHover = !! ( window.matchMedia && window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches );

	function onReady( fn ) {
		if ( document.readyState !== 'loading' ) {
			fn();
		} else {
			document.addEventListener( 'DOMContentLoaded', fn );
		}
	}

	function matches( el, sel ) {
		var fn = el.matches || el.msMatchesSelector || el.webkitMatchesSelector;
		return !! fn && fn.call( el, sel );
	}

	function closest( el, sel ) {
		while ( el && el.nodeType === 1 ) {
			if ( matches( el, sel ) ) {
				return el;
			}
			el = el.parentNode;
		}
		return null;
	}

	function summaryOf( details ) {
		for ( var i = 0; i < details.children.length; i++ ) {
			if ( details.children[ i ].tagName === 'SUMMARY' ) {
				return details.children[ i ];
			}
		}
		return null;
	}

	function sync( details ) {
		var summary = summaryOf( details );
		if ( summary ) {
			summary.setAttribute( 'aria-expanded', details.open ? 'true' : 'false' );
		}
	}

	function syncAll() {
		var all = document.querySelectorAll( SELECTOR );
		for ( var i = 0; i < all.length; i++ ) {
			sync( all[ i ] );
		}
	}

	function clearTimers( details ) {
		clearTimeout( details._elallasOpen );
		clearTimeout( details._elallasClose );
	}

	function setOpen( details, open ) {
		clearTimers( details );
		if ( ! open ) {
			details.removeAttribute( HOVER_FLAG );
		}
		details.open = open;
		sync( details );
	}

	// Mirror aria-expanded on every native toggle (toggle does not bubble: capture).
	document.addEventListener( 'toggle', function ( e ) {
		var details = e.target;
		if ( details && details.nodeType === 1 && matches( details, SELECTOR ) ) {
			if ( ! details.open ) {
				details.removeAttribute( HOVER_FLAG );
			}
			sync( details );
		}
	}, true );

	// Hover: open after a short delay when the pointer enters the summary.
	document.addEventListener( 'mouseover', function ( e ) {
		if ( ! canHover ) {
			return;
		}
		var details = closest( e.target, SELECTOR );
		if ( ! details || ( e.relatedTarget && details.contains( e.relatedTarget ) ) ) {
			return;
		}
		clearTimeout( details._elallasClose );
		if ( ! details.open && closest( e.target, 'summary' ) ) {
			details._elallasOpen = setTimeout( function () {
				details.setAttribute( HOVER_FLAG, '1' );
				details.open = true;
				sync( details );
			}, OPEN_DELAY );
		}
	} );

	// Leaving: close only what hover opened (a clicked panel stays open).
	document.addEventListener( 'mouseout', function ( e ) {
		if ( ! canHover ) {
			return;
		}
		var details = closest( e.target, SELECTOR );
		if ( ! details || ( e.relatedTarget && details.contains( e.relatedTarget ) ) ) {
			return;
		}
		clearTimeout( details._elallasOpen );
		if ( details.open && details.hasAttribute( HOVER_FLAG ) ) {
			details._elallasClose = setTimeout( function () {
				setOpen( details, false );
			}, CLOSE_DELAY );
		}
	} );

	document.addEventListener( 'click', function ( e ) {
		var summary = closest( e.target, 'summary' );
		var own     = summary ? closest( summary, SELECTOR ) : null;

		// A click on a hover-opened summary pins it open instead of closing it.
		if ( own && own.open && own.hasAttribute( HOVER_FLAG ) && summary.parentNode === own ) {
			e.preventDefault();
			clearTimers( own );
			own.removeAttribute( HOVER_FLAG );
			return;
		}

		// Outside click closes open disclosures.
		var open = document.querySelectorAll( SELECTOR + '[open]' );
		for ( var i = 0; i < open.length; i++ ) {
			if ( ! open[ i ].contains( e.target ) ) {
				setOpen( open[ i ], false );
			}
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key !== 'Escape' && e.key !== 'Esc' ) {
			return;
		}
		var active = closest( document.activeElement, SELECTOR + '[open]' );
		if ( active ) {
			setOpen( active, false );
			var summary = summaryOf( active );
			if ( summary ) {
				summary.focus();
			}
			return;
		}
		var hovered = document.querySelectorAll( SELECTOR + '[' + HOVER_FLAG + ']' );
		for ( var i = 0; i < hovered.length; i++ ) {
			setOpen( hovered[ i ], false );
		}
	} );

	onReady( function () {
		syncAll();
		if ( window.jQuery ) {
			window.jQuery( document.body ).on( 'updated_checkout', syncAll );
		}
	} );
}() );
