<?php
/**
 * WooCommerce front-end hooks (order details button, email link).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Woo;

use LightweightPlugins\Elallas\Options;
use LightweightPlugins\Elallas\Frontend\Shortcodes;
use LightweightPlugins\Elallas\Integrations\Multilingual;
use LightweightPlugins\Elallas\Domain\EligibilityChecker;

/**
 * Surfaces the withdrawal button on order views and order emails.
 */
final class Hooks {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( Options::get( 'display_order_details' ) ) {
			add_action( 'woocommerce_order_details_after_order_table', [ $this, 'order_details_button' ], 20 );
		}

		if ( Options::get( 'display_order_email' ) ) {
			add_action( 'woocommerce_email_after_order_table', [ $this, 'email_link' ], 20, 1 );
		}
	}

	/**
	 * Render the button under the order details table.
	 *
	 * @param \WC_Order $order Order.
	 * @return void
	 */
	public function order_details_button( \WC_Order $order ): void {
		if ( ! $this->is_eligible( $order ) ) {
			return;
		}

		$url = Shortcodes::page_url();

		if ( '' === $url ) {
			return;
		}

		printf(
			'<p class="elallas-order-action"><a class="button elallas-button" href="%1$s">%2$s</a></p>',
			esc_url( add_query_arg( 'order', $order->get_order_number(), $url ) ),
			esc_html( Multilingual::translate_option_string( 'button_label' ) )
		);
	}

	/**
	 * Add a link to the withdrawal page in order emails.
	 *
	 * @param \WC_Order $order Order.
	 * @return void
	 */
	public function email_link( \WC_Order $order ): void {
		if ( ! $this->is_eligible( $order ) ) {
			return;
		}

		$url = Shortcodes::page_url();

		if ( '' === $url ) {
			return;
		}

		printf(
			'<p style="margin-top:16px;"><a href="%1$s">%2$s</a></p>',
			esc_url( $url ),
			esc_html( Multilingual::translate_option_string( 'button_label' ) )
		);
	}

	/**
	 * Lightweight eligibility check (status only) for display gating.
	 *
	 * @param \WC_Order $order Order.
	 * @return bool
	 */
	private function is_eligible( \WC_Order $order ): bool {
		if ( ! Options::get( 'enabled' ) ) {
			return false;
		}

		$allowed = (array) Options::get( 'eligible_statuses', [ 'processing', 'completed' ] );

		return in_array( $order->get_status(), $allowed, true );
	}
}
