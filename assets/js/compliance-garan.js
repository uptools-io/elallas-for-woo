/**
 * Elállás – GARAN label: nested label opens the full label (click, tap,
 * Enter/Space, or hover on hover-capable devices); Escape closes it and
 * returns focus. All handlers are delegated on document because the classic
 * checkout replaces its payment fragment on every update_checkout. Full labels
 * delivered in <template> are cloned lazily; identical labels in one list share
 * a single moving instance, so ids never repeat. Vanilla ES5.
 */
(function () {
	'use strict';

	var HOVER_OPEN = 150;
	var HOVER_CLOSE = 400;
	var canHover = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
	var openTimer = null;

	function closest(el, selector) {
		while (el && el.nodeType === 1) {
			if (el.matches ? el.matches(selector) : el.msMatchesSelector(selector)) {
				return el;
			}
			el = el.parentNode;
		}
		return null;
	}

	function regionOf(toggle) {
		var id = toggle.getAttribute('aria-controls');
		return id ? document.getElementById(id) : null;
	}

	function materialize(region) {
		var scroller = region.querySelector('.elallas-garan__scroller') || region;
		if (scroller.querySelector('[data-elallas-garan-instance]') || scroller.querySelector('svg')) {
			return;
		}
		var key = region.getAttribute('data-template-ref');
		var tpl = scroller.querySelector('template[data-elallas-garan-full]');
		if (!tpl && key) {
			tpl = document.querySelector('template[data-elallas-garan-full="' + key + '"]');
		}
		if (!tpl) {
			return;
		}
		key = key || tpl.getAttribute('data-elallas-garan-full');
		var existing = document.querySelector('[data-elallas-garan-instance="' + key + '"]');
		if (existing) {
			var owner = closest(existing, '.elallas-garan__full');
			if (owner && owner !== region) {
				setOpen(owner, false);
			}
			scroller.appendChild(existing);
			return;
		}
		var wrap = document.createElement('div');
		wrap.setAttribute('data-elallas-garan-instance', key);
		wrap.appendChild(document.importNode(tpl.content, true));
		scroller.appendChild(wrap);
	}

	function toggleFor(region) {
		return region && region.id ? document.querySelector('.elallas-garan__toggle[aria-controls="' + region.id + '"]') : null;
	}

	function setOpen(region, open, viaHover) {
		if (!region) {
			return;
		}
		if (open) {
			materialize(region);
		}
		region.hidden = !open;
		region.setAttribute('data-hover', open && viaHover ? '1' : '');
		var toggle = toggleFor(region);
		if (toggle) {
			toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		}
	}

	document.addEventListener('click', function (e) {
		var toggle = closest(e.target, '.elallas-garan__toggle');
		if (!toggle) {
			return;
		}
		e.preventDefault();
		window.clearTimeout(openTimer);
		var region = regionOf(toggle);
		if (!region) {
			return;
		}
		var hoverOpened = region.getAttribute('data-hover') === '1';
		setOpen(region, region.hidden || hoverOpened, false);
	});

	document.addEventListener('keydown', function (e) {
		if (e.key !== 'Escape' && e.key !== 'Esc') {
			return;
		}
		var open = document.querySelectorAll('.elallas-garan__full:not([hidden])');
		for (var i = 0; i < open.length; i++) {
			var toggle = toggleFor(open[i]);
			var hadFocus = open[i].contains(document.activeElement);
			setOpen(open[i], false);
			if (toggle && (hadFocus || document.activeElement === toggle)) {
				toggle.focus();
			}
		}
	});

	if (canHover) {
		var closeTimers = {};

		document.addEventListener('mouseover', function (e) {
			var toggle = closest(e.target, '.elallas-garan__toggle');
			var label = closest(e.target, '[data-elallas-garan]');
			if (label) {
				var r = label.querySelector('.elallas-garan__full');
				if (r && closeTimers[r.id]) {
					window.clearTimeout(closeTimers[r.id]);
					delete closeTimers[r.id];
				}
			}
			if (!toggle || (e.relatedTarget && toggle.contains(e.relatedTarget))) {
				return;
			}
			var region = regionOf(toggle);
			if (!region || !region.hidden) {
				return;
			}
			window.clearTimeout(openTimer);
			openTimer = window.setTimeout(function () {
				if (region.hidden) {
					setOpen(region, true, true);
				}
			}, HOVER_OPEN);
		});

		document.addEventListener('mouseout', function (e) {
			var label = closest(e.target, '[data-elallas-garan]');
			if (!label || (e.relatedTarget && label.contains(e.relatedTarget))) {
				return;
			}
			window.clearTimeout(openTimer);
			var region = label.querySelector('.elallas-garan__full');
			if (!region || region.hidden || region.getAttribute('data-hover') !== '1') {
				return;
			}
			closeTimers[region.id] = window.setTimeout(function () {
				if (region.getAttribute('data-hover') === '1') {
					setOpen(region, false);
				}
				delete closeTimers[region.id];
			}, HOVER_CLOSE);
		});
	}

	/* Classic checkout fragment refresh: re-sync aria-expanded with [hidden]. */
	function resync() {
		var toggles = document.querySelectorAll('.elallas-garan__toggle');
		for (var i = 0; i < toggles.length; i++) {
			var region = regionOf(toggles[i]);
			toggles[i].setAttribute('aria-expanded', region && !region.hidden ? 'true' : 'false');
		}
	}
	if (window.jQuery) {
		window.jQuery(document.body).on('updated_checkout updated_cart_totals', resync);
	}

	window.elallasGaran = { setOpen: setOpen, resync: resync };
})();
