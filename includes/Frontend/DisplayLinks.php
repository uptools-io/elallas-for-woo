<?php
/**
 * Global header/footer withdrawal links.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Frontend;

use LightweightPlugins\Elallas\Options;

/**
 * Prints a link to the withdrawal page in the site header and/or footer.
 */
final class DisplayLinks {

	/**
	 * Constructor — hooks the enabled placements.
	 */
	public function __construct() {
		if ( ! Options::get( 'enabled' ) ) {
			return;
		}

		if ( Options::get( 'display_header' ) ) {
			add_action( 'wp_body_open', [ $this, 'render' ] );
		}

		if ( Options::get( 'display_footer' ) ) {
			add_action( 'wp_footer', [ $this, 'render' ] );
		}
	}

	/**
	 * Render the link.
	 *
	 * Resolves the page URL here (not in the constructor): permalinks are not
	 * available as early as `plugins_loaded`.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( is_admin() || '' === Shortcodes::page_url() ) {
			return;
		}

		printf(
			'<div class="elallas-global-link"><a href="%1$s">%2$s</a></div>',
			esc_url( Shortcodes::page_url() ),
			esc_html( (string) Options::get( 'button_label' ) )
		);
	}
}
