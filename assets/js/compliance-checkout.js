/**
 * Elállás – block checkout slot (harmonised notice + GARAN before the place-order button).
 *
 * The server prints the markup into <template id="elallas-checkout-slot">. This
 * script clones it once into a container and inserts it before the OUTER
 * place-order wrapper (never into React's inner container). A MutationObserver
 * re-inserts the same node if a React re-render detaches it (idempotent, no
 * re-cloning, so ids never duplicate). Vanilla ES5, no dependencies.
 */
(function () {
	'use strict';

	var FALLBACK_DELAY = 6000;

	function onReady(fn) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', fn);
		} else {
			fn();
		}
	}

	function closest(el, selector) {
		if (!el || !el.closest) {
			return null;
		}
		return el.closest(selector);
	}

	onReady(function () {
		var tpl = document.getElementById('elallas-checkout-slot');
		if (!tpl || !('content' in tpl)) {
			return;
		}

		var anchors = [];
		try {
			anchors = JSON.parse(tpl.getAttribute('data-anchors') || '[]');
		} catch (e) {
			anchors = [];
		}

		var slot = document.createElement('div');
		slot.className = 'elallas-checkout-slot';
		slot.setAttribute('data-elallas-slot', '');
		slot.appendChild(document.importNode(tpl.content, true));

		var anchored = false;
		var usedFallback = false;
		var reported = '';
		var scheduled = false;

		function insertBefore(target) {
			if (!target || !target.parentNode) {
				return false;
			}
			if (slot.parentNode === target.parentNode && slot.nextSibling === target) {
				return true;
			}
			target.parentNode.insertBefore(slot, target);
			return true;
		}

		function findAnchor() {
			for (var i = 0; i < anchors.length; i++) {
				var el = null;
				try {
					el = document.querySelector(anchors[i]);
				} catch (e) {
					el = null;
				}
				if (el) {
					return el;
				}
			}
			return null;
		}

		function place() {
			var anchor = findAnchor();
			if (!anchor) {
				if (usedFallback && !slot.isConnected) {
					fallback();
				}
				return;
			}
			var target = closest(anchor, '.wp-block-woocommerce-checkout-actions-block') ||
				closest(anchor, '.wc-block-checkout__actions') ||
				anchor;
			if (insertBefore(target)) {
				anchored = true;
				usedFallback = false;
				report('ok');
			}
		}

		function fallback() {
			var root = document.querySelector('.wp-block-woocommerce-checkout');
			if (!root) {
				return;
			}
			var button = root.querySelector('button[type=submit]') ||
				root.querySelector('.wc-block-components-button');
			var placed = false;
			if (button) {
				/* Before the submit button's container: still right before the contract declaration. */
				placed = insertBefore(button.parentNode && button.parentNode !== root ? button.parentNode : button);
			}
			if (!placed) {
				var main = root.querySelector('.wc-block-checkout__main') || root.querySelector('.wc-block-components-main');
				if (main && main.firstChild) {
					placed = insertBefore(main.firstChild);
				} else if (main) {
					main.appendChild(slot);
					placed = true;
				}
			}
			if (!placed) {
				root.appendChild(slot);
			}
			usedFallback = true;
			if (window.console && window.console.warn) {
				window.console.warn('Elállás: place-order anchor not found, the notice/GARAN slot uses the fallback position. Check the elallas_checkout_block_anchors selectors.');
			}
			report('fallback');
		}

		function schedule() {
			if (scheduled) {
				return;
			}
			scheduled = true;
			var run = function () {
				scheduled = false;
				place();
			};
			if (window.requestAnimationFrame) {
				window.requestAnimationFrame(run);
			} else {
				window.setTimeout(run, 16);
			}
		}

		/* Admin slot check (?elallas_slot_check=1, manage_woocommerce only). */
		function report(status) {
			if (tpl.getAttribute('data-slot-check') !== '1' || reported === status) {
				return;
			}
			reported = status;
			var bar = document.getElementById('elallas-slot-check');
			if (!bar) {
				bar = document.createElement('div');
				bar.id = 'elallas-slot-check';
				bar.setAttribute('role', 'status');
				bar.style.cssText = 'position:fixed;left:0;right:0;bottom:0;z-index:99999;padding:10px 16px;font:14px/1.4 sans-serif;color:#fff;';
				document.body.appendChild(bar);
			}
			bar.style.background = status === 'ok' ? '#00703c' : '#b32d2e';
			bar.textContent = tpl.getAttribute(status === 'ok' ? 'data-msg-ok' : 'data-msg-fallback') || status;

			var url = tpl.getAttribute('data-ajax-url');
			if (!url || !window.XMLHttpRequest) {
				return;
			}
			var xhr = new XMLHttpRequest();
			xhr.open('POST', url, true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.send('action=elallas_slot_fallback&status=' + encodeURIComponent(status) +
				'&nonce=' + encodeURIComponent(tpl.getAttribute('data-nonce') || ''));
		}

		/* Hide GARAN rows whose cart item was removed (wc/store/cart). */
		function syncCart() {
			var store = window.wp && window.wp.data && window.wp.data.select ? window.wp.data.select('wc/store/cart') : null;
			if (!store || !store.getCartData) {
				return;
			}
			var lastKeys = null;
			window.wp.data.subscribe(function () {
				var data = store.getCartData();
				var items = data && data.items ? data.items : [];
				if (!items.length) {
					return;
				}
				var keys = [];
				for (var i = 0; i < items.length; i++) {
					keys.push(String(items[i].key));
				}
				var joined = keys.join('|');
				if (joined === lastKeys) {
					return;
				}
				lastKeys = joined;
				var rows = slot.querySelectorAll('[data-elallas-cart-key]');
				for (var r = 0; r < rows.length; r++) {
					rows[r].hidden = keys.indexOf(rows[r].getAttribute('data-elallas-cart-key')) === -1;
				}
				var lists = slot.querySelectorAll('[data-elallas-cart-list]');
				for (var l = 0; l < lists.length; l++) {
					lists[l].hidden = !lists[l].querySelector('[data-elallas-cart-key]:not([hidden])');
				}
			});
		}

		place();
		if (window.MutationObserver) {
			new window.MutationObserver(function () {
				if (!slot.isConnected || !anchored || usedFallback) {
					schedule();
				} else {
					var anchor = findAnchor();
					var target = anchor ? (closest(anchor, '.wp-block-woocommerce-checkout-actions-block') || closest(anchor, '.wc-block-checkout__actions') || anchor) : null;
					if (target && slot.nextSibling !== target) {
						schedule();
					}
				}
			}).observe(document.body, { childList: true, subtree: true });
		}
		window.setTimeout(function () {
			if (!anchored && !slot.isConnected) {
				fallback();
			}
		}, FALLBACK_DELAY);
		syncCart();
	});
})();
