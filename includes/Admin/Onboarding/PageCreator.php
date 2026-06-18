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
		$existing = (int) Options::get( 'withdrawal_page_id' );

		if ( $existing > 0 && 'publish' === get_post_status( $existing ) ) {
			return $existing;
		}

		$page_id = wp_insert_post(
			[
				'post_title'   => __( 'Elállás', 'elallas-for-woo' ),
				'post_content' => '[elallas_form]',
				'post_status'  => 'publish',
				'post_type'    => 'page',
			]
		);

		if ( is_wp_error( $page_id ) || 0 === $page_id ) {
			return 0;
		}

		Options::set( 'withdrawal_page_id', (int) $page_id );

		return (int) $page_id;
	}
}
