<?php
/**
 * Refund calculator input from a live order.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Woo;

use LightweightPlugins\Elallas\Database\CaseItemRepository;
use LightweightPlugins\Elallas\Models\WithdrawalCase;

/**
 * Turns a case and its WooCommerce order into RefundCalculator lines, plus the
 * display fields the refund form needs (name, restockable).
 */
final class RefundLines {

	/**
	 * Refund meta linking a WooCommerce refund to the case it was recorded for.
	 */
	public const CASE_META = '_lw_elallas_case_id';

	/**
	 * Product lines of the case followed by the order's shipping and fee lines.
	 *
	 * @param WithdrawalCase $case  Case.
	 * @param \WC_Order      $order Order.
	 * @return array<int, array<string, mixed>>
	 */
	public static function for_case( WithdrawalCase $case, \WC_Order $order ): array {
		$lines         = [];
		$case_refunded = self::case_refunded( $order, $case->id );

		foreach ( CaseItemRepository::for_case( $case->id ) as $case_item ) {
			$item = $order->get_item( $case_item->order_item_id );

			if ( $item instanceof \WC_Order_Item_Product ) {
				$line                      = self::product_line( $order, $item, $case_item->qty_withdrawn, $case_item->is_excepted() );
				$line['qty_case_refunded'] = $case_refunded[ $item->get_id() ] ?? 0;
				$lines[]                   = $line;
			}
		}

		foreach ( $order->get_items( [ 'shipping', 'fee' ] ) as $item ) {
			if ( $item instanceof \WC_Order_Item_Shipping || $item instanceof \WC_Order_Item_Fee ) {
				$lines[] = self::cost_line( $order, $item );
			}
		}

		return $lines;
	}

	/**
	 * Quantities refunded for this case so far, per order item ID.
	 *
	 * Read from the refunds themselves, so deleting a refund in WooCommerce
	 * makes its quantity refundable again.
	 *
	 * @param \WC_Order $order   Order.
	 * @param int       $case_id Case ID.
	 * @return array<int, int>
	 */
	private static function case_refunded( \WC_Order $order, int $case_id ): array {
		$qty = [];

		foreach ( $order->get_refunds() as $refund ) {
			if ( (int) $refund->get_meta( self::CASE_META, true ) !== $case_id ) {
				continue;
			}

			foreach ( $refund->get_items() as $refund_item ) {
				$id         = (int) $refund_item->get_meta( '_refunded_item_id', true );
				$qty[ $id ] = ( $qty[ $id ] ?? 0 ) + abs( (int) $refund_item->get_quantity() );
			}
		}

		return $qty;
	}

	/**
	 * Product line.
	 *
	 * @param \WC_Order              $order         Order.
	 * @param \WC_Order_Item_Product $item          Order item.
	 * @param int                    $qty_withdrawn Withdrawn quantity.
	 * @param bool                   $excepted      Whether the product is excepted from withdrawal.
	 * @return array<string, mixed>
	 */
	private static function product_line( \WC_Order $order, \WC_Order_Item_Product $item, int $qty_withdrawn, bool $excepted ): array {
		$product = $item->get_product();

		return [
			'type'          => 'line_item',
			'item_id'       => $item->get_id(),
			'name'          => $item->get_name(),
			'qty_ordered'   => $item->get_quantity(),
			'qty_withdrawn' => $qty_withdrawn,
			'qty_refunded'  => abs( (int) $order->get_qty_refunded_for_item( $item->get_id() ) ),
			'total'         => (string) $item->get_total(),
			'taxes'         => self::taxes( $item ),
			'excepted'      => $excepted,
			'restockable'   => $product instanceof \WC_Product && $product->managing_stock() && '' !== (string) $item->get_meta( '_reduced_stock', true ),
		];
	}

	/**
	 * Shipping or fee line.
	 *
	 * @param \WC_Order                                  $order Order.
	 * @param \WC_Order_Item_Shipping|\WC_Order_Item_Fee $item  Order item.
	 * @return array<string, mixed>
	 */
	private static function cost_line( \WC_Order $order, \WC_Order_Item_Shipping|\WC_Order_Item_Fee $item ): array {
		$type         = $item->get_type();
		$taxes        = self::taxes( $item );
		$tax_refunded = 0.0;

		foreach ( array_keys( $taxes ) as $rate_id ) {
			$tax_refunded += (float) $order->get_tax_refunded_for_item( $item->get_id(), $rate_id, $type );
		}

		return [
			'type'           => $type,
			'item_id'        => $item->get_id(),
			'name'           => $item->get_name(),
			'total'          => (string) $item->get_total(),
			'taxes'          => $taxes,
			'total_refunded' => (string) $order->get_total_refunded_for_item( $item->get_id(), $type ),
			'tax_refunded'   => (string) $tax_refunded,
		];
	}

	/**
	 * Line tax per rate ID.
	 *
	 * @param \WC_Order_Item $item Order item.
	 * @return array<int, string>
	 */
	private static function taxes( \WC_Order_Item $item ): array {
		$taxes = method_exists( $item, 'get_taxes' ) ? $item->get_taxes() : [];

		return array_map( 'strval', array_filter( (array) ( $taxes['total'] ?? [] ), 'is_numeric' ) );
	}
}
