<?php
/**
 * Creates the withdrawal page during onboarding.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin\Onboarding;

use LightweightPlugins\Elallas\Options;

/**
 * Handles the "create withdrawal page" onboarding action.
 */
final class PageCreator {

	/**
	 * Nonce action for page creation.
	 */
	public const NONCE = 'elallas_create_page';

	/**
	 * Register the admin-post handler.
	 *
	 * @return void
	 */
	public static function maybe_handle(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! isset( $_POST['elallas_create_page'] ) ) {
			return;
		}

		check_admin_referer( self::NONCE );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		self::create();

		wp_safe_redirect(
			add_query_arg(
				[
					'page' => 'elallas-for-woo-onboarding',
					'step' => 3,
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Insert a published "Elállás" page with the form shortcode.
	 *
	 * @return int Page ID (0 on failure).
	 */
	public static function create(): int {
		return self::create_for( 'withdrawal_page_id', __( 'Elállás', 'elallas-for-woo' ), '[elallas_form]' );
	}

	/**
	 * Insert a published page and store its ID in an option, unless the stored
	 * page still exists.
	 *
	 * @param string $option_key Option holding the page ID.
	 * @param string $title      Page title.
	 * @param string $content    Page content (shortcode).
	 * @param string $slug       Page slug ('' = derived from the title by WordPress).
	 * @return int Page ID (0 on failure).
	 */
	public static function create_for( string $option_key, string $title, string $content, string $slug = '' ): int {
		$existing = (int) Options::get( $option_key );

		if ( $existing > 0 && 'publish' === get_post_status( $existing ) ) {
			return $existing;
		}

		$postarr = [
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => 'page',
		];

		if ( '' !== $slug ) {
			$postarr['post_name'] = $slug;
		}

		$page_id = wp_insert_post( $postarr );

		if ( ! is_int( $page_id ) || 0 === $page_id ) {
			return 0;
		}

		Options::set( $option_key, $page_id );

		return $page_id;
	}
}
