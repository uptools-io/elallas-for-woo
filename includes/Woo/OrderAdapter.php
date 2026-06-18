<?php
/**
 * WooCommerce order adapter (HPOS-safe).
 *
 * All order access goes through WooCommerce CRUD so the plugin works
 * identically with and without High-Performance Order Storage.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Woo;

/**
 * Reads order data via WooCommerce CRUD.
 */
final class OrderAdapter {

	/**
	 * Get an order object.
	 *
	 * @param int $order_id Order ID.
	 * @return \WC_Order|null
	 */
	public static function get_order( int $order_id ): ?\WC_Order {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return null;
		}

		$order = wc_get_order( $order_id );

		return $order instanceof \WC_Order ? $order : null;
	}

	/**
	 * Get the order by its display number (falls back to ID).
	 *
	 * @param string $number Order number as entered by the customer.
	 * @return \WC_Order|null
	 */
	public static function get_order_by_number( string $number ): ?\WC_Order {
		$number = trim( $number );
		$id     = (int) preg_replace( '/[^0-9]/', '', $number );

		return $id > 0 ? self::get_order( $id ) : null;
	}

	/**
	 * Get the customer email on the order.
	 *
	 * @param \WC_Order $order Order.
	 * @return string
	 */
	public static function customer_email( \WC_Order $order ): string {
		return (string) $order->get_billing_email();
	}

	/**
	 * Whether the supplied email matches the order's billing email.
	 *
	 * @param \WC_Order $order Order.
	 * @param string    $email Candidate email.
	 * @return bool
	 */
	public static function email_matches( \WC_Order $order, string $email ): bool {
		return strtolower( trim( $email ) ) === strtolower( self::customer_email( $order ) );
	}

	/**
	 * Billing company name.
	 *
	 * @param \WC_Order $order Order.
	 * @return string
	 */
	public static function company( \WC_Order $order ): string {
		return (string) $order->get_billing_company();
	}

	/**
	 * Best-effort VAT / tax number from common HU invoicing plugins.
	 *
	 * @param \WC_Order $order Order.
	 * @return string
	 */
	public static function vat_number( \WC_Order $order ): string {
		$keys = [ '_billing_vat', 'billing_vat', '_vat_number', 'vat_number', '_szamlazz_taxnumber', '_billing_tax_number' ];

		foreach ( $keys as $key ) {
			$value = $order->get_meta( $key );
			if ( ! empty( $value ) ) {
				return (string) $value;
			}
		}

		return '';
	}

	/**
	 * Relevant order dates as MySQL strings (or null).
	 *
	 * @param \WC_Order $order Order.
	 * @return array{created: ?string, completed: ?string, delivery: ?string}
	 */
	public static function dates( \WC_Order $order ): array {
		$created   = $order->get_date_created();
		$completed = $order->get_date_completed();
		$paid      = $order->get_date_paid();
		$delivery  = $order->get_meta( '_lw_elallas_delivery_date' );
		$delivery  = ! empty( $delivery ) ? (string) $delivery : null;

		/**
		 * Filter the delivery date used for the withdrawal deadline.
		 *
		 * Shipping integrations (GLS, Packeta, Foxpost, MPL, DPD, Shipment
		 * Tracking) hook this to supply a carrier delivery date.
		 *
		 * @param string|null $delivery Delivery date (Y-m-d H:i:s) or null.
		 * @param \WC_Order    $order    Order.
		 */
		$delivery = apply_filters( 'elallas_delivery_date', $delivery, $order );

		return [
			'created'   => $created ? $created->date( 'Y-m-d H:i:s' ) : null,
			'completed' => $completed ? $completed->date( 'Y-m-d H:i:s' ) : ( $paid ? $paid->date( 'Y-m-d H:i:s' ) : null ),
			'delivery'  => ! empty( $delivery ) ? (string) $delivery : null,
		];
	}

	/**
	 * Normalised line items.
	 *
	 * @param \WC_Order $order Order.
	 * @return array<int, array<string, mixed>>
	 */
	public static function items( \WC_Order $order ): array {
		$items = [];

		foreach ( $order->get_items() as $item_id => $item ) {
			if ( ! $item instanceof \WC_Order_Item_Product ) {
				continue;
			}

			$product = $item->get_product();

			$items[] = [
				'order_item_id'         => (int) $item_id,
				'product_id'            => (int) $item->get_product_id(),
				'variation_id'          => (int) $item->get_variation_id(),
				'product_name_snapshot' => (string) $item->get_name(),
				'sku_snapshot'          => $product ? (string) $product->get_sku() : '',
				'qty_ordered'           => (int) $item->get_quantity(),
				'line_total_snapshot'   => (string) $order->get_line_total( $item, true, false ),
				'tax_total_snapshot'    => (string) $order->get_line_tax( $item ),
			];
		}

		return $items;
	}
}
