<?php
/**
 * Block-checkout slot collecting notice and GARAN markup.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

/**
 * Shared collector for the React (block) checkout, which runs no PHP hook
 * before the place-order button.
 *
 * Modules contribute a render callback (usually on the `wp` action); on
 * `wp_footer`@15 — before the footer scripts (@20) — every contribution is
 * printed, in priority order, into a hidden `<template id="elallas-checkout-slot">`.
 * assets/js/compliance-checkout.js clones it once in front of the outer
 * place-order wrapper and keeps it there across React re-renders.
 */
final class CheckoutSlot {

	/**
	 * Script handle of the block-checkout inserter.
	 */
	public const HANDLE = 'elallas-compliance-checkout';

	/**
	 * Transient set when the admin slot check saw the fallback placement.
	 */
	public const FALLBACK_TRANSIENT = 'lw_elallas_checkout_fallback';

	/**
	 * AJAX action / nonce action of the admin slot check report.
	 */
	private const AJAX_ACTION = 'elallas_slot_fallback';

	/**
	 * Contributions: id => {priority, render}.
	 *
	 * @var array<string, array{priority: int, render: callable}>
	 */
	private static array $contributions = [];

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'register_script' ] );
		add_action( 'wp_footer', [ $this, 'print' ], 15 );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'ajax_report' ] );
	}

	/**
	 * Add markup to the block-checkout slot.
	 *
	 * Lower priority = printed first; the entry printed last sits closest to
	 * the place-order button (notice 10, GARAN list 20). A contribution with an
	 * existing id replaces the earlier one.
	 *
	 * @param string   $id       Unique contribution id.
	 * @param int      $priority Order (ascending).
	 * @param callable $render   Returns the HTML (may return '').
	 * @return void
	 */
	public static function contribute( string $id, int $priority, callable $render ): void {
		self::$contributions[ $id ] = [
			'priority' => $priority,
			'render'   => $render,
		];
	}

	/**
	 * Whether the current request is the block (React) checkout page.
	 *
	 * The order-pay and order-received endpoints fall back to classic output
	 * even on a block checkout page, so they are excluded.
	 *
	 * @return bool
	 */
	public static function is_block_checkout(): bool {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return false;
		}
		if ( is_wc_endpoint_url( 'order-received' ) || is_wc_endpoint_url( 'order-pay' ) ) {
			return false;
		}

		// The current page decides: a classic [woocommerce_checkout] page is a
		// checkout too, even when the configured checkout page uses the block.
		$page = get_post( (int) get_queried_object_id() );
		if ( ! $page instanceof \WP_Post ) {
			$page = get_post( wc_get_page_id( 'checkout' ) );
		}

		return $page instanceof \WP_Post && has_block( 'woocommerce/checkout', $page );
	}

	/**
	 * Register the inserter script (enqueued only when a slot is printed).
	 *
	 * @return void
	 */
	public function register_script(): void {
		wp_register_script(
			self::HANDLE,
			ELALLAS_FOR_WOO_URL . 'assets/js/compliance-checkout.js',
			[],
			ELALLAS_FOR_WOO_VERSION,
			true
		);
	}

	/**
	 * Print the collected contributions into the hidden template.
	 *
	 * @return void
	 */
	public function print(): void {
		if ( [] === self::$contributions || ! self::is_block_checkout() ) {
			return;
		}

		$html = self::render_contributions();
		if ( '' === trim( $html ) ) {
			return;
		}

		wp_enqueue_script( self::HANDLE );

		/**
		 * Filters the CSS selectors the block-checkout slot is anchored to.
		 *
		 * The slot is inserted before the outer wrapper of the first match.
		 *
		 * @param array<int, string> $anchors Selectors, most specific first.
		 */
		$anchors = (array) apply_filters(
			'elallas_checkout_block_anchors',
			[
				'.wp-block-woocommerce-checkout-actions-block',
				'.wc-block-checkout__actions_row',
				'.wc-block-components-checkout-place-order-button',
			]
		);
		$anchors = array_values( array_filter( array_map( 'strval', $anchors ) ) );

		printf(
			'<template id="elallas-checkout-slot" data-anchors="%1$s"%2$s>%3$s</template>',
			esc_attr( (string) wp_json_encode( $anchors ) ),
			self::slot_check_attributes(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in slot_check_attributes().
			$html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Contributions are escaped by their own renderers/templates.
		);
	}

	/**
	 * Store (or clear) the admin slot-check result.
	 *
	 * @return void
	 */
	public function ajax_report(): void {
		check_ajax_referer( self::AJAX_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( null, 403 );
		}

		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		if ( 'fallback' === $status ) {
			set_transient( self::FALLBACK_TRANSIENT, time(), 30 * DAY_IN_SECONDS );
		} else {
			delete_transient( self::FALLBACK_TRANSIENT );
		}

		wp_send_json_success();
	}

	/**
	 * Render every contribution in priority order.
	 *
	 * @return string
	 */
	private static function render_contributions(): string {
		$items = self::$contributions;
		uasort(
			$items,
			static function ( array $a, array $b ): int {
				return $a['priority'] <=> $b['priority'];
			}
		);

		$html = '';
		foreach ( $items as $item ) {
			$html .= (string) call_user_func( $item['render'] );
		}

		return $html;
	}

	/**
	 * Extra attributes for the admin "block checkout check" (?elallas_slot_check=1).
	 *
	 * @return string Escaped attribute string ('' for everyone else).
	 */
	private static function slot_check_attributes(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display switch; the report itself is nonce-protected.
		if ( ! isset( $_GET['elallas_slot_check'] ) || ! current_user_can( 'manage_woocommerce' ) ) {
			return '';
		}

		return sprintf(
			' data-slot-check="1" data-ajax-url="%1$s" data-nonce="%2$s" data-msg-ok="%3$s" data-msg-fallback="%4$s"',
			esc_url( admin_url( 'admin-ajax.php' ) ),
			esc_attr( wp_create_nonce( self::AJAX_ACTION ) ),
			esc_attr__( 'Elállás: az értesítés és a GARAN a blokkos pénztárban a rendelés gomb előtt jelenik meg.', 'elallas-for-woo' ),
			esc_attr__( 'Elállás: a rendelés gomb horgonya nem található, tartalék-hely lépett életbe. Ellenőrizd az elallas_checkout_block_anchors szelektorait.', 'elallas-for-woo' )
		);
	}
}
