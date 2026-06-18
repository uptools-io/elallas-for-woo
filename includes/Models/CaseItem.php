<?php
/**
 * Case item entity (DTO).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Models;

/**
 * Typed representation of a row in the case items table (order snapshot).
 */
final class CaseItem {

	public int $id                     = 0;
	public int $case_id                = 0;
	public int $order_item_id          = 0;
	public int $product_id             = 0;
	public int $variation_id           = 0;
	public string $product_name_snapshot = '';
	public string $sku_snapshot        = '';
	public int $qty_ordered            = 0;
	public int $qty_withdrawn          = 0;
	public string $line_total_snapshot = '0';
	public string $tax_total_snapshot  = '0';
	public string $eligibility_flag    = 'eligible';
	public string $eligibility_note    = '';

	/**
	 * Build from a DB row.
	 *
	 * @param object|array<string, mixed> $row Database row.
	 * @return self
	 */
	public static function from_row( object|array $row ): self {
		$data     = (array) $row;
		$instance = new self();
		$ints     = [ 'id', 'case_id', 'order_item_id', 'product_id', 'variation_id', 'qty_ordered', 'qty_withdrawn' ];

		foreach ( $data as $key => $value ) {
			if ( ! property_exists( $instance, $key ) ) {
				continue;
			}

			$instance->$key = in_array( $key, $ints, true ) ? (int) $value : (string) $value;
		}

		return $instance;
	}
}
