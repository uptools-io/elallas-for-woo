<?php
/**
 * Resolves the GARAN data of products, cart items and order items.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

use LightweightPlugins\Elallas\Admin\GaranProductFields;

/**
 * Product / variation / cart item / order item → ?GaranData.
 *
 * Rules: not goods (virtual) → null; a variation with its own flag 'yes'
 * uses its own fields, 'no' → null, '' → inherits the parent; the parent
 * needs flag 'yes' and valid data. Memoised per request.
 */
final class GaranResolver {

	/**
	 * Order item meta holding the purchase-time snapshot (JSON).
	 */
	public const ITEM_META = '_lw_elallas_garan';

	/**
	 * Memo: "product:variation" => data|null.
	 *
	 * @var array<string, GaranData|null>
	 */
	private static array $memo = [];

	/**
	 * Whether half-year durations (x,5) are accepted.
	 *
	 * Disabled by default: with the conservative width check they overlap the
	 * calendar icon of the official label.
	 *
	 * @return bool
	 */
	public static function allow_half_years(): bool {
		/**
		 * Filters whether half-year GARAN durations (2,5–9,5) may be saved.
		 *
		 * @param bool $allow Default false.
		 */
		return (bool) apply_filters( 'elallas_garan_allow_half_years', false );
	}

	/**
	 * GARAN data of a product (optionally a specific variation).
	 *
	 * @param int $product_id   Product (parent) id.
	 * @param int $variation_id Variation id (0 = none).
	 * @return GaranData|null
	 */
	public static function for_product( int $product_id, int $variation_id = 0 ): ?GaranData {
		$key = $product_id . ':' . $variation_id;
		if ( ! array_key_exists( $key, self::$memo ) ) {
			self::$memo[ $key ] = self::resolve( $product_id, $variation_id );
		}

		return self::$memo[ $key ];
	}

	/**
	 * GARAN data of a cart item.
	 *
	 * @param array<string, mixed> $cart_item Cart item.
	 * @return GaranData|null
	 */
	public static function for_cart_item( array $cart_item ): ?GaranData {
		return self::for_product( absint( $cart_item['product_id'] ?? 0 ), absint( $cart_item['variation_id'] ?? 0 ) );
	}

	/**
	 * GARAN data of an order item: the purchase-time snapshot, else (old or
	 * manually created orders) the current product data.
	 *
	 * @param \WC_Order_Item_Product $item Order item.
	 * @return GaranData|null
	 */
	public static function for_order_item( \WC_Order_Item_Product $item ): ?GaranData {
		$raw = $item->get_meta( self::ITEM_META );
		if ( is_string( $raw ) && '' !== $raw ) {
			$snapshot = json_decode( $raw, true );

			return is_array( $snapshot ) ? GaranData::from_array( $snapshot ) : null;
		}

		return self::for_product( (int) $item->get_product_id(), (int) $item->get_variation_id() );
	}

	/**
	 * Resolve a product id that may itself be a variation.
	 *
	 * @param \WC_Product $product Product or variation.
	 * @return GaranData|null
	 */
	public static function for_wc_product( \WC_Product $product ): ?GaranData {
		if ( $product->is_type( 'variation' ) ) {
			return self::for_product( (int) $product->get_parent_id(), (int) $product->get_id() );
		}

		return self::for_product( (int) $product->get_id() );
	}

	/**
	 * Uncached resolution.
	 *
	 * @param int $product_id   Parent id.
	 * @param int $variation_id Variation id.
	 * @return GaranData|null
	 */
	private static function resolve( int $product_id, int $variation_id ): ?GaranData {
		$data    = null;
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $variation_id > 0 ? $variation_id : $product_id ) : null;

		if ( $product instanceof \WC_Product && GoodsScope::applies_to_product( $product ) ) {
			$data = self::from_meta( $product_id, $variation_id );
		}

		/**
		 * Filters the resolved GARAN data.
		 *
		 * @param GaranData|null $data         Data (null = no label).
		 * @param int            $product_id   Product id.
		 * @param int            $variation_id Variation id.
		 */
		$data = apply_filters( 'elallas_garan_data', $data, $product_id, $variation_id );

		return $data instanceof GaranData ? $data : null;
	}

	/**
	 * Data from the product / variation meta.
	 *
	 * @param int $product_id   Parent id.
	 * @param int $variation_id Variation id.
	 * @return GaranData|null
	 */
	private static function from_meta( int $product_id, int $variation_id ): ?GaranData {
		if ( $variation_id > 0 ) {
			$own = GaranProductFields::raw( $variation_id );
			if ( 'no' === $own['enabled'] ) {
				return null;
			}
			if ( 'yes' === $own['enabled'] ) {
				return GaranData::from_input( $own['years'], $own['brand'], $own['model'], true );
			}
		}

		$raw = GaranProductFields::raw( $product_id );
		if ( 'yes' !== $raw['enabled'] ) {
			return null;
		}

		// Saved values were validated (incl. the half-year switch) on save.
		return GaranData::from_input( $raw['years'], $raw['brand'], $raw['model'], true );
	}
}
