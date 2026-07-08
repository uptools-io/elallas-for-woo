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
	 * Resolve an order from the number the customer sees and types.
	 *
	 * With an order-numbering plugin such as WooCommerce Sequential Order
	 * Numbers (Pro), the visible order number is NOT the WooCommerce order ID
	 * (e.g. the customer sees "5" while the order's WP ID is 30). Resolving the
	 * typed value straight to an ID would then never find the order, so we ask
	 * the numbering plugin to map the number to its order first, and only fall
	 * back to treating the value as the native order ID.
	 *
	 * @param string $number Order number as entered by the customer.
	 * @return \WC_Order|null
	 */
	public static function get_order_by_number( string $number ): ?\WC_Order {
		$number = ltrim( trim( $number ), '#' );

		if ( '' === $number ) {
			return null;
		}

		/**
		 * Resolve a customer-entered order number to a WooCommerce order ID.
		 *
		 * Lets any order-numbering plugin provide the lookup. Return 0 to fall
		 * through to the built-in resolution.
		 *
		 * @param int    $order_id Resolved order ID (0 = unresolved).
		 * @param string $number   The order number as entered by the customer.
		 */
		$order_id = (int) apply_filters( 'elallas_resolve_order_number', 0, $number );

		if ( $order_id > 0 ) {
			return self::get_order( $order_id );
		}

		// WooCommerce Sequential Order Numbers (Pro / free): map the visible
		// number to its order. The helper also falls back to a native order ID
		// for legacy orders created while the plugin was inactive.
		foreach ( [ 'wc_seq_order_number_pro', 'wc_seq_order_number' ] as $resolver ) {
			if ( ! function_exists( $resolver ) ) {
				continue;
			}

			$instance = $resolver();

			if ( is_object( $instance ) && method_exists( $instance, 'find_order_by_order_number' ) ) {
				$order_id = (int) $instance->find_order_by_order_number( $number );

				return $order_id > 0 ? self::get_order( $order_id ) : null;
			}
		}

		// No numbering plugin: the entered value is the native WooCommerce order ID.
		$id = (int) preg_replace( '/[^0-9]/', '', $number );

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
