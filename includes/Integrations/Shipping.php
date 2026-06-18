<?php
/**
 * Shipping integration.
 *
 * Best-effort resolution of a delivery/shipment date from common HU/EU carriers
 * and WooCommerce Shipment Tracking, read from order meta. All reads are guarded
 * so a missing carrier plugin never causes a fatal.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Integrations;

/**
 * Resolves a delivery date for a withdrawal case from carrier order meta.
 */
final class Shipping {

	/**
	 * Order meta keys that may carry a delivery/shipment date, in priority order.
	 *
	 * @var array<int, string>
	 */
	private const DATE_META_KEYS = [
		'_gls_delivery_date',
		'_gls_shipment_date',
		'_packeta_packet_status_date',
		'_foxpost_shipping_date',
		'_mpl_delivery_date',
		'_dpd_delivery_date',
		'_dpd_shipment_date',
		'_delivery_date',
	];

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_filter( 'elallas_delivery_date', [ $this, 'resolve_delivery_date' ], 10, 2 );
	}

	/**
	 * Resolve a delivery date from carrier meta, falling back to the passed value.
	 *
	 * @param string|null $date  Currently resolved date (may be null).
	 * @param \WC_Order    $order Order to inspect.
	 * @return string|null A 'Y-m-d H:i:s' date when found, otherwise the passed value.
	 */
	public function resolve_delivery_date( ?string $date, \WC_Order $order ): ?string {
		$found = $this->from_shipment_tracking( $order );

		if ( null === $found ) {
			$found = $this->from_carrier_meta( $order );
		}

		return null !== $found ? $found : $date;
	}

	/**
	 * Read a shipped date from the WooCommerce Shipment Tracking meta.
	 *
	 * @param \WC_Order $order Order to inspect.
	 * @return string|null Normalized date or null.
	 */
	private function from_shipment_tracking( \WC_Order $order ): ?string {
		$items = $order->get_meta( '_wc_shipment_tracking_items', true );

		if ( ! is_array( $items ) ) {
			return null;
		}

		foreach ( $items as $item ) {
			$shipped = is_array( $item ) ? ( $item['date_shipped'] ?? '' ) : '';

			if ( '' !== $shipped ) {
				return $this->normalize( $shipped );
			}
		}

		return null;
	}

	/**
	 * Read a delivery/shipment date from common carrier meta keys.
	 *
	 * @param \WC_Order $order Order to inspect.
	 * @return string|null Normalized date or null.
	 */
	private function from_carrier_meta( \WC_Order $order ): ?string {
		foreach ( self::DATE_META_KEYS as $key ) {
			$value = $order->get_meta( $key, true );

			if ( is_string( $value ) && '' !== $value ) {
				return $this->normalize( $value );
			}

			if ( is_numeric( $value ) && (int) $value > 0 ) {
				return gmdate( 'Y-m-d H:i:s', (int) $value );
			}
		}

		return null;
	}

	/**
	 * Normalize an arbitrary date string (or timestamp) to 'Y-m-d H:i:s'.
	 *
	 * @param string $value Raw date value.
	 * @return string|null Normalized date or null when unparseable.
	 */
	private function normalize( string $value ): ?string {
		$timestamp = is_numeric( $value ) ? (int) $value : strtotime( $value );

		if ( false === $timestamp || $timestamp <= 0 ) {
			return null;
		}

		return gmdate( 'Y-m-d H:i:s', $timestamp );
	}
}
