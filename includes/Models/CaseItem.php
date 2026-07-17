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

	/**
	 * Case item row primary key.
	 *
	 * @var int
	 */
	public int $id = 0;

	/**
	 * Parent case ID.
	 *
	 * @var int
	 */
	public int $case_id = 0;

	/**
	 * Related WooCommerce order item ID.
	 *
	 * @var int
	 */
	public int $order_item_id = 0;

	/**
	 * Product ID.
	 *
	 * @var int
	 */
	public int $product_id = 0;

	/**
	 * Product variation ID.
	 *
	 * @var int
	 */
	public int $variation_id = 0;

	/**
	 * Product name captured at order time.
	 *
	 * @var string
	 */
	public string $product_name_snapshot = '';

	/**
	 * Product SKU captured at order time.
	 *
	 * @var string
	 */
	public string $sku_snapshot = '';

	/**
	 * Quantity ordered.
	 *
	 * @var int
	 */
	public int $qty_ordered = 0;

	/**
	 * Quantity withdrawn.
	 *
	 * @var int
	 */
	public int $qty_withdrawn = 0;

	/**
	 * Line total captured at order time.
	 *
	 * @var string
	 */
	public string $line_total_snapshot = '0';

	/**
	 * Tax total captured at order time.
	 *
	 * @var string
	 */
	public string $tax_total_snapshot = '0';

	/**
	 * Withdrawal eligibility flag.
	 *
	 * @var string
	 */
	public string $eligibility_flag = 'eligible';

	/**
	 * Note explaining the eligibility flag.
	 *
	 * @var string
	 */
	public string $eligibility_note = '';

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

	/**
	 * Eligibility flag labels (translatable).
	 *
	 * @return array<string, string>
	 */
	public static function eligibility_labels(): array {
		return [
			'eligible' => __( 'Jogosult', 'elallas-for-woo' ),
			'excepted' => __( 'Kizárt – ellenőrizendő', 'elallas-for-woo' ),
		];
	}

	/**
	 * Human, translated label for this item's eligibility flag.
	 *
	 * @return string
	 */
	public function eligibility_label(): string {
		return self::eligibility_labels()[ $this->eligibility_flag ] ?? $this->eligibility_flag;
	}

	/**
	 * Whether the item is flagged as an exception (excluded from withdrawal).
	 *
	 * @return bool
	 */
	public function is_excepted(): bool {
		return 'excepted' === $this->eligibility_flag;
	}

	/**
	 * Sample item with placeholder data for the WooCommerce email preview.
	 *
	 * @return self
	 */
	public static function sample(): self {
		$instance                        = new self();
		$instance->product_name_snapshot = __( 'Mintatermék', 'elallas-for-woo' );
		$instance->sku_snapshot          = 'SKU-001';
		$instance->qty_ordered           = 1;
		$instance->qty_withdrawn         = 1;

		return $instance;
	}
}
