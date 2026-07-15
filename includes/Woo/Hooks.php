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
			add_action( 'woocommerce_email_after_order_table', [ $this, 'email_link' ], 20, 2 );
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
	 * Add the withdrawal link (with optional intro text) to order emails.
	 *
	 * The merchant-editable "Rendelési e-mail elállási szövege" option wraps the
	 * link in real copy: a `{link}` placeholder is replaced with a smart link to
	 * the configured withdrawal page (pre-filled with this order), and when the
	 * placeholder is absent the link is appended after the text. An empty option
	 * falls back to a bare link.
	 *
	 * @param \WC_Order $order         Order.
	 * @param bool      $sent_to_admin Whether this is the admin copy of the e-mail.
	 * @return void
	 */
	public function email_link( \WC_Order $order, bool $sent_to_admin = false ): void {
		// The withdrawal CTA is customer-facing; don't append it to admin order e-mails.
		if ( $sent_to_admin || ! $this->is_eligible( $order ) ) {
			return;
		}

		$url = Shortcodes::page_url();

		if ( '' === $url ) {
			return;
		}

		// Pre-fill the order on the withdrawal page (matches the order-details button).
		$anchor = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( add_query_arg( 'order', $order->get_order_number(), $url ) ),
			esc_html( Multilingual::translate_option_string( 'button_label' ) )
		);

		$text = Multilingual::translate_option_string( 'email_order_text' );

		if ( '' === $text ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $anchor is built from esc_url() + esc_html().
			echo '<p style="margin-top:16px;">' . $anchor . '</p>';
			return;
		}

		// Escape the admin text, then substitute {link} with the (already-safe)
		// anchor, or append the anchor when the placeholder is missing.
		if ( str_contains( $text, '{link}' ) ) {
			$html = implode( $anchor, array_map( 'esc_html', explode( '{link}', $text ) ) );
		} else {
			$html = esc_html( $text ) . ' ' . $anchor;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- text is esc_html'd above; anchor is esc_url() + esc_html().
		echo '<div class="elallas-email-cta" style="margin-top:16px;">' . nl2br( $html ) . '</div>';
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
