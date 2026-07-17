<?php
/**
 * Withdrawal form renderer.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Frontend;

use LightweightPlugins\Elallas\Security\Honeypot;
use LightweightPlugins\Elallas\Options;

/**
 * Drives the front-end flow: processes the request, renders the right step.
 */
final class WithdrawalForm {

	/**
	 * Render the form for the current request state.
	 *
	 * @param array<string, mixed> $atts Shortcode/block attributes.
	 * @return string
	 */
	public static function render( array $atts = [] ): string {
		if ( ! Options::get( 'enabled' ) ) {
			return '<div class="elallas-form elallas-disabled"><p>' . esc_html__( 'Az online elállási funkció jelenleg nem érhető el.', 'elallas-for-woo' ) . '</p></div>';
		}

		$state = ( new FormHandler() )->handle();
		$view  = (string) $state['view'];
		$data  = (array) $state['data'];

		if ( 'identify' === $view ) {
			$data += self::identify_prefill();
		}

		$data['atts']        = $atts;
		$data['form_action'] = esc_url( remove_query_arg( [ 'elallas_done' ] ) );
		$data['nonce_field'] = wp_nonce_field( 'elallas_form', 'elallas_nonce', true, false );
		$data['honeypot']    = Honeypot::field();

		$html = TemplateLoader::render( 'frontend/' . $view . '.php', $data );

		return '<div class="elallas-form elallas-step-' . esc_attr( $view ) . '">' . $html . '</div>';
	}

	/**
	 * Prefill data for the identify step: order from ?order=, and the logged-in
	 * user's email + eligible orders (so they can pick instead of typing).
	 *
	 * @return array<string, mixed>
	 */
	private static function identify_prefill(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order_param = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : '';

		$data = [
			'prefill_order' => $order_param,
			'prefill_email' => '',
			'user_orders'   => [],
		];

		if ( ! is_user_logged_in() || ! function_exists( 'wc_get_orders' ) ) {
			return $data;
		}

		$data['prefill_email'] = (string) wp_get_current_user()->user_email;

		$orders = wc_get_orders(
			[
				'customer_id' => get_current_user_id(),
				'status'      => (array) Options::get( 'eligible_statuses', [ 'processing', 'completed' ] ),
				'limit'       => 20,
				'orderby'     => 'date',
				'order'       => 'DESC',
			]
		);

		foreach ( $orders as $order ) {
			if ( $order instanceof \WC_Order ) {
				$data['user_orders'][] = [
					'number' => (string) $order->get_order_number(),
					'date'   => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d' ) : '',
				];
			}
		}

		return $data;
	}
}
