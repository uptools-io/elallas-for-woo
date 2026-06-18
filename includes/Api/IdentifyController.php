<?php
/**
 * Identify-order REST controller.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Api;

use LightweightPlugins\Elallas\Options;
use LightweightPlugins\Elallas\Data\DefaultTexts;
use LightweightPlugins\Elallas\Domain\EligibilityChecker;
use LightweightPlugins\Elallas\Domain\EligibilityResult;
use LightweightPlugins\Elallas\Security\RateLimiter;
use LightweightPlugins\Elallas\Woo\OrderAdapter;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Validates an order + email pair and returns its withdrawable line items.
 *
 * All failures return a single neutral response so the endpoint never leaks
 * which field (order number / email / status) was wrong.
 */
final class IdentifyController {

	/**
	 * Public permission (validation and rate-limiting happen inside).
	 *
	 * @return bool
	 */
	public function permission_public(): bool {
		return true;
	}

	/**
	 * Identify an order for withdrawal.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function handle( WP_REST_Request $request ): WP_REST_Response {
		$order_number = sanitize_text_field( (string) $request->get_param( 'order_number' ) );

		if ( ! Options::get( 'enabled' ) || RateLimiter::too_many( 'rest_identify' ) || RateLimiter::too_many_global( 'order_' . $order_number ) ) {
			return $this->neutral();
		}

		$email = sanitize_email( (string) $request->get_param( 'email' ) );

		$order  = OrderAdapter::get_order_by_number( $order_number );
		$result = $order ? ( new EligibilityChecker() )->check( $order, $email ) : null;

		if ( null === $order || ! $result instanceof EligibilityResult || ! $result->eligible ) {
			return $this->neutral();
		}

		return rest_ensure_response(
			[
				'eligible'        => true,
				'deadline_status' => $result->deadline_status,
				'items'           => $this->items( $order ),
			]
		);
	}

	/**
	 * Map order line items to the public response shape.
	 *
	 * @param \WC_Order $order Order.
	 * @return array<int, array<string, mixed>>
	 */
	private function items( \WC_Order $order ): array {
		$items = [];

		foreach ( OrderAdapter::items( $order ) as $item ) {
			$items[] = [
				'order_item_id' => (int) $item['order_item_id'],
				'name'          => (string) $item['product_name_snapshot'],
				'qty'           => (int) $item['qty_ordered'],
			];
		}

		return $items;
	}

	/**
	 * Neutral "not eligible" response (never reveals the failing field).
	 *
	 * @return WP_REST_Response
	 */
	private function neutral(): WP_REST_Response {
		return rest_ensure_response(
			[
				'eligible' => false,
				'message'  => DefaultTexts::neutral_error(),
			]
		);
	}
}
