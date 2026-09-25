<?php
/**
 * Decides whether the compliance output applies (goods vs. digital-only).
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Compliance;

use LightweightPlugins\Elallas\Options;
use LightweightPlugins\Elallas\Woo\OrderAdapter;

/**
 * The notice obligation concerns goods (45/2014. Korm. rendelet 11. § (1a)).
 * Purely virtual (not shipped) products are excluded while the
 * `compliance_exclude_virtual` option is on (default).
 */
final class GoodsScope {

	/**
	 * Per-request memo: product id => applies.
	 *
	 * @var array<int, bool>
	 */
	private static array $memo = [];

	/**
	 * Whether the compliance output applies to a product.
	 *
	 * @param \WC_Product $product Product (simple, variable, variation, …).
	 * @return bool
	 */
	public static function applies_to_product( \WC_Product $product ): bool {
		$id = (int) $product->get_id();

		if ( ! isset( self::$memo[ $id ] ) ) {
			$result = ! ( Options::get( 'compliance_exclude_virtual' ) && self::is_virtual( $product ) );

			/**
			 * Filter whether the notice / GARAN applies to a product.
			 *
			 * @param bool        $result  Applies.
			 * @param \WC_Product $product Product.
			 */
			self::$memo[ $id ] = (bool) apply_filters( 'elallas_compliance_applies_to_product', $result, $product );
		}

		return self::$memo[ $id ];
	}

	/**
	 * Whether the current cart contains at least one covered (goods) item.
	 *
	 * @return bool
	 */
	public static function cart_has_goods(): bool {
		if ( ! function_exists( 'WC' ) || null === WC()->cart ) {
			return false;
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'] ?? null;

			if ( $product instanceof \WC_Product && self::applies_to_product( $product ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether an order contains at least one covered (goods) item.
	 *
	 * A deleted product counts as goods (the safe direction).
	 *
	 * @param \WC_Order $order Order.
	 * @return bool
	 */
	public static function order_has_goods( \WC_Order $order ): bool {
		foreach ( OrderAdapter::items( $order ) as $item ) {
			$id      = (int) ( $item['variation_id'] ? $item['variation_id'] : $item['product_id'] );
			$product = $id > 0 ? wc_get_product( $id ) : null;

			if ( ! $product instanceof \WC_Product || self::applies_to_product( $product ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Virtual check; a variable parent is virtual only if every variation is.
	 *
	 * @param \WC_Product $product Product.
	 * @return bool
	 */
	private static function is_virtual( \WC_Product $product ): bool {
		if ( ! $product->is_type( 'variable' ) ) {
			return $product->is_virtual();
		}

		$children = $product->get_children();

		if ( empty( $children ) ) {
			return false;
		}

		foreach ( $children as $child_id ) {
			$child = wc_get_product( $child_id );

			if ( ! $child instanceof \WC_Product || ! $child->is_virtual() ) {
				return false;
			}
		}

		return true;
	}
}
