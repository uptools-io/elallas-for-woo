<?php
/**
 * Registers and enqueues the elallas-compliance CSS/JS.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

use LightweightPlugins\Elallas\Options;

/**
 * The shared `elallas-compliance` style + script (notice styles and the
 * delegated disclosure behaviour: hover, Esc, outside click, aria-expanded).
 *
 * Only registered on `wp_enqueue_scripts`; renderers call enqueue() right
 * before printing, late styles are printed in the footer by core. Pages that
 * are known to show the notice get it early to avoid a flash of unstyled markup.
 */
final class ComplianceAssets {

	/**
	 * Style and script handle.
	 */
	public const HANDLE = 'elallas-compliance';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register' ] );
	}

	/**
	 * Register the handles and enqueue early where the notice is known to show.
	 *
	 * @return void
	 */
	public function register(): void {
		self::register_handles();

		if ( self::expected_on_page() ) {
			self::enqueue();
		}
	}

	/**
	 * Enqueue the shared style + script (safe to call repeatedly and late).
	 *
	 * @return void
	 */
	public static function enqueue(): void {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		self::register_handles();
		wp_enqueue_style( self::HANDLE );
		wp_enqueue_script( self::HANDLE );
	}

	/**
	 * Register both handles once.
	 *
	 * @return void
	 */
	private static function register_handles(): void {
		if ( wp_style_is( self::HANDLE, 'registered' ) ) {
			return;
		}

		wp_register_style( self::HANDLE, ELALLAS_FOR_WOO_URL . 'assets/css/compliance.css', [], ELALLAS_FOR_WOO_VERSION );
		wp_register_script( self::HANDLE, ELALLAS_FOR_WOO_URL . 'assets/js/compliance.js', [], ELALLAS_FOR_WOO_VERSION, true );
	}

	/**
	 * Whether the notice is expected on the current page.
	 *
	 * @return bool
	 */
	private static function expected_on_page(): bool {
		if ( ! Options::get( 'notice_enabled' ) ) {
			return false;
		}

		if ( Options::get( 'notice_display_header' ) || Options::get( 'notice_display_footer' ) ) {
			return true;
		}

		if ( Options::get( 'notice_display_product' ) && function_exists( 'is_product' ) && is_product() ) {
			return true;
		}

		return Options::get( 'notice_display_checkout' ) && function_exists( 'is_checkout' )
			&& ( is_checkout() || ( function_exists( 'is_account_page' ) && is_account_page() ) );
	}
}
