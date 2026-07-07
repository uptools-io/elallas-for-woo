<?php
/**
 * Front-end asset enqueuing.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Frontend;

use LightweightPlugins\Elallas\Options;
use LightweightPlugins\Elallas\Integrations\Multilingual;

/**
 * Enqueues the small front-end stylesheet/script only where needed.
 */
final class Assets {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
	}

	/**
	 * Enqueue assets on the withdrawal page and account endpoint.
	 *
	 * @return void
	 */
	public function enqueue(): void {
		if ( ! $this->should_load() ) {
			return;
		}

		wp_enqueue_style(
			'elallas-frontend',
			ELALLAS_FOR_WOO_URL . 'assets/css/frontend.css',
			[],
			ELALLAS_FOR_WOO_VERSION
		);

		wp_enqueue_script(
			'elallas-frontend',
			ELALLAS_FOR_WOO_URL . 'assets/js/frontend.js',
			[],
			ELALLAS_FOR_WOO_VERSION,
			true
		);
	}

	/**
	 * Whether assets should load on the current request.
	 *
	 * @return bool
	 */
	private function should_load(): bool {
		$page_id = (int) Options::get( 'withdrawal_page_id' );

		// Match the translated withdrawal page too, not just the original one.
		if ( $page_id > 0 ) {
			$page_id = Multilingual::object_id( $page_id, 'page' );

			if ( is_page( $page_id ) ) {
				return true;
			}
		}

		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			return true;
		}

		global $post;

		return $post instanceof \WP_Post && has_shortcode( (string) $post->post_content, 'elallas_form' );
	}
}
