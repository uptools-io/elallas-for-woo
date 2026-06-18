<?php
/**
 * B2B / B2C detection.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Domain;

use LightweightPlugins\Elallas\Woo\OrderAdapter;

/**
 * Best-effort detection of whether an order is a business (B2B) purchase.
 *
 * WooCommerce cannot reliably tell consumer from business apart, so this is a
 * heuristic the merchant can override per case.
 */
final class B2BDetector {

	/**
	 * Whether an order looks like a business purchase.
	 *
	 * @param \WC_Order $order Order.
	 * @return bool
	 */
	public static function is_b2b( \WC_Order $order ): bool {
		$company    = OrderAdapter::company( $order );
		$vat_number = OrderAdapter::vat_number( $order );

		$is_b2b = '' !== trim( $company ) || '' !== trim( $vat_number );

		/**
		 * Filter the B2B detection result.
		 *
		 * @param bool      $is_b2b Whether the order is business.
		 * @param \WC_Order $order  Order.
		 */
		return (bool) apply_filters( 'elallas_is_order_b2b', $is_b2b, $order );
	}

	/**
	 * Human-readable detection reason.
	 *
	 * @param \WC_Order $order Order.
	 * @return string
	 */
	public static function reason( \WC_Order $order ): string {
		if ( '' !== trim( OrderAdapter::vat_number( $order ) ) ) {
			return __( 'Adószám mező kitöltve', 'elallas-for-woo' );
		}

		if ( '' !== trim( OrderAdapter::company( $order ) ) ) {
			return __( 'Cégnév megadva', 'elallas-for-woo' );
		}

		return __( 'Nincs B2B jelző', 'elallas-for-woo' );
	}
}
