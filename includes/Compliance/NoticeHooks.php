<?php
/**
 * Front-end placements of the harmonised legal-guarantee notice.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

use LightweightPlugins\Elallas\Options;

/**
 * Option-gated placements: product page, header, footer, classic checkout,
 * order-pay, order view, and the block-checkout slot contribution.
 *
 * Never depends on the withdrawal module's `enabled` / `eligible_statuses`.
 */
final class NoticeHooks {

	/**
	 * Register hooks.
	 */
	public function __construct() {
		if ( Options::get( 'notice_display_product' ) ) {
			ProductPlacement::add( 'notice', 35, 20, [ $this, 'product_html' ] );
		}

		if ( Options::get( 'notice_display_header' ) ) {
			add_action( 'wp_body_open', [ $this, 'render_header' ], 10 );
		}

		if ( Options::get( 'notice_display_footer' ) ) {
			add_action( 'wp_footer', [ $this, 'render_footer' ], 5 );
		}

		if ( Options::get( 'notice_display_checkout' ) ) {
			add_action( 'woocommerce_review_order_before_submit', [ $this, 'render_checkout' ], 5 );
			add_action( 'woocommerce_pay_order_before_submit', [ $this, 'render_order_pay' ], 5 );
			add_action( 'woocommerce_order_details_after_order_table', [ $this, 'render_order' ], 15 );
			add_action( 'wp', [ $this, 'queue_checkout_block' ] );
		}
	}

	/**
	 * Product page notice (goods only).
	 *
	 * @param \WC_Product $product Product.
	 * @return string
	 */
	public function product_html( \WC_Product $product ): string {
		if ( ! GoodsScope::applies_to_product( $product ) ) {
			return '';
		}

		return NoticeRenderer::render( [ 'context' => 'product' ] );
	}

	/**
	 * Header bar (themes supporting wp_body_open).
	 *
	 * @return void
	 */
	public function render_header(): void {
		$this->print_bar( 'header' );
	}

	/**
	 * Footer bar.
	 *
	 * @return void
	 */
	public function render_footer(): void {
		$this->print_bar( 'footer' );
	}

	/**
	 * Classic checkout, right before the place-order button.
	 *
	 * @return void
	 */
	public function render_checkout(): void {
		if ( GoodsScope::cart_has_goods() ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output is escaped.
			echo NoticeRenderer::render( [ 'context' => 'checkout' ] );
		}
	}

	/**
	 * Order-pay page, right before the pay button (goods of the order, B2B by order).
	 *
	 * @return void
	 */
	public function render_order_pay(): void {
		$order = wc_get_order( absint( get_query_var( 'order-pay' ) ) );

		if ( ! $order instanceof \WC_Order || ! GoodsScope::order_has_goods( $order ) ) {
			return;
		}

		$html = NoticeRenderer::render(
			[
				'context' => 'orderpay',
				'order'   => $order,
			]
		);

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output is escaped.
	}

	/**
	 * Order view (thank-you page, My Account → order), before the withdrawal button.
	 *
	 * @param mixed $order Order.
	 * @return void
	 */
	public function render_order( $order ): void {
		if ( ! $order instanceof \WC_Order || ! GoodsScope::order_has_goods( $order ) ) {
			return;
		}

		$html = NoticeRenderer::render(
			[
				'context' => 'order',
				'order'   => $order,
			]
		);

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output is escaped.
	}

	/**
	 * Contribute the notice to the block-checkout slot (printed before the button).
	 *
	 * @return void
	 */
	public function queue_checkout_block(): void {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_wc_endpoint_url() ) {
			return;
		}

		CheckoutSlot::contribute(
			'notice',
			10,
			static function (): string {
				return GoodsScope::cart_has_goods() ? NoticeRenderer::render( [ 'context' => 'slot' ] ) : '';
			}
		);
	}

	/**
	 * Print a header/footer bar.
	 *
	 * @param string $context header|footer.
	 * @return void
	 */
	private function print_bar( string $context ): void {
		$html = NoticeRenderer::render( [ 'context' => $context ] );

		if ( '' === $html ) {
			return;
		}

		printf(
			'<div class="elallas-notice-bar elallas-notice-bar--%1$s">%2$s</div>',
			esc_attr( $context ),
			$html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Template output is escaped.
		);
	}
}
