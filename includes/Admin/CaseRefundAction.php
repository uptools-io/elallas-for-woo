<?php
/**
 * Admin-post handler for recording a refund from a case.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Admin;

use LightweightPlugins\Elallas\Database\CaseRepository;
use LightweightPlugins\Elallas\Woo\RefundCreator;

/**
 * Validates the refund form, calls RefundCreator and redirects back with a notice.
 */
final class CaseRefundAction {

	/**
	 * Admin-post action and nonce name.
	 */
	public const ACTION = 'elallas_record_refund';

	/**
	 * Per-user transient holding the result notice for the next page load.
	 */
	private const NOTICE = 'elallas_refund_notice_';

	/**
	 * Constructor — registers the admin-post hook.
	 */
	public function __construct() {
		add_action( 'admin_post_' . self::ACTION, [ $this, 'handle' ] );
	}

	/**
	 * Handle the submitted refund form.
	 *
	 * @return void
	 */
	public function handle(): void {
		check_admin_referer( self::ACTION );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Nincs jogosultságod.', 'elallas-for-woo' ) );
		}

		$case_id = isset( $_POST['case_id'] ) ? absint( $_POST['case_id'] ) : 0;
		$case    = CaseRepository::find( $case_id );

		if ( null === $case ) {
			wp_die( esc_html__( 'Az ügy nem található.', 'elallas-for-woo' ) );
		}

		$result = RefundCreator::create(
			$case,
			$this->request(),
			isset( $_POST['restock'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['restock'] ) ) : [],
			! empty( $_POST['close_case'] ),
			get_current_user_id()
		);

		set_transient(
			self::NOTICE . get_current_user_id(),
			is_wp_error( $result )
				? [ 'error', $result->get_error_message() ]
				: [ 'success', __( 'A visszatérítés rögzítve a WooCommerce-rendelésen.', 'elallas-for-woo' ) ],
			MINUTE_IN_SECONDS
		);

		wp_safe_redirect(
			add_query_arg(
				[
					'page'    => AdminMenu::SLUG,
					'view'    => 'case',
					'case_id' => $case_id,
				],
				admin_url( 'admin.php' )
			) . '#elallas-refund'
		);
		exit;
	}

	/**
	 * Print and clear the pending result notice, if any.
	 *
	 * @return void
	 */
	public static function render_notice(): void {
		$key    = self::NOTICE . get_current_user_id();
		$notice = get_transient( $key );

		if ( ! is_array( $notice ) ) {
			return;
		}

		delete_transient( $key );
		printf(
			'<div class="notice notice-%1$s inline"><p>%2$s</p></div>',
			esc_attr( 'error' === $notice[0] ? 'error' : 'success' ),
			esc_html( (string) $notice[1] )
		);
	}

	/**
	 * Sanitized item requests: item ID => [qty] or [amount].
	 *
	 * @return array<int, array<string, int|string>>
	 */
	private function request(): array {
		$raw     = isset( $_POST['items'] ) && is_array( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : []; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce checked in handle(); each field is sanitized below.
		$request = [];

		foreach ( (array) $raw as $item_id => $fields ) {
			$fields = (array) $fields;

			if ( isset( $fields['qty'] ) ) {
				$request[ absint( $item_id ) ] = [ 'qty' => absint( $fields['qty'] ) ];
			} elseif ( isset( $fields['amount'] ) ) {
				$request[ absint( $item_id ) ] = [ 'amount' => wc_format_decimal( sanitize_text_field( (string) $fields['amount'] ) ) ];
			}
		}

		return $request;
	}
}
