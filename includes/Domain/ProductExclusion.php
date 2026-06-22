<?php
/**
 * Resolves whether a product is excluded from withdrawal.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Domain;

use LightweightPlugins\Elallas\Admin\ProductFields;
use LightweightPlugins\Elallas\Admin\TermFields;

/**
 * Combines per-product exclusion (product meta) with taxonomy-based exclusion
 * (excluded product categories / tags, set as term meta on each term).
 */
final class ProductExclusion {

	/**
	 * Evaluate a product's exclusion status.
	 *
	 * @param int $product_id Product ID.
	 * @return array{0: bool, 1: string} [ excluded, reason label ].
	 */
	public static function evaluate( int $product_id ): array {
		if ( ProductFields::is_excluded( $product_id ) ) {
			return [ true, ProductFields::exclusion_label( $product_id ) ];
		}

		return self::taxonomy_exclusion( $product_id );
	}

	/**
	 * Whether the product belongs to an excluded category or tag (term meta).
	 *
	 * @param int $product_id Product ID.
	 * @return array{0: bool, 1: string}
	 */
	private static function taxonomy_exclusion( int $product_id ): array {
		if ( $product_id <= 0 || ! function_exists( 'wp_get_post_terms' ) ) {
			return [ false, '' ];
		}

		foreach ( [ 'product_cat', 'product_tag' ] as $taxonomy ) {
			$term_ids = wp_get_post_terms( $product_id, $taxonomy, [ 'fields' => 'ids' ] );

			if ( ! is_array( $term_ids ) ) {
				continue;
			}

			foreach ( $term_ids as $term_id ) {
				if ( TermFields::is_excluded_term( (int) $term_id ) ) {
					return [ true, TermFields::term_reason_label( (int) $term_id ) ];
				}
			}
		}

		return [ false, '' ];
	}
}
