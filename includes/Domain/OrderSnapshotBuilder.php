<?php
/**
 * Order snapshot builder.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Domain;

use LightweightPlugins\Elallas\Woo\OrderAdapter;

/**
 * Builds case-item snapshots from a live order.
 *
 * Snapshots keep a withdrawal reconstructable even if the product name, price
 * or order data later changes.
 */
final class OrderSnapshotBuilder {

	/**
	 * Build snapshot rows for the selected items.
	 *
	 * @param \WC_Order       $order    Order.
	 * @param array<int, int> $selected Map of order_item_id => qty_withdrawn (empty = all items, full qty).
	 * @return array<int, array<string, mixed>>
	 */
	public static function build( \WC_Order $order, array $selected = [] ): array {
		$rows = [];

		foreach ( OrderAdapter::items( $order ) as $item ) {
			$item_id = (int) $item['order_item_id'];

			if ( ! empty( $selected ) && ! isset( $selected[ $item_id ] ) ) {
				continue;
			}

			$qty_withdrawn = empty( $selected )
				? (int) $item['qty_ordered']
				: min( (int) $selected[ $item_id ], (int) $item['qty_ordered'] );

			if ( $qty_withdrawn <= 0 ) {
				continue;
			}

			[ $flag, $note ] = self::eligibility( (int) $item['product_id'] );

			$rows[] = array_merge(
				$item,
				[
					'qty_withdrawn'    => $qty_withdrawn,
					'eligibility_flag' => $flag,
					'eligibility_note' => $note,
				]
			);
		}

		return $rows;
	}

	/**
	 * Per-product eligibility flag/note from merchant exception settings.
	 *
	 * @param int $product_id Product ID.
	 * @return array{0: string, 1: string}
	 */
	private static function eligibility( int $product_id ): array {
		if ( class_exists( ProductExclusion::class ) ) {
			[ $excluded, $label ] = ProductExclusion::evaluate( $product_id );

			if ( $excluded ) {
				return [ 'excepted', $label ];
			}
		}

		return [ 'eligible', '' ];
	}
}
